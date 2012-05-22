<?php

require_once(dirname(__FILE__) . "/config.php");
require_once("/usr/lib/phpdatabase/database.php");

if(!isset($_SERVER['PHP_AUTH_USER'])) {
	loginFailed();
}

$username = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

$GLOBALS["database"] = new MysqlConnection();
$GLOBALS["database"]->open($database_hostname, $database_username, $database_password, $database_name);

$user = $GLOBALS["database"]->stdGetTry("adminUser", array("username"=>$username), array("userID", "customerID", "username", "password"), false);
if($user === false) {
	loginFailed();
}
if(!crypt($password, $user["password"]) === $user["password"]) {
	loginFailed();
}
$customerRightID = $GLOBALS["database"]->stdGetTry("adminCustomerRight", array("customerID"=>$user["customerID"], "right"=>"domains"), "customerRightID", false);
if($customerRightID === false) {
	loginFailed();
}
if(!$GLOBALS["database"]->stdExists("adminUserRight", array("userID"=>$user["userID"], "customerRightID"=>$customerRightID)) && !$GLOBALS["database"]->stdExists("adminUserRight", array("userID"=>$user["userID"], "customerRightID"=>null))) {
	loginFailed();
}

if(!isset($_GET["hostname"])) {
	invalidRequest("nohost");
}
$hostnames = $_GET["hostname"];
if(!isset($_GET["myip"]) || !validIPv4($_GET["myip"])) {
	$ip = $_SERVER["REMOTE_ADDR"];
} else {
	$ip = $_GET["myip"];
}

$domainIDs = array();
foreach(explode(",", $hostnames) as $hostname) {
	$domainID = domainID($hostname);
	if($domainID === false) {
		invalidRequest("notfqdn");
	}
	$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("customerID", "addressType"));
	if($domain["customerID"] != $user["customerID"]) {
		invalidRequest("nohost");
	}
	if($domain["addressType"] != "IP") {
		invalidRequest("nohost");
	}
	$domainIDs[] = $domainID;
}

$GLOBALS["database"]->startTransaction();
foreach($domainIDs as $domainID) {
	if($GLOBALS["database"]->stdGetTry("dnsRecord", array("domainID"=>$domainID, "type"=>"A"), "value", null) != $ip) {
		$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID, "type"=>"A"));
		$GLOBALS["database"]->stdNew("dnsRecord", array("domainID"=>$domainID, "type"=>"A", "value"=>$ip));
	}
}
$GLOBALS["database"]->commitTransaction();

updateDomains($user["customerID"]);

die("good $ip");

function loginFailed()
{
	header('WWW-Authenticate: Basic realm="DynDns"');
	header('HTTP/1.0 401 Unauthorized');
	die("badauth");
}

function invalidRequest($error)
{
	header('HTTP/1.0 400 Bad Request');
	die($error);
}

function validIPv4($ip)
{
	if(count(explode(".", $ip)) != 4) {
		return false;
	}
	foreach(explode(".", $ip) as $part) {
		if(!ctype_digit($part)) {
			return false;
		}
		if($part < 0 || $part > 255) {
			return false;
		}
	}
	return true;
}

function updateHosts($hosts, $command)
{
	foreach($hosts as $hostID) {
		$host = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("ipv4Address", "sshPort"));
		`/usr/bin/ssh -i {$GLOBALS["ssh_private_key_file"]} -l root -p {$host["sshPort"]} {$host["ipv4Address"]} '$command' > /dev/null &`;
	}
}

function updateDomains($customerID)
{
	$nameSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "nameSystemID");
	$GLOBALS["database"]->stdIncrement("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), "version", 1000000000);
	
	$hosts = $GLOBALS["database"]->stdList("infrastructureNameServer", array("nameSystemID"=>$nameSystemID), "hostID");
	updateHosts($hosts, "update-treva-bind");
}

function domainID($domainname)
{
	try {
		$parts = explode(".", $domainname);
		$tldID = $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("name"=>$parts[count($parts) - 1]), "domainTldID");
		$domainID = $GLOBALS["database"]->stdGet("dnsDomain", array("name"=>$parts[count($parts) - 2], "domainTldID"=>$tldID), "domainID");
		
		for($i = count($parts) - 3; $i >= 0; $i--) {
			$domainID = $GLOBALS["database"]->stdGet("dnsDomain", array("name"=>$parts[$i], "parentDomainID"=>$domainID), "domainID");
		}
		return $domainID;
	} catch(NotFoundException $e) {
		return false;
	}
}

?>