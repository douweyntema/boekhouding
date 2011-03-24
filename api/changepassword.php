<?php

require_once("common.php");

if(!isset($_REQUEST["username"]) || !isset($_REQUEST["oldpassword"]) || !isset($_REQUEST["newpassword"])) {
	header("HTTP/1.1 400 Bad Request");
	echo "HTTP/1.1 400 Bad Request";
	die();
}

$username = $_REQUEST["username"];
$oldpassword = $_REQUEST["oldpassword"];
$newpassword = $_REQUEST["newpassword"];

$user = $GLOBALS["database"]->stdGetTry("adminUser", array("username"=>$username), array("userID", "customerID", "username", "password"), false);
if($user === false) {
	echo "wrongpassword";
	die();
}
if(crypt($oldpassword, $user["password"]) !== $user["password"]) {
	echo "wrongpassword";
	die();
}

$newPasswordHash = crypt($newpassword, '$6$');

ignore_user_abort(true);

// Update the password
$GLOBALS["database"]->stdSet("adminUser", array("username"=>$username), array("password"=>$newPasswordHash));

// Update the filesystem version
$customerID = $GLOBALS["database"]->stdGet("adminUser", array("username"=>$username), "customerID");
$filesystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "filesystemID");
$GLOBALS["database"]->stdIncrement("infrastructureFilesystem", array("filesystemID"=>$filesystemID), "filesystemVersion");

// Update all servers
$hosts = $GLOBALS["database"]->stdList("infrastructureMount", array("filesystemID"=>$filesystemID), "hostID");
updateHosts($hosts, "update-passwd");

echo "success";

?>