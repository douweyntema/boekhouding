<?php

$locale = "nl_NL.UTF-8";
putenv("LC_ALL=$locale");
setlocale(LC_ALL, $locale);

bindtextdomain("boekhouding", dirname(__FILE__) . "/../locale");
textdomain("boekhouding");

$_ = "gettext";

require_once(dirname(__FILE__) . "/config.php");
require_once("/usr/lib/phpdatabase/database.php");
require_once("/usr/lib/phpmail/mimemail.php");
require_once(dirname(__FILE__) . "/ui.php");
require_once(dirname(__FILE__) . "/util.php");

ignore_user_abort(true);

function exceptionHandler($exception)
{
	$message = $exception->__toString() . "\n\n";
	$message .= "\$_SERVER = " . var_export($_SERVER, TRUE) . "\n\n";
	$message .= "\$_GET = " . var_export($_GET, TRUE) . "\n\n";
	$message .= "\$_POST = " . var_export($_POST, TRUE) . "\n\n";
	$message .= "\$_SESSION[username] = " . $_SESSION["username"] . "\n\n";
	mailAdmin("Controlpanel exception", $message);
	die("Internal error. An administrator has been informed.");
}

class AssertionError extends Exception {}

if($_SERVER["REMOTE_ADDR"] == "127.0.0.1" || $_SERVER["REMOTE_ADDR"] == "::1") {
	error_reporting(E_ALL & ~E_DEPRECATED);
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

foreach($componentsEnabled as $component) {
	$GLOBALS["componentRights"][$component] = array();
	require_once(dirname(__FILE__) . "/$component/api.php");
	$title = $GLOBALS[$component . "Title"];
	$target = $GLOBALS[$component . "Target"];
	$menu = isset($GLOBALS[$component . "Menu"]) ? $GLOBALS[$component . "Menu"] : $target;
	$extraMenuItems = isset($GLOBALS[$component . "ExtraMenuItems"]) ? $GLOBALS[$component . "ExtraMenuItems"] : array();
	$GLOBALS["components"][$component] = array("name"=>$component, "title"=>$title, "target"=>$target, "menu"=>$menu, "extraMenuItems"=>$extraMenuItems);
	if(!in_array($target, array("admin", "both", "customer"))) {
		die("Internal error: undefined target '$target' in component '$component'");
	}
	if(!in_array($menu, array("admin", "both", "customer"))) {
		die("Internal error: undefined menu '$menu' in component '$component'");
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
	$userID = stdList("adminUser", array("customerID"=>null), "userID", array("userID"=>"asc"), 1);
	return $userID[0];
}

function htmlHeader($content)
{
	if(isset($GLOBALS["brandingColor"]) && $GLOBALS["brandingColor"] != "") {
		$style = <<<CSS
<style>
.welcome {
	background-color: {$GLOBALS["brandingColor"]};
}
h1 {
	color: {$GLOBALS["brandingColor"]};
}
</style>

CSS;
	} else {
		$style = "";
	}
return <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="{$GLOBALS["rootHtml"]}css/layout.css" />
<link rel="stylesheet" type="text/css" href="{$GLOBALS["rootHtml"]}css/jquery-ui.css" />
<link rel="stylesheet" type="text/css" href="{$GLOBALS["rootHtml"]}css/font-awesome/css/font-awesome.min.css" />
<script type="text/javascript" src="{$GLOBALS["rootHtml"]}js/jquery.js"></script>
<script type="text/javascript" src="{$GLOBALS["rootHtml"]}js/jquery-ui.js"></script>
<script type="text/javascript" src="{$GLOBALS["rootHtml"]}js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="{$GLOBALS["rootHtml"]}js/jquery.treeTable.js"></script>
<script type="text/javascript" src="{$GLOBALS["rootHtml"]}js/script.js"></script>
<title>{$GLOBALS["htmlTitle"]}</title>
$style
</head>
<body>
$content
</body>
</html>

HTML;
}

function welcomeHeader()
{
	global $_;
	if(isLoggedIn()) {
		$loginMessage = sprintf(_("Logged in as %s"), htmlentities(username()));
		return <<<HTML
<div class="welcome">
<span>$loginMessage - <a href="{$GLOBALS["rootHtml"]}logout.php">{$_("log out")}</a></span>
</div>

HTML;
	} else {
		return <<<HTML
<div class="welcome">
<span>{$GLOBALS["htmlTitle"]}</span>
</div>

HTML;
	}
}

function menu()
{
	$output = "";
	
	if(isRoot()) {
		$output .= "<ul>\n";
		$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}\">" . _("Welcome") . "</a></li>\n";
		foreach(components() as $component) {
			if($component["menu"] == "customer") {
				continue;
			}
			$titleHtml = htmlentities($component["title"]);
			$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}{$component["name"]}/\">$titleHtml</a></li>\n";
			foreach($component["extraMenuItems"] as $menuItem) {
				$titleHtml = htmlentities($menuItem["title"]);
				$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}{$menuItem["url"]}\">$titleHtml</a></li>\n";
			}
		}
		$output .= "</ul>\n";
	} else {
		$blocked = array();
		$output .= "<ul>\n";
		$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}\">Welcome</a></li>\n";
		foreach(components() as $component) {
			if($component["menu"] == "admin") {
				continue;
			}
			if(!canAccessCustomerComponent($component["name"])) {
				$blocked[] = $component;
				continue;
			}
			$titleHtml = htmlentities($component["title"]);
			$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}{$component["name"]}/\">$titleHtml</a></li>\n";
			foreach($component["extraMenuItems"] as $menuItem) {
				$titleHtml = htmlentities($menuItem["title"]);
				$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}{$menuItem["url"]}\">$titleHtml</a></li>\n";
			}
		}
		$output .= "</ul>\n";
		
		if(isImpersonating() && count($blocked) > 0) {
			$output .= "<ul class=\"menu-admin\">\n";
			foreach($blocked as $component) {
				$titleHtml = htmlentities($component["title"]);
				$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}{$component["name"]}/\">$titleHtml</a></li>\n";
				foreach($component["extraMenuItems"] as $menuItem) {
					$titleHtml = htmlentities($menuItem["title"]);
					$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}{$menuItem["url"]}\">$titleHtml</a></li>\n";
				}
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

function updateHosts($hosts, $command)
{
	foreach($hosts as $hostID) {
		$host = stdGet("infrastructureHost", array("hostID"=>$hostID), array("ipv4Address", "sshPort"));
		`/usr/bin/ssh -i {$GLOBALS["ssh_private_key_file"]} -l root -p {$host["sshPort"]} {$host["ipv4Address"]} '$command' > /dev/null &`;
	}
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
	$customer = stdGet("adminCustomer", array("customerID"=>$customerID), array("email", "name", "companyName", "initials", "lastName"));
	
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

function error404()
{
	header("HTTP/1.1 404 Not Found");
	die("The requested page could not be found.");
}

function redirect($url)
{
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}$url");
	die();
}

?>