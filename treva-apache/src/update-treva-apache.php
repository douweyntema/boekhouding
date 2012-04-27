#!/usr/bin/php
<?php

/*
 * Notes:
 * - Redirect gaat voor alias, dus het is niet mogelijk om iets anders onder een redirect te plaatsen (behalve een andere redirect)
 * - Een redirect neemt nu zijn postfix mee naar de nieuwe url, willen we dit?
 */

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
require_once("/etc/treva-infrastructure/apache.conf");

$lockfile = "/var/lock/update-treva-apache.lock";
$lock = fopen($lockfile, "w");
flock($lock, LOCK_EX) OR die("Couldn't acquire global update-treva-apache lock");

$GLOBALS["database"] = new MysqlConnection();
$GLOBALS["database"]->open($mysqlServer, $mysqlUsername, $mysqlPassword, $mysqlDatabase);


$date = date("r", time());

$tmpDir = "/tmp/update-treva-apache-" . uniqid();
deepCopy($httpTargetDirectory, $tmpDir);
globalReplace($httpTargetDirectory, $tmpDir . "/", $tmpDir);

$GLOBALS["database"]->startTransaction(true);

$hostID = $GLOBALS["database"]->stdGetTry("infrastructureHost", array("hostname"=>$hostname), "hostID");
if($hostID === null) {
	echo "Host not in database\n";
	exit(1);
}

$fileSystems = $database->stdList("infrastructureWebServer", array("hostID"=>$hostID), array("fileSystemID", "version"));

$updateNeeded = false;
foreach($fileSystems as $fileSystem) {
	$id = $fileSystem["fileSystemID"];
	$version = $fileSystem["version"];
	
	$databaseVersion = $database->stdGet("infrastructureFileSystem", array("fileSystemID"=>$id), "httpVersion");
	
	if($version != $databaseVersion) {
		$updateNeeded = true;
		$database->stdSet("infrastructureWebServer", array("hostID"=>$hostID, "fileSystemID"=>$id), array("version"=>$databaseVersion));
	}
}

if(!$updateNeeded && !$force) {
	exit(0);
}

$hostIDSql = $GLOBALS["database"]->addSlashes($hostID);
$customerList = $GLOBALS["database"]->query("SELECT customerID from adminCustomer INNER JOIN infrastructureWebServer USING(fileSystemID) WHERE hostID='$hostIDSql'")->fetchList();
$customers = array();
foreach($customerList as $customer) {
	$customers[$customer["customerID"]] = $customer["customerID"];
}




$fatalError = false;
$brokenSites = array();
foreach(array("testRun"=>$tmpDir, "liveRun"=>$httpTargetDirectory) as $runType=>$p) {
	if(substr($p, -1) != "/") {
		$p .= "/";
	}
	if(is_dir($p . "sites-scripted")) {
		deepRemove($p . "sites-scripted", true);
	}
	if(!is_dir($p . "sites-scripted")) {
		mkdir($p . "sites-scripted");
	}
	//
	// Test empty config
	//
	if($runType == "testRun") {
		$check = testConfig($p);
		if($check !== true) {
			$message = <<<MESSAGE
FATAL ERROR: The apache configuration file is broken!

The apache configuration is no longer updated!

Error message:
$check

MESSAGE;
			mail($adminMail, "[FATAL ERROR][Apache] Broken configuration file", $message);
			echo $message;
			$fatalError = true;
			break;
		}
	}
	
	//
	// Open ports in apache (currently only port 80 is supported)
	//
	$ports = array(80);
	
	$file = <<<HEADER
#
# THIS FILE IS AUTO-GENERATED. MANUAL EDITS WILL BE OVERWRITTEN!
# generated: $date
#


HEADER;
	
	foreach($ports as $port) {
		$file .= "Listen {$port}\n";
		$file .= "NameVirtualHost *:{$port}\n";
	}
	
	file_put_contents($p . "ports.conf", $file);
	
	//
	// test ports file
	//
	if($runType == "testRun") {
		$check = testConfig($p);
		if($check !== true) {
			$message = <<<MESSAGE
FATAL ERROR: The apache ports.conf file is broken!

The apache configuration is no longer updated!

Error message:
$check

MESSAGE;
			mail($adminMail, "[FATAL ERROR][Apache] Broken ports file", $message);
			echo $message;
			$fatalError = true;
			break;
		}
	}
	
	//
	// Generate virtualhosts
	//
	$rootDomainIDs = getRootDomains($hostID);
	
	foreach($rootDomainIDs as $rootDomainID) {
		$domainName = domainName($rootDomainID);
		$customerID = $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$rootDomainID), "customerID");
		$customerName = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "name");
		
		$header = <<<HEADER
#
# THIS FILE IS AUTO-GENERATED. MANUAL EDITS WILL BE OVERWRITTEN!
# generated: $date
#

#
# Apache config file for
#
# Domain: $domainName
# DomainID: $rootDomainID
# Customer: $customerName
#
# and it's subdomains.
#

HEADER;
		$config = "";
		try {
			$config = getDomainConfig($rootDomainID);
		} catch(NotFoundException $e) {
			$brokenSites[$rootDomainID] = true;
			$message = <<<MESSAGE
ERROR: Broken site detected. This site is disabled.
Domain: $domainName
RootDomainID: $rootDomainID
Customer: $customerName

Error message:
$e

Config file:
$config

MESSAGE;
			mail($adminMail, "[ERROR][Apache] Broken virtualhost: $domainName", $message);
			echo $message;
		}
		
		if(isset($brokenSites[$rootDomainID])) {
			$header .= <<<BROKEN
#
# The configuration of this domain is broken, so it is disabled.
#


BROKEN;
			file_put_contents($p . "sites-scripted/" . $domainName, $header . "#" . str_replace("\n", "\n#", $config). "\n");
			continue;
		}
		
		file_put_contents($p . "sites-scripted/" . $domainName, $header . $config);
		
		//
		// Test this domain's configuration
		//
		if($runType == "testRun") {
			$check = testConfig($p);
			if($check !== true) {
				$brokenSites[$rootDomainID] = true;
				$message = <<<MESSAGE
ERROR: Broken site detected. This site is disabled.
Domain: $domainName
RootDomainID: $rootDomainID
Customer: $customerName

Error message:
$check

Config file:
$config

MESSAGE;
				mail($adminMail, "[ERROR][Apache] Broken virtualhost: $domainName", $message);
				echo $message;
				
				file_put_contents($p . "sites-scripted/" . $domainName, $header . "#" . str_replace("\n", "\n#", $config). "\n");
			}
		}
	}
}

if($fatalError) {
	$GLOBALS["database"]->rollbackTransaction();
} else {
	$GLOBALS["database"]->commitTransaction();
}

deepRemove($tmpDir);

fclose($lock);
unlink($lockfile);

if(!$fatalError) {
	echo shell_exec("/etc/init.d/apache2 reload");
}


function getRootDomains()
{
	$domainList = $GLOBALS["database"]->stdList("httpDomain", array(), array("domainID", "parentDomainID", "customerID"));
	$allDomains = array();
	foreach($domainList as $domain) {
		$allDomains[$domain["domainID"]] = $domain;
	}
	$domains = array();
	foreach($allDomains as $domainID => $domain) {
		if(!isset($GLOBALS["customers"][$domain["customerID"]])) {
			continue;
		}
		$current = $domain["parentDomainID"];
		$found = false;
		while($current !== null) {
			if(isset($GLOBALS["customers"][$allDomains[$current]["customerID"]])) {
				$found = true;
				break;
			}
			$current = $allDomains[$current]["parentDomainID"];
		}
		if($found) {
			continue;
		}
		$domains[] = $domainID;
	}
	return $domains;
}

function getSubDomains($parentID)
{
	return $GLOBALS["database"]->stdList("httpDomain", array("parentDomainID"=>$parentID), "domainID");
}

function getDomainConfig($domainID)
{
	$output = "";
	
	$domain = domainName($domainID);
	
	$subDomainIDs = getSubDomains($domainID);
	foreach($subDomainIDs as $subDomainID) {
		$output .= getDomainConfig($subDomainID);
		$output .= "\n\n";
	}
	
	//
	// If this subdomain should not be hosted on this server, treat it like a trivial subdomain (one without a Path config).
	//
	if(!isset($GLOBALS["customers"][$GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$domainID), "customerID")])) {
		return $output;
	}
	
	$rootPathID = $GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), "pathID");
	if($rootPathID === null) {
		return $output;
	}
	
	$customConfigText = $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$domainID), "customConfigText");
	if($customConfigText !== null) {
		$customConfigText = indent("### Custom configuration:\n\n" . $customConfigText . "\n\n### End custom configuration\n");
	} else {
		$customConfigText = "";
	}
	
	$locations = indent(getPathConfig($rootPathID, "", array()));
	
	$log = getLog($domainID);
	
	// write config file
	$output .= <<<CONFIG
# domainID: $domainID
<VirtualHost *:80>
	ServerName $domain
	ServerAlias *.$domain

CONFIG;
$output .= $locations;
$output .= $log;
$output .= $customConfigText;
$output .= "</VirtualHost>\n";
	
	return $output;
}

function getPathConfig($pathID, $location, $ancestors)
{
	$path = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), array("domainID", "name", "type", "hostedUserID", "hostedPath", "hostedIndexes", "svnPath", "redirectTarget", "mirrorTargetPathID", "userDatabaseID", "userDatabaseRealm", "customLocationConfigText", "customDirectoryConfigText"));
	
	$childAncestors = $ancestors;
	$childAncestors[$pathID] = $location;
	
	$output = "";
	foreach($GLOBALS["database"]->stdList("httpPath", array("parentPathID"=>$pathID), array("pathID", "name")) as $subPath) {
		$output .= getPathConfig($subPath["pathID"], $location . "/" . $subPath["name"], $childAncestors);
		$output .= "\n\n";
	}
	
	$effectiveLocation = ($location == "" ? "/" : $location);
	
	$output .= "# pathID: $pathID\n";
	
	$locationBlocks = null;
	$directoryBlocks = null;
	$directoryPath = null;
	
	if($path["type"] == "HOSTED") {
		$username = $GLOBALS["database"]->stdGet("adminUser", array("userID"=>$path["hostedUserID"]), "username");
		$customerID = $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$path["domainID"]), "customerID");
		$groupname = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "groupname");
		$directoryBase = "/home/" . $username . "/www/";
		$directoryPath = $directoryBase . $path["hostedPath"];
		if(!is_dir($directoryBase)) {
			mkdir($directoryBase, 0755);
			chown($directoryBase, $username);
			chgrp($directoryBase, $groupname);
		}
		if(!is_dir($directoryPath)) {
			mkdir($directoryPath, 0755);
			chown($directoryPath, $username);
			chgrp($directoryPath, $groupname);
		}
		$locationBlocks = array();
		$directoryBlocks = array();
		if($path["hostedIndexes"] == 1) {
			$directoryBlocks[] = "Options SymLinksIfOwnerMatch Indexes";
		} else {
			$directoryBlocks[] = "Options SymLinksIfOwnerMatch";
		}
		$directoryBlocks[] = "AllowOverride AuthConfig FileInfo Indexes Limit Options=Indexes,IncludesNOEXEC,SymLinksIfOwnerMatch,FollowSymLinks,MultiViews";
		if($location == "") {
			$output .= "DocumentRoot $directoryPath\n";
		} else {
			$output .= "Alias $effectiveLocation $directoryPath\n";
		}
	} else if($path["type"] == "REDIRECT") {
		$output .= "RewriteEngine On\n";
		$output .= 'RewriteRule ^' . str_replace('.', '\\.', $location) . "(/|\$)(.*) {$path["redirectTarget"]} [R,L]\n";
	} else if($path["type"] == "MIRROR") {
		if(isset($ancestors[$path["mirrorTargetPathID"]])) {
			if($ancestors[$path["mirrorTargetPathID"]] == $location) {
				$output .= "### Alias loop, ignoring path.\n";
				$output .= 'RewriteRule ^' . str_replace('.', '\\.', $location) . "(/|\$)(.*) - [R=500,L]\n";
			} else {
				$output .= "RewriteEngine On\n";
				$output .= 'RewriteRule ^' . str_replace('.', '\\.', $location) . "(/|\$)(.*) {$ancestors[$path["mirrorTargetPathID"]]}/\$2 [N]\n";
			}
		} else {
			$output .= "# $effectiveLocation is a mirror of " . pathName($path["mirrorTargetPathID"]) . ":\n";
			$output .= getPathConfig($path["mirrorTargetPathID"], $location, $childAncestors);
		}
	} else if($path["type"] == "SVN") {
		$directoryPath = $path["svnPath"];
		if(!is_dir($directoryPath)) {
			return $output . "### WARNING: SVN directory does not exist; Ignoring site.\n";
		}
		$locationBlocks = array(<<<SVN
DAV svn
SVNParentPath $directorypath

SVN
		);
		$directoryBlocks = array();
	} else if($path["type"] == "AUTH") {
		$locationBlocks = array();
		$output .= "# TODO\n";
	} else if($path["type"] == "NONE") {
		$output .= "# nothing here\n";
	}
	
	if($locationBlocks !== null) {
		$output .= "<Location $effectiveLocation>\n";
		if($path["customLocationConfigText"] !== null) {
			$output .= indent("### Custom configuration:\n\n" . $path["customLocationConfigText"] . "\n\n### End custom configuration\n");
		}
		foreach($locationBlocks as $locationBlock) {
			$output .= indent($locationBlock) . "\n";
		}
		$output .= "</Location>\n";
	}
	
	if($directoryBlocks !== null) {
		if($directoryPath === null) {
			die("Assertion failure: \$directoryPath is not set!\n");
		}
		$output .= "<Directory $directoryPath>\n";
		if($path["customDirectoryConfigText"] !== null) {
			$output .= indent("### Custom configuration:\n\n" . $path["customDirectoryConfigText"] . "\n\n### End custom configuration\n");
		}
		foreach($directoryBlocks as $directoryBlock) {
			$output .= indent($directoryBlock) . "\n";
		}
		$output .= "</Directory>\n";
	}
	
	return $output;
}

function location($pathID)
{
	$path = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), array("parentPathID", "name"));
	if($path["parentPathID"] === null) {
		if($path["name"] === null) {
			return "/";
		} else {
			return "/" . $path["name"];
		}
	}
	$parent = location($path["parentPathID"]);
	if($parent == "/") {
		$parent = "";
	}
	return $parent . "/" . $path["name"];
}

function indent($string)
{
	$indented = "\t" . str_replace("\n", "\n\t", $string);
	if(substr($indented, -1) == "\t") {
		$indented = substr($indented, 0, -1);
	}
	return $indented;
}

function getLog($domainID)
{
	global $httpLogDirectory;
	global $httpLogFormat;
	global $httpLogLevel;
	global $httpLogPipe;
	global $httpLogPipeFormat;
	
	$customerID = $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$domainID), "customerID");
	$username = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "name");
	$group = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "groupname");
	$domain = domainName($domainID);
	
	$logDirectory = "{$httpLogDirectory}{$group}/{$domain}/";
	if(!is_dir($httpLogDirectory)) {
		mkdir($httpLogDirectory, 0755, true);
		chown($httpLogDirectory, "www-data");
		chgrp($httpLogDirectory, "www-data");
	}
	if(!is_dir($httpLogDirectory . $group)) {
		mkdir($httpLogDirectory . $group, 0750);
		chown($httpLogDirectory . $group, "www-data");
		chgrp($httpLogDirectory . $group, $group);
	}
	if(!is_dir($logDirectory)) {
		mkdir($logDirectory, 0750);
		chown($logDirectory, "www-data");
		chgrp($logDirectory, $group);
	}
	$logFormat = "\"" . str_replace("\"", "\\\"", $httpLogFormat) . "\"";
	$logLevel = $httpLogLevel;
	if(isset($httpLogPipe) && $httpLogPipe != "") {
		$logPipe = "\tCustomLog \"|$httpLogPipe\" \"" . str_replace("\"", "\\\"", $httpLogPipeFormat) . "\"\n";
	} else {
		$logPipe = "";
	}
	$log = <<<LOG
	LogLevel $logLevel
	ErrorLog {$logDirectory}error.log
	CustomLog {$logDirectory}access.log $logFormat

LOG;
	$log .= $logPipe;
	return $log;
}

function domainName($domainID)
{
	$domain = $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$domainID), array("parentDomainID", "name", "domainTldID"));
	if($domain["parentDomainID"] === null) {
		$tld = $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$domain["domainTldID"]), "name");
		return $domain["name"] . "." . $tld;
	}
	return $domain["name"] . "." . domainName($domain["parentDomainID"]);
}

function pathName($pathID)
{
	$path = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), array("name", "parentPathID", "domainID"));
	if($path["parentPathID"] == null) {
		return domainName($path["domainID"]);
	} else {
		return pathName($path["parentPathID"]) . "/" . $path["name"];
	}
}

function testConfig($dir)
{
	//RETURN true or error message
	$cmd = ". {$dir}envvars; apache2 -f \"{$dir}apache2.conf\" -t 2>&1";
	$result = shell_exec($cmd);
	if(strpos($result, "Syntax OK") !== false) {
		return true;
	} else {
		return $result;
	}
}

function globalReplace($find, $replace, $dir)
{
	if(is_dir($dir)) {
		$handle = opendir($dir);
		while($file = readdir($handle)) {
			if($file == "." || $file == "..") {
				continue;
			}
			globalReplace($find, $replace, $dir . "/" . $file);
		}
		closedir($handle);
	} else {
		$contents = file_get_contents($dir);
		$contents = str_replace($find, $replace, $contents);
		file_put_contents($dir, $contents);
		return true;
	}
}

function deepCopy($source, $target)
{
	$pos = strrpos($source, "/");
	if($pos === false) {
		$name = $source;
	} else {
		$name = substr($source, $pos + 1);
	}
	if(is_dir($source)) {
		mkdir($target . "/" . $name) or error("Error: could not create dir `$target/$name`");
		chmod($target . "/" . $name, fileperms($source)) or error("Error: could not set permissions of file `$target/$name`");
		$dir = opendir($source) or error("Error: could not open dir `$source`");
		while($file = readdir($dir)) {
			if($file == "." || $file == "..") {
				continue;
			}
			deepCopy($source . "/" . $file, $target . "/" . $name);
		}
		closedir($dir);
	} else {
		if(is_link($source)) {
			symlink(readlink($source), $target . "/" . $name) or error("Error: could not create symlink to file `$source`");
		} else {
			copy($source, $target . "/" . $name) or error("Error: could not create link to file `$source`");
			chmod($target . "/" . $name, fileperms($source)) or error("Error: could not set permissions of file `$target/$name`");
		}
	}
}

function deepRemove($target, $noSymlinks = false)
{
	if($noSymlinks && is_link($target)) {
		return false;
	}
	if(is_dir($target)) {
		$dir = opendir($target);
		$success = true;
		while($file = readdir($dir)) {
			if($file == "." || $file == "..") {
				continue;
			}
			$success &= deepRemove($target . "/" . $file, $noSymlinks);
		}
		closedir($dir);
		if($success) {
			rmdir($target);
			return true;
		} else {
			return false;
		}
	} else {
		unlink($target);
		return true;
	}
}

function error($message)
{
	echo $message . "\n";
}

?>