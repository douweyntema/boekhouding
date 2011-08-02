#!/usr/bin/php
<?php

chdir("/");

$force = false;
$verbose = false;

$arguments = $_SERVER["argv"];
array_shift($arguments);
foreach($arguments as $argument) {
	if($argument == "--force") {
		$force = true;
	}
	if($argument == "--verbose") {
		$verbose = true;
	}
}

function exceptionHandler($exception)
{
	echo "Internal error\n";
	exit(1);
}

if(!$verbose) {
	set_exception_handler("exceptionHandler");
	error_reporting(0);
}

if(posix_getuid() != 0) {
	echo "Error: root privileges required.\n";
	exit(2);
}

require_once("/usr/lib/phpdatabase/database.php");
require_once("/etc/treva-infrastructure/common.conf");

$lockfile = "/var/lock/update-treva-dovecot.lock";
$lock = fopen($lockfile, "w");
flock($lock, LOCK_EX) OR die("Couldn't acquire global update-treva-dovecot lock");

$GLOBALS["database"] = new MysqlConnection();
$GLOBALS["database"]->open($mysqlServer, $mysqlUsername, $mysqlPassword, $mysqlDatabase);

$time = time();
$date = date("r", $time);

$GLOBALS["database"]->startTransaction(true);

$hostID = $GLOBALS["database"]->stdGetTry("infrastructureHost", array("hostname"=>$hostname), "hostID");
if($hostID === null) {
	echo "Host not in database\n";
	exit(1);
}

$mailSystems = $database->stdList("infrastructureMailServer", array("hostID"=>$hostID, "primary"=>true), array("mailSystemID", "dovecotVersion"));

$updateNeeded = false;
foreach($mailSystems as $mailSystem) {
	$id = $mailSystem["mailSystemID"];
	$version = $mailSystem["dovecotVersion"];
	
	$databaseVersion = $database->stdGet("infrastructureMailSystem", array("mailSystemID"=>$id), "version");
	
	if($version != $databaseVersion) {
		$updateNeeded = true;
		$database->stdSet("infrastructureMailServer", array("hostID"=>$hostID, "mailSystemID"=>$id), array("dovecotVersion"=>$databaseVersion));
	}
}

if(!$updateNeeded && !$force) {
	exit(0);
}

$hostIDSql = $GLOBALS["database"]->addSlashes($hostID);

$mailboxes = $GLOBALS["database"]->query("SELECT addressID AS id, localpart, mailDomain.name AS domain, password, canUseImap, quota, spambox, virusbox, groupname, adminCustomer.email AS customerEmail, adminCustomer.mailQuota AS customerQuota FROM mailAddress INNER JOIN mailDomain USING(domainID) INNER JOIN adminCustomer USING(customerID) INNER JOIN infrastructureMailServer USING(mailSystemID) WHERE infrastructureMailServer.hostID = '$hostIDSql' AND infrastructureMailServer.primary = 1")->fetchList();

$allMailboxes = array();
$allDomains = array();
$allCustomers = array();
foreach($mailboxes as $mailbox) {
	$allMailboxes["{$mailbox["localpart"]}@{$mailbox["domain"]}"] = $mailbox;
	$allDomains[$mailbox["domain"]] = $mailbox;
	$allCustomers[$mailbox["groupname"]] = $mailbox;
}

// Create nonexisting mailboxes.
foreach($mailboxes as $mailbox) {
	$customerDirectory = "/var/mail/{$mailbox["groupname"]}";
	$domainDirectory = "$customerDirectory/{$mailbox["domain"]}";
	$mailboxDirectory = "$domainDirectory/{$mailbox["localpart"]}@{$mailbox["domain"]}-{$mailbox["id"]}";
	
	if(!file_exists($customerDirectory)) {
		mkdir($customerDirectory, 0750);
		chown($customerDirectory, "mailbox");
		chgrp($customerDirectory, "mailbox");
	}
	
	if(!file_exists($domainDirectory)) {
		mkdir($domainDirectory, 0750);
		chown($domainDirectory, "mailbox");
		chgrp($domainDirectory, "mailbox");
	}
	
	if(!file_exists($mailboxDirectory)) {
		mkdir($mailboxDirectory, 0750);
		chown($mailboxDirectory, "mailbox");
		chgrp($mailboxDirectory, "mailbox");
	}
}

// Write passwd file.
$passwd = "";
foreach($mailboxes as $mailbox) {
	$customerDirectory = "/var/mail/{$mailbox["groupname"]}";
	$domainDirectory = "$customerDirectory/{$mailbox["domain"]}";
	$mailboxDirectory = "$domainDirectory/{$mailbox["localpart"]}@{$mailbox["domain"]}-{$mailbox["id"]}";
	
	// Username
	$passwd .= "{$mailbox["localpart"]}@{$mailbox["domain"]}:";
	// Password
	if($mailbox["canUseImap"]) {
		$passwd .= "{PLAIN.b64}" . base64_encode($mailbox["password"]) . ":";
	} else {
		$passwd .= ":";
	}
	// uid, gid, gecos
	$passwd .= ":::";
	// Home directory
	$passwd .= "$mailboxDirectory:";
	// Shell
	$passwd .= ":";
	
	// Quota information encoded in extra variables
	// Warning settings have been hard-configured in the config file,
	// because they must contain spaces which cannot occur in a passwd file.
	$passwd .= "userdb_customeremail={$mailbox["customerEmail"]}";
	// Quota1 sends warnings to the customer contact email.
	if($mailbox["customerQuota"] !== null) {
		$passwd .= " userdb_quota=dict:Global:customer:file:$customerDirectory/quota";
		$passwd .= " userdb_quota_rule=*:storage={$mailbox["customerQuota"]}MB";
	}
	// Quota2 sends warnings to the overflowing mailbox.
	if($mailbox["quota"] !== null) {
		$passwd .= " userdb_quota2=dict:Mailbox::file:$mailboxDirectory/quota";
		$passwd .= " userdb_quota2_rule=*:storage={$mailbox["quota"]}MB";
	}
	
	$passwd .= "\n";
}

$umask = umask(077);
file_put_contents("/etc/dovecot/passwd-$time", $passwd);
chown("/etc/dovecot/passwd-$time", "dovecot-auth");
chgrp("/etc/dovecot/passwd-$time", "dovecot-auth");
rename("/etc/dovecot/passwd-$time", "/etc/dovecot/passwd");
umask($umask);

// "Delete" (move out of the way) superfluous mailboxes.
$dir = opendir("/var/mail");
while(($customer = readdir($dir)) !== false) {
	if($customer == "." || $customer == "..") {
		continue;
	}
	if(!is_dir("/var/mail/$customer")) {
		continue;
	}
	$customerDir = opendir("/var/mail/$customer");
	while(($domain = readdir($customerDir)) !== false) {
		if($domain == "." || $domain == "..") {
			continue;
		}
		if(!is_dir("/var/mail/$customer/$domain")) {
			continue;
		}
		$mailboxDir = opendir("/var/mail/$customer/$domain");
		while(($mailbox = readdir($mailboxDir)) !== false) {
			if($mailbox == "." || $mailbox == "..") {
				continue;
			}
			if(!is_dir("/var/mail/$customer/$domain/$mailbox")) {
				continue;
			}
			
			$delete = false;
			$pos = strrpos($mailbox, "-");
			if($pos === false) {
				$delete = true;
			} else {
				$address = substr($mailbox, 0, $pos);
				$id = substr($mailbox, $pos + 1);
				
				if(!isset($allMailboxes[$address]) || $allMailboxes[$address]["id"] != $id) {
					$delete = true;
				}
			}
			
			if($delete) {
				rename("/var/mail/$customer/$domain/$mailbox", "/var/backups/mail/mailbox-$time-$mailbox");
			}
		}
		closedir($mailboxDir);
		
		if(!isset($allDomains[$domain])) {
			rename("/var/mail/$customer/$domain", "/var/backups/mail/domain-$time-$domain");
		}
	}
	closedir($customerDir);
	
	if(!isset($allCustomers[$customer])) {
		rename("/var/mail/$customer", "/var/backups/mail/customer-$time-$customer");
	}
}
closedir($dir);

$GLOBALS["database"]->commitTransaction();

fclose($lock);
unlink($lockfile);

?>