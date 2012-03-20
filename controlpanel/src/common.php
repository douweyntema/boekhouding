<?php

require_once(dirname(__FILE__) . "/config.php");
require_once("/usr/lib/phpdatabase/database.php");
require_once("/usr/lib/phpmail/mimemail.php");
require_once(dirname(__FILE__) . "/ui.php");

ignore_user_abort(true);

function exceptionHandler($exception)
{
	// TODO: netjes formatten
	mailAdmin("Controlpanel exception", $exception->__toString());
	die();
}

function exceptionHandlerTesting($exception)
{
	// TODO: netjes formatten
	die($exception->__toString());
}

if($_SERVER["REMOTE_ADDR"] == "127.0.0.1") {
	error_reporting(E_ALL);
	set_exception_handler("exceptionHandlerTesting");
} else {
	error_reporting(0);
	set_exception_handler("exceptionHandler");
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

function get($id)
{
	return isset($_GET[$id]) ? $_GET[$id] : null;
}

function post($id)
{
	return isset($_POST[$id]) ? $_POST[$id] : null;
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

$GLOBALS["components"] = array();
$GLOBALS["componentRights"] = array();

function defineRight($module, $rightName, $rightTitle, $rightDescription)
{
	$GLOBALS["componentRights"][$module][] = array("name"=>$rightName, "title"=>$rightTitle, "description"=>$rightDescription);
}

foreach($componentsEnabled as $component) {
	$GLOBALS["componentRights"][$component] = array();
	require_once(dirname(__FILE__) . "/$component/api.php");
	$title = $GLOBALS[$component . "Title"];
	$target = $GLOBALS[$component . "Target"];
	$GLOBALS["components"][$component] = array("name"=>$component, "title"=>$title, "target"=>$target);
	if(!in_array($target, array("admin", "both", "customer"))) {
		die("Internal error: undefined target '$target' in component '$component'");
	}
	if($target == "customer" || $target == "both") {
		$description = $GLOBALS[$component . "Description"];
		array_unshift($GLOBALS["componentRights"][$component], array("name"=>$component, "title"=>$title, "description"=>$description));
	}
}

$GLOBALS["rights"] = array();
foreach($GLOBALS["componentRights"] as $componentRights) {
	foreach($componentRights as $right) {
		$GLOBALS["rights"][] = $right;
	}
}

function components()
{
	return $GLOBALS["components"];
}

function componentExists($component)
{
	return isset($GLOBALS[$component]);
}

function rights()
{
	return $GLOBALS["rights"];
}

function getRootUser()
{
	$userID = $GLOBALS["database"]->stdList("adminUser", array("customerID"=>null), "userID", array("userID"=>"asc"), 1);
	return $userID[0];
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
<script type="text/javascript" src="{$GLOBALS["rootHtml"]}js/jquery.treeTable.js"></script>
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
<span>Logged in as $usernameHtml@$customerHtml - <a href="{$GLOBALS["rootHtml"]}logout.php">log out</a> - <a href="{$GLOBALS["rootHtml"]}customers/?customerID=0">back to $usernameHtml</a></span>
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

function menu()
{
	$output = "";
	
	if(isRoot()) {
		$output .= "<ul>\n";
		$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}\">Welcome</a></li>\n";
		foreach(components() as $component) {
			if($component["target"] == "customer") {
				continue;
			}
			$titleHtml = htmlentities($component["title"]);
			$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}{$component["name"]}/\">$titleHtml</a></li>\n";
		}
		$output .= "</ul>\n";
	} else {
		$blocked = array();
		$output .= "<ul>\n";
		$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}\">Welcome</a></li>\n";
		foreach(components() as $component) {
			if($component["target"] == "admin") {
				continue;
			}
			if(!canAccessCustomerComponent($component["name"])) {
				$blocked[] = $component;
				continue;
			}
			$titleHtml = htmlentities($component["title"]);
			$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}{$component["name"]}/\">$titleHtml</a></li>\n";
		}
		$output .= "</ul>\n";
		
		if(isImpersonating() && count($blocked) > 0) {
			$output .= "<ul class=\"menu-admin\">\n";
			foreach($blocked as $component) {
				$titleHtml = htmlentities($component["title"]);
				$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}{$component["name"]}/\">$titleHtml</a></li>\n";
			}
			$output .= "</ul>\n";
		}
	}
	
	return $output;
}

function page($content)
{
	echo htmlHeader(welcomeHeader() . "<div class=\"menu\">\n" . menu() . "</div>\n<div class=\"main\">\n" . $content . "</div>");
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

function changePasswordForm($postUrl, $error = "", $password = null)
{
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = "readonly=\"readonly\"";
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
	}
	
	if($readonly == "") {
		$passwordHtml = <<<HTML
<tr>
<th>Password:</th>
<td class="stretch"><input type="password" name="password1" /></td>
</tr>
<tr>
<th>Confirm password:</th>
<td class="stretch"><input type="password" name="password2" /></td>
</tr>

HTML;
	} else {
		$encryptedPassword = encryptPassword($password);
		$masked = str_repeat("*", strlen($password));
		$passwordHtml = <<<HTML
<tr>
<th>Password:</th>
<td><input type="password" value="$masked" readonly="readonly" /><input type="hidden" name="encryptedPassword" value="$encryptedPassword" /></td>
</tr>

HTML;
	}
	
	return <<<HTML
<div class="operation">
<h2>Change password</h2>
$messageHtml
<form action="$postUrl" method="post">
$confirmHtml
<table>
$passwordHtml
<tr class="submit">
<td colspan="2"><input type="submit" value="Change Password" /></td>
</tr>
</table>
</form>
</div>

HTML;
}

function checkPassword($content, $postUrl)
{
	if(post("confirm") === null) {
		if(post("password1") === null || post("password2") === null) {
			$content .= changePasswordForm($postUrl);
			die(page($content));
		}
		
		if(post("password1") != post("password2")) {
			$content .= changePasswordForm($postUrl, "The entered passwords do not match.", null);
			die(page($content));
		}
		
		if(post("password1") == "") {
			$content .= changePasswordForm($postUrl, "Passwords must be at least one character long.", null);
			die(page($content));
		}
		
		$content .= changePasswordForm($postUrl, null, post("password1"));
		die(page($content));
	}
	
	$password = decryptPassword(post("encryptedPassword"));
	if($password === null) {
		$content .= changePasswordForm($postUrl, "Internal error: invalid encrypted password. Please enter password again.", null);
		die(page($content));
	}
	
	return $password;
}

function trivialActionForm($postUrl, $error, $title, $warning = null, $extraInfo = "", $removeDataWarning = null, $data = null)
{
	if($error === null) {
		if($warning === null) {
			$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		} else {
			$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n<p class=\"confirmdelete\">$warning</p>\n";
		}
		if($removeDataWarning === null) {
			$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		} else {
			$confirmHtml = "<tr><td><label><input type=\"checkbox\" name=\"confirm\" value=\"1\" />$removeDataWarning</label></td></tr>\n";
		}
		$readonly = "readonly=\"readonly\"";
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
	}
	
	$dataHtml = "";
	if($data !== null) {
		foreach($data as $name=>$value) {
			$dataHtml .= "<input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
		}
	}
	
	$messageHtml .= $extraInfo;
	
	return <<<HTML
<div class="operation">
<h2>$title</h2>
$messageHtml
<form action="$postUrl" method="post">
$dataHtml
<table>
$confirmHtml
<tr class="submit"><td>
<input type="submit" value="$title" />
</td></tr></table>
</form>
</div>

HTML;
}

function checkTrivialAction($content, $postUrl, $title, $warning = null, $extraInfo = "", $removeDataWarning = null, $data = null)
{
	if(post("confirm") === null) {
		$content .= trivialActionForm($postUrl, null, $title, $warning, $extraInfo, $removeDataWarning, $data);
		die(page($content));
	}
	return true;
}

function formatPrice($cents)
{
	return "&euro; " . floor($cents / 100) . "," . str_pad($cents % 100, 2, "0");
}

function updateHosts($hosts, $command)
{
	foreach($hosts as $hostID) {
		$host = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("ipv4Address", "sshPort"));
		`/usr/bin/ssh -i {$GLOBALS["ssh_private_key_file"]} -l root -p {$host["sshPort"]} {$host["ipv4Address"]} '$command' > /dev/null &`;
	}
}

function updateAccounts($customerID)
{
	// Update the fileSystem version
	$fileSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "fileSystemID");
	$GLOBALS["database"]->stdIncrement("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "fileSystemVersion", 1000000000);
	
	// Update all servers
	$hosts = $GLOBALS["database"]->stdList("infrastructureMount", array("fileSystemID"=>$fileSystemID), "hostID");
	updateHosts($hosts, "update-treva-passwd");
}

function updateMail($customerID)
{
	$mailSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "mailSystemID");
	$GLOBALS["database"]->stdIncrement("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "version", 1000000000);
	
	$hosts = $GLOBALS["database"]->stdList("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), "hostID");
	updateHosts($hosts, "update-treva-dovecot");
	updateHosts($hosts, "update-treva-exim");
}

function updateHttp($customerID)
{
	$fileSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "fileSystemID");
	$GLOBALS["database"]->stdIncrement("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "httpVersion", 1000000000);
	
	$hosts = $GLOBALS["database"]->stdList("infrastructureWebServer", array("fileSystemID"=>$fileSystemID), "hostID");
	updateHosts($hosts, "update-treva-apache");
}

function breadcrumbs($breadcrumbs)
{
	$output = "";
	$output .= "\n<div class=\"breadcrumbs\">\n";
	$separator = "         ";
	foreach($breadcrumbs as $breadcrumb) {
		$urlHtml = htmlentities($breadcrumb["url"]);
		$nameHtml = htmlentities($breadcrumb["name"]);
		$output .= $separator . "<a href=\"$urlHtml\">$nameHtml</a>\n";
		$separator = "&gt;&gt; ";
	}
	$output .= "</div>\n\n";
	return $output;
}

function mailCustomer($customerID, $subject, $body, $bccAdmin = false)
{
	global $adminMail, $adminMailName;
	
	$customer = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), array("email", "name", "companyName", "initials", "lastName"));
	
	$name = trim($customer["initials"] . " " . $customer["lastName"]);
	if($name == "") {
		$name = trim($customer["companyName"]);
		if($name == "") {
			$name = $customer["name"];
		}
	}
	
	$mail = new mimemail();
	$mail->addReceiver($customer["email"], $name);
	$mail->setSender($adminMail, $adminMailName);
	$mail->setSubject($subject);
	if($bccAdmin) {
		$mail->addBcc($adminMail, $adminMailName);
	}
	$mail->setTextMessage($body);
	$mail->send();
}

function mailAdmin($subject, $body)
{
	global $adminMail, $adminMailName;
	
	$mail = new mimemail();
	$mail->addReceiver($adminMail, $adminMailName);
	$mail->setSender($adminMail, "Controlpanel");
	$mail->setSubject($subject);
	$mail->setTextMessage($body);
	$mail->send();
}

function error404()
{
	header("HTTP/1.1 404 Not Found");
	die("The requested page could not be found.");
}

?>