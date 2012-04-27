<?php

define("COUNTRYCODES_FILE", dirname(__FILE__) . "/../countrycodes");

require_once(dirname(__FILE__) . "/config.php");
require_once("/usr/lib/phpdatabase/database.php");
require_once("/usr/lib/phpmail/mimemail.php");
require_once(dirname(__FILE__) . "/ui.php");

ignore_user_abort(true);

function exceptionHandler($exception)
{
	mailAdmin("Controlpanel exception", $exception->__toString());
	die("Internal error. An administrator has been informed.");
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

function searchKey($array /*, $keys */)
{
	$keys = func_get_args();
	array_shift($keys);
	foreach($keys as $key) {
		if(isset($array[$key])) {
			return $key;
		}
	}
	return null;
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

function formatPrice($cents)
{
	return "&euro; " . formatPriceRaw($cents);
}

function formatPriceRaw($cents)
{
	return floor($cents / 100) . "," . str_pad($cents % 100, 2, "0", STR_PAD_LEFT);
}

function parsePrice($string)
{
	$count = preg_match("/^(?<euro>[0-9]*)([,\\.](?<cent>[0-9]{2}))?$/", $string, $matches);
	if($count !== 1) {
		return null;
	}
	if(isset($matches["cent"])) {
		return $matches["euro"] * 100 + $matches["cent"];
	} else {
		return $matches["euro"] * 100;
	}
}

function parseDate($string)
{
	$date = strtotime($string);
	if($date === false) {
		return null;
	}
	return $date;
}

function updateHosts($hosts, $command)
{
	foreach($hosts as $hostID) {
		$host = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("ipv4Address", "sshPort"));
		`/usr/bin/ssh -i {$GLOBALS["ssh_private_key_file"]} -l root -p {$host["sshPort"]} {$host["ipv4Address"]} '$command' > /dev/null &`;
	}
}

// TODO: move update* functies naar de respectivelijke api
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
	$output = "<div class=\"breadcrumbs\">\n";
	$separator = "         ";
	foreach($breadcrumbs as $breadcrumb) {
		$urlHtml = htmlentities($breadcrumb["url"]);
		$nameHtml = htmlentities($breadcrumb["name"]);
		$output .= $separator . "<a href=\"$urlHtml\">$nameHtml</a>\n";
		$separator = "&gt;&gt; ";
	}
	$output .= "</div>\n";
	return $output;
}

function makeHeader($title/*, $breadcrumbs*/)
{
	$breadcrumbsList = func_get_args();
	array_shift($breadcrumbsList);
	$breadcrumbs = array();
	foreach($breadcrumbsList as $crumbs) {
		$breadcrumbs = array_merge($breadcrumbs, $crumbs);
	}
	return "<h1>$title</h1>\n" . breadcrumbs($breadcrumbs);
}

function mailCustomer($customerID, $subject, $body, $bccAdmin = true)
{
	$customer = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), array("email", "name", "companyName", "initials", "lastName"));
	
	$name = trim($customer["initials"] . " " . $customer["lastName"]);
	if($name == "") {
		$name = trim($customer["companyName"]);
		if($name == "") {
			$name = $customer["name"];
		}
	}
	
	$mail = new mimemail();
	if(isset($GLOBALS["controlpanelEnableCustomerEmail"]) && $GLOBALS["controlpanelEnableCustomerEmail"]) {
		$mail->addReceiver($customer["email"], $name);
	} else {
		$mail->addReceiver($GLOBALS["adminMail"], $GLOBALS["adminMailName"]);
		$subject = "TEST: " . $subject;
	}
	$mail->setSender($GLOBALS["adminMail"], $GLOBALS["adminMailName"]);
	$mail->setSubject($subject);
	if($bccAdmin) {
		$mail->addBcc($GLOBALS["adminMail"], $GLOBALS["adminMailName"]);
	}
	$mail->setTextMessage($body);
	$mail->send();
}

function mailAdmin($subject, $body)
{
	$mail = new mimemail();
	$mail->addReceiver($GLOBALS["adminMail"], $GLOBALS["adminMailName"]);
	$mail->setSender($GLOBALS["adminMail"], "Controlpanel");
	$mail->setSubject($subject);
	$mail->setTextMessage($body);
	$mail->send();
}

function pdfLatex($tex)
{
	$dir = tempnam("/tmp", "controlpanel-");
	unlink($dir);
	mkdir($dir);
	chdir($dir);
	
	$h = fopen($dir . "/file.tex", "w");
	fwrite($h, $tex);
	fclose($h);
	
	$md5 = "";
	$count = 10;
	do {
		`pdflatex $dir/file.tex`;
		if(!file_exists("$dir/file.pdf")) {
			return null;
		}
		$oldmd5 = $md5;
		$md5 = md5(file_get_contents("$dir/file.pdf"));
		$count--;
	} while($md5 != $oldmd5 && $count > 0);
	
	$pdf = file_get_contents($dir . "/file.pdf");
	`rm -r $dir`;
	return $pdf;
}

function texdate($date)
{
	$maanden = array("",
		"januari",
		"februari",
		"maart",
		"april",
		"mei",
		"juni",
		"juli",
		"augustus",
		"september",
		"oktober",
		"november",
		"december");
	$day = date("j", $date);
	$month = date("n", $date);
	$year = date("Y", $date);
	return $day . " " . $maanden[$month] . " " . $year;
}

function countryCodes()
{
	$countryCodes = array();
	foreach(explode("\n", file_get_contents(COUNTRYCODES_FILE)) as $line) {
		if($line == "") {
			continue;
		}
		$parts = explode(" ", $line);
		$code = $parts[0];
		array_shift($parts);
		$name = implode(" ", $parts);
		$countryCodes[$code] = $name; 
	}
	return $countryCodes;
}

function countryName($code)
{
	$code = strtoupper($code);
	$country = countryCodes();
	if(isset($country[$code])) {
		return $country[$code];
	} else {
		return $code;
	}
}

function error404()
{
	header("HTTP/1.1 404 Not Found");
	die("The requested page could not be found.");
}

?>