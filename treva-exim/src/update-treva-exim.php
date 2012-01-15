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

$lockfile = "/var/lock/update-treva-exim.lock";
$lock = fopen($lockfile, "w");
flock($lock, LOCK_EX) OR die("Couldn't acquire global update-treva-exim lock");

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

$mailSystems = $database->stdList("infrastructureMailServer", array("hostID"=>$hostID), array("mailSystemID", "eximVersion"));

$updateNeeded = false;
foreach($mailSystems as $mailSystem) {
	$id = $mailSystem["mailSystemID"];
	$version = $mailSystem["eximVersion"];
	
	$databaseVersion = $database->stdGet("infrastructureMailSystem", array("mailSystemID"=>$id), "version");
	
	if($version != $databaseVersion) {
		$updateNeeded = true;
		$database->stdSet("infrastructureMailServer", array("hostID"=>$hostID, "mailSystemID"=>$id), array("eximVersion"=>$databaseVersion));
	}
}

if(!$updateNeeded && !$force) {
	exit(0);
}

$hostIDSql = $GLOBALS["database"]->addSlashes($hostID);

$databaseDirectory = "/etc/exim4/database";
umask(077);

$localDomains = "";
$relayDomains = "";
foreach($database->query("SELECT mailDomain.name AS name, self.`primary` AS `primary` FROM infrastructureMailServer AS self INNER JOIN infrastructureMailServer AS master USING(mailSystemID) INNER JOIN adminCustomer USING(mailSystemID) INNER JOIN mailDomain USING(customerID) WHERE self.hostID='$hostIDSql' AND master.`primary` = 1")->fetchList() as $domain) {
	if($domain["primary"]) {
		$localDomains .= $domain["name"] . "\n";
	} else {
		$relayDomains .= $domain["name"] . "\n";
	}
}
file_put_contents("$databaseDirectory/local_domains-$time", $localDomains);
file_put_contents("$databaseDirectory/relay_domains-$time", $relayDomains);

$localMailboxes = "";
foreach($database->query("SELECT mailDomain.name AS domain, localpart, spambox, virusbox FROM infrastructureMailServer INNER JOIN adminCustomer USING(mailSystemID) INNER JOIN mailDomain USING(customerID) INNER JOIN mailAddress USING(domainID) WHERE infrastructureMailServer.hostID='$hostIDSql' AND infrastructureMailServer.`primary` = 1")->fetchList() as $address) {
	$localMailboxes .= "{$address["localpart"]}@{$address["domain"]}";
	
	if($address["spambox"] === null) {
		$localMailboxes .= ":drop: ";
	} else if($address["spambox"] === "") {
		$localMailboxes .= ":inbox: ";
	} else {
		$localMailboxes .= ":folder:{$address["spambox"]}";
	}
	
	if($address["virusbox"] === null) {
		$localMailboxes .= ":drop: ";
	} else if($address["virusbox"] === "") {
		$localMailboxes .= ":inbox: ";
	} else {
		$localMailboxes .= ":folder:{$address["virusbox"]}";
	}
	
	$localMailboxes .= "\n";
}
file_put_contents("$databaseDirectory/local_mailboxes-$time", $localMailboxes);

$localAliases = "";
foreach($database->query("SELECT mailDomain.name AS domain, localpart, GROUP_CONCAT(targetAddress SEPARATOR ', ') AS aliases FROM infrastructureMailServer INNER JOIN adminCustomer USING(mailSystemID) INNER JOIN mailDomain USING(customerID) INNER JOIN mailAlias USING(domainID) WHERE infrastructureMailServer.hostID='$hostIDSql' AND infrastructureMailServer.`primary` = 1 GROUP BY domain, localpart")->fetchList() as $aliases) {
	$localAliases .= "{$aliases["localpart"]}@{$aliases["domain"]}:{$aliases["aliases"]}\n";
}
file_put_contents("$databaseDirectory/local_aliases-$time", $localAliases);

$relayAddresses = "";
foreach($database->query("SELECT mailDomain.name AS domain, localpart, host.hostname AS hostname FROM infrastructureMailServer AS backup INNER JOIN adminCustomer USING(mailSystemID) INNER JOIN mailDomain USING(customerID) INNER JOIN ((SELECT domainID, localpart FROM mailAddress) UNION (SELECT DISTINCT domainID, localpart FROM mailAlias)) AS localparts USING(domainID) INNER JOIN infrastructureMailServer AS master USING(mailSystemID) INNER JOIN infrastructureHost AS host ON host.hostID = master.hostID WHERE backup.hostID='$hostIDSql' AND backup.`primary` = 0 AND master.`primary` = 1")->fetchList() as $address) {
	$relayAddresses .= "{$address["localpart"]}@{$address["domain"]}:{$address["hostname"]}\n";
}
file_put_contents("$databaseDirectory/relay_addresses-$time", $relayAddresses);

$authPasswords = "";
foreach($database->query("SELECT mailDomain.name AS domain, localpart, password FROM infrastructureMailServer INNER JOIN adminCustomer USING(mailSystemID) INNER JOIN mailDomain USING(customerID) INNER JOIN mailAddress USING(domainID) WHERE infrastructureMailServer.hostID='$hostIDSql'")->fetchList() as $account) {
	$encodedPassword = rfc2047_encode($account["password"]);
	$authPasswords .= "{$account["localpart"]}@{$account["domain"]}:$encodedPassword\n";
}
file_put_contents("$databaseDirectory/auth_passwords-$time", $authPasswords);

foreach(array("local_domains", "relay_domains", "local_mailboxes", "local_aliases", "relay_addresses", "auth_passwords") as $file) {
	chown("$databaseDirectory/$file-$time", "Debian-exim");
	chgrp("$databaseDirectory/$file-$time", "Debian-exim");
	rename("$databaseDirectory/$file-$time", "$databaseDirectory/$file");
}

$GLOBALS["database"]->commitTransaction();

fclose($lock);
unlink($lockfile);

function rfc2047_encode($string)
{
	// Encoded chunks can be up to 75 bytes long. Since the wrapper is 17 bytes,
	// this means up to 58 bytes of base64-encoded data, which means
	// up to (int)(58/4)*3 = 42 bytes can be encoded per chunk.
	$chunks = array();
	foreach(str_split($string, 42) as $chunk) {
		$encoded = base64_encode($chunk);
		$chunks[] = "=?iso-8859-1?B?$encoded?=";
	}
	return implode(" ", $chunks);
}

?>