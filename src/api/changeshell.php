<?php

require_once("common.php");

if(!isset($_REQUEST["username"]) || !isset($_REQUEST["password"]) || !isset($_REQUEST["shell"])) {
	header("HTTP/1.1 400 Bad Request");
	echo "HTTP/1.1 400 Bad Request";
	die();
}

$available_shells = array(
	"/bin/csh",
	"/bin/sh",
	"/usr/bin/es",
	"/usr/bin/ksh",
	"/bin/ksh",
	"/usr/bin/rc",
	"/usr/bin/tcsh",
	"/bin/tcsh",
	"/usr/bin/esh",
	"/bin/dash",
	"/bin/bash",
	"/bin/rbash"
);

$username = $_REQUEST["username"];
$password = $_REQUEST["password"];
$shell = $_REQUEST["shell"];

$user = stdGetTry("adminUser", array("username"=>$username), array("userID", "customerID", "username", "password"), false);
if($user === false) {
	echo "wrongpassword";
	die();
}
if(crypt($password, $user["password"]) !== $user["password"]) {
	echo "wrongpassword";
	die();
}
if(!in_array($shell, $available_shells)) {
	echo "invalidshell";
	die();
}

ignore_user_abort(true);

// Update the shell
stdSet("adminUser", array("username"=>$username), array("shell"=>$shell));

// Distribute the accounts database
$customerID = stdGet("adminUser", array("username"=>$username), "customerID");
updateAccounts($customerID);

echo "success";

?>