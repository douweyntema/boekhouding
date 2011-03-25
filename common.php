<?php

require_once(dirname(__FILE__) . "/config.php");
require_once("/usr/lib/phpdatabase/database.php");
require_once(dirname(__FILE__) . "/menu.php");

function exceptionHandler($exception)
{
	// TODO: netjes formatten
	die($exception->__toString());
}

set_exception_handler("exceptionHandler");
error_reporting(0);

if($_SERVER["REMOTE_ADDR"] == "127.0.0.1") {
	error_reporting(E_ALL);
}

$GLOBALS["database"] = new MysqlConnection();
$GLOBALS["database"]->open($database_hostname, $database_username, $database_password, $database_name);

if(get_magic_quotes_gpc()) {
	foreach($_GET as $key=>$value) {
		$_GET[$key] = utf8_decode(stripslashes($value));
	}
	foreach($_POST as $key=>$value) {
		$_POST[$key] = utf8_decode(stripslashes($value));
	}
	foreach($_COOKIE as $key=>$value) {
		$_COOKIE[$key] = utf8_decode(stripslashes($value));
	}
}

function relativeRoot()
{
	$directories = substr_count(realpath($_SERVER["SCRIPT_FILENAME"]), "/") - substr_count(realpath(__FILE__), "/");
	return str_repeat("../", $directories);
}

$GLOBALS["relativeRoot"] = relativeRoot();

function absoluteRoot()
{
	$directories = substr_count(realpath($_SERVER["SCRIPT_FILENAME"]), "/") - substr_count(realpath(__FILE__), "/");
	$url = substr($_SERVER["PHP_SELF"], 0, strrpos($_SERVER["PHP_SELF"], "/") + 1);
	while($directories > 0) {
		$url = substr($url, 0, -1);
		$url = substr($url, 0, strrpos($url, "/") + 1);
		$directories--;
	}
	return $url;
}

$GLOBALS["root"] = absoluteRoot();
$GLOBALS["rootHtml"] = htmlentities($GLOBALS["root"]);

if(!(isset($GLOBALS["noLoginChecks"]) && $GLOBALS["noLoginChecks"])) {
	require_once(dirname(__FILE__) . "/login.php");
}

function htmlHeader($content)
{
return <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="{$GLOBALS["rootHtml"]}layout.css" />
<script type="text/javascript" src="{$GLOBALS["rootHtml"]}js/jquery.js"></script>
<script type="text/javascript" src="{$GLOBALS["rootHtml"]}js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="{$GLOBALS["rootHtml"]}js/script.js"></script>
<title>Treva control panel</title>
</head>
<body>
$content
</body>
</html>

HTML;
}

function welcomeHeader()
{
	if(isLoggedIn() && isImpersonating()) {
		$usernameHtml = htmlentities(username());
		$customerHtml = htmlentities(impersonatedCustomer());
		return <<<HTML
<div class="welcome">
<span>Logged in as $usernameHtml@$customerHtml - <a href="{$GLOBALS["rootHtml"]}logout.php">log out</a> - <a href="{$GLOBALS["rootHtml"]}index.php?customerID=0">back to $usernameHtml</a></span>
</div>

HTML;
	
	} else if(isLoggedIn()) {
		$usernameHtml = htmlentities(username());
		return <<<HTML
<div class="welcome">
<span>Logged in as $usernameHtml - <a href="{$GLOBALS["rootHtml"]}logout.php">log out</a></span>
</div>

HTML;
	} else {
		return <<<HTML
<div class="welcome">
<span>Treva control panel</span>
</div>

HTML;
	}
}

function page($content)
{
	echo htmlHeader(welcomeHeader() . "<div class=\"menu\">\n" . menu() . "</div>\n<div class=\"main\">\n" . $content . "</div>");
}

function componentExists($component)
{
	return $GLOBALS["database"]->stdGetTry("adminComponent", array("name"=>$component), "componentID", false) !== false;
}

function components()
{
	return $GLOBALS["database"]->stdList("adminComponent", array(), array("componentID", "name", "title", "description", "rootOnly"), array("order"=>"ASC", "name"=>"ASC"));
}

function inputValue($value)
{
	if($value === null || $value === "") {
		return "";
	} else {
		return "value=\"" . htmlentities($value) . "\"";
	}
}

function hashPassword($password)
{
	$salt = base64_encode(mcrypt_create_iv(12, MCRYPT_DEV_URANDOM));
	return crypt($password, '$6$' . $salt);
}

function verifyPassword($password, $passwordHash)
{
	return crypt($password, $passwordHash) === $passwordHash;
}

function encryptPassword($password)
{
	$iv = mcrypt_create_iv(32, MCRYPT_DEV_URANDOM);
	$plaintext = md5($password) . base64_encode($password);
	$key = md5($GLOBALS["crypto_key"], true);
	$cipher = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $plaintext, MCRYPT_MODE_CBC, $iv);
	return base64_encode($iv) . ":" . base64_encode($cipher);
}

function decryptPassword($cipher)
{
	$pos = strpos($cipher, ':');
	if($pos === false) {
		return null;
	}
	$iv = base64_decode(substr($cipher, 0, $pos));
	$decoded = base64_decode(substr($cipher, $pos + 1));
	$key = md5($GLOBALS["crypto_key"], true);
	$plaintext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv);
	$checksum = substr($plaintext, 0, 32);
	$password = base64_decode(substr($plaintext, 32));
	if(md5($password) != $checksum) {
		return null;
	}
	return $password;
}

function updateHosts($hosts, $command)
{
	foreach($hosts as $hostID) {
		$host = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("hostname", "sshPort"));
		`ssh -i $ssh_private_key_file -l root -p {$host["sshPort"]} {$host["hostname"]} '$command'`;
	}
}

?>