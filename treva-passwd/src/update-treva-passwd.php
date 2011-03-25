#!/usr/bin/php
<?php

define("CUSTOMER_UID_MIN", 10000);
define("CUSTOMER_UID_MAX", 60000);

$accountDisabled = "-e 1970-01-01 -f 0";

function exceptionHandler($exception)
{
	echo "Internal error\n";
	exit(1);
}

//set_exception_handler("exceptionHandler");
//error_reporting(0);

if(posix_getuid() != 0) {
	echo "Error: root privileges required.\n";
	exit(2);
}

require_once("/etc/treva-update-passwd.conf");
require_once("/usr/lib/phpdatabase/database.php");

$force = false;

$arguments = $_SERVER["argv"];
array_shift($arguments);
foreach($arguments as $argument) {
	if($argument == "--force") {
		$force = true;
	}
}

$database = new MysqlConnection();
try {
	$database->open($mysqlServer, $mysqlUsername, $mysqlPassword, $mysqlDatabase);
} catch(DatabaseException $exception) {
	echo "Could not connect to database\n";
	exit(1);
}

$database->startTransaction();

$hostID = $database->stdGetTry("infrastructureHost", array("hostname"=>$hostname), "hostID");
if($hostID === null) {
	echo "Host not in database\n";
	exit(1);
}

$filesystems = $database->stdList("infrastructureMount", array("hostID"=>$hostID), array("filesystemID", "version", "allowCustomerLogin"));

$updateNeeded = false;
foreach($filesystems as $filesystem) {
	$id = $filesystem["filesystemID"];
	$version = $filesystem["version"];
	
	$databaseVersion = $database->stdGet("infrastructureFilesystem", array("filesystemID"=>$id), "filesystemVersion");
	
	if($version != $databaseVersion) {
		$updateNeeded = true;
		$database->stdSet("infrastructureMount", array("hostID"=>$hostID, "filesystemID"=>$id), array("version"=>$databaseVersion));
	}
}

if(!$updateNeeded && !$force) {
	exit(0);
}

$shellComponentID = $database->stdGet("adminComponent", array("name"=>"shell"), "componentID");

$allusers = array();
$allgroups = array();

$hostIDEscaped = $database->addSlashes($hostID);
$users = $database->query("SELECT adminUser.userID AS userID, adminUser.username AS username, adminUser.password AS password, adminUser.shell AS shell, adminCustomer.customerID AS customerID, adminCustomer.groupname AS groupname, infrastructureMount.allowCustomerLogin as loginAllowed FROM infrastructureMount INNER JOIN infrastructureFilesystem ON infrastructureMount.filesystemID = infrastructureFilesystem.filesystemID INNER JOIN adminCustomer ON infrastructureFilesystem.filesystemID = adminCustomer.filesystemID INNER JOIN adminUser ON adminCustomer.customerID = adminUser.customerID WHERE infrastructureMount.hostID = '$hostIDEscaped' ORDER BY adminUser.userID ASC")->fetchList();

foreach($users as $user) {
	$uid = $user["userID"] + CUSTOMER_UID_MIN;
	$gid = $user["customerID"] + CUSTOMER_UID_MIN;
	$allusers[$uid] = $user;
	$allgroups[$gid] = $user;
	
	if(!$user["loginAllowed"]) {
		$isDisabled = true;
	} else if($database->stdGetTry("adminCustomerRight", array("customerID"=>$user["customerID"], "componentID"=>$shellComponentID), "customerID", null) === null) {
		$isDisabled = true;
	} else if($database->stdGetTry("adminUserRight", array("userID"=>$user["userID"], "componentID"=>0), "userID", null) !== null) {
		$isDisabled = false;
	} else if($database->stdGetTry("adminUserRight", array("userID"=>$user["userID"], "componentID"=>$shellComponentID), "userID", null) === null) {
		$isDisabled = true;
	} else {
		$isDisabled = false;
	}
	$disabled = ($isDisabled ? $accountDisabled : "");
	
	$gr = posix_getgrgid($gid);
	if($gr === false) {
		`groupadd -g $gid {$user["groupname"]}`;
	}
	
	$pw = posix_getpwuid($uid);
	if($pw === false) {
		`useradd $disabled -m -u $uid -p {$user["password"]} -s {$user["shell"]} -g $gid {$user["username"]} >/dev/null 2>/dev/null`;
		$chpasswd = popen("chpasswd -e", "w");
		fwrite($chpasswd, "{$user["username"]}:{$user["password"]}\n");
		pclose($chpasswd);
	} else if($pw["name"] == $user["username"]) {
		`usermod $disabled -s {$user["shell"]} -g $gid {$user["username"]} >/dev/null 2>/dev/null`;
		$chpasswd = popen("chpasswd -e", "w");
		fwrite($chpasswd, "{$user["username"]}:{$user["password"]}\n");
		pclose($chpasswd);
	} else {
		echo "WARNING: inconsistent user found: user '$uid:{$user["username"]}' in the control panel does not match user '{$pw["uid"]}:{$pw["name"]}' in /etc/passwd, skipping\n";
		continue;
	}
}

$passwd = `bash -c "VISUAL=cat vipw -q 2>/dev/null"`;
foreach(explode("\n", trim($passwd)) as $user) {
	$fields = explode(":", $user);
	$username = $fields[0];
	$uid = $fields[2];
	if($uid >= CUSTOMER_UID_MIN && $uid < CUSTOMER_UID_MAX && !isset($allusers[$uid])) {
		`usermod $accountDisabled $username`;
		`killall -u $username`;
		`killall -u $username -9`;
		`userdel $username`;
	}
}

$groups = `bash -c "VISUAL=cat vigr -q 2>/dev/null"`;
foreach(explode("\n", trim($groups)) as $group) {
	$fields = explode(":", $group);
	$groupname = $fields[0];
	$gid = $fields[2];
	if($gid >= CUSTOMER_UID_MIN && $gid < CUSTOMER_UID_MAX && !isset($allgroups[$gid])) {
		if($fields[3] != "") {
			echo "WARNING: nonempty undefined group found: group '$gid:$groupname' in /etc/groups does not exist in the control panel, but has members '{$fields[3]}', skipping\n";
			continue;
		}
		`groupdel $groupname`;
	}
}

$database->commitTransaction();

?>