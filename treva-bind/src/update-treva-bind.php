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

$lockfile = "/var/lock/update-treva-bind.lock";
$lock = fopen($lockfile, "w");
flock($lock, LOCK_EX) OR die("Couldn't acquire global update-treva-bind lock");

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

$nameSystems = $database->stdList("infrastructureNameServer", array("hostID"=>$hostID), array("nameSystemID", "version"));

$updateNeeded = false;
foreach($nameSystems as $nameSystem) {
	$id = $nameSystem["nameSystemID"];
	$version = $nameSystem["version"];
	
	$databaseVersion = $database->stdGet("infrastructureNameSystem", array("nameSystemID"=>$id), "version");
	
	if($version != $databaseVersion) {
		$updateNeeded = true;
		$database->stdSet("infrastructureNameServer", array("hostID"=>$hostID, "nameSystemID"=>$id), array("version"=>$databaseVersion));
	}
}

if(!$updateNeeded && !$force) {
	exit(0);
}

$hostIDSql = $GLOBALS["database"]->addSlashes($hostID);

$zones = $GLOBALS["database"]->query("SELECT domainID FROM dnsDomain INNER JOIN adminCustomer USING(customerID) INNER JOIN infrastructureNameServer USING(nameSystemID) WHERE hostID='$hostIDSql' AND parentDomainID IS NULL ORDER BY dnsDomain.name")->fetchList("domainID");

$domainsList = <<<DOMAINSLIST
//
// THIS FILE IS AUTO-GENERATED. MANUAL EDITS WILL BE OVERWRITTEN!
// generated: {$GLOBALS["date"]}
//


DOMAINSLIST;

foreach($zones as $domainID) {
	$name = getFullDomainName($domainID);
	$zone = buildZoneFile($domainID);
	
	file_put_contents("/etc/bind/db.$name", $zone);
	
	$domainsList .= "zone \"$name\" { type master; file \"/etc/bind/db.$name\"; };\n";
}

file_put_contents("/etc/bind/named.conf.local", $domainsList);

$GLOBALS["database"]->commitTransaction();

echo shell_exec("/usr/sbin/rndc reload");

fclose($lock);
unlink($lockfile);



function buildZoneFile($domainID)
{
	$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("customerID", "ttl"));
	$customer = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$domain["customerID"]), array("nameSystemID", "name"));
	
	$name = getFullDomainName($domainID);
	
	if($domain["ttl"] !== null) {
		$ttl = $domain["ttl"];
	} else {
		$ttl = $GLOBALS["database"]->stdGet("infrastructureNameSystem", array("nameSystemID"=>$customer["nameSystemID"]), "ttl");
	}
	
	$hostname = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$GLOBALS["hostID"]), "hostname");
	
	$zone = <<<ZONE
;
; THIS FILE IS AUTO-GENERATED. MANUAL EDITS WILL BE OVERWRITTEN!
; generated: {$GLOBALS["date"]}
;

;
; Zone file for $name of customer {$customer["name"]}.
;

\$TTL $ttl
$name. IN SOA $hostname. hostmaster.$name. ( {$GLOBALS["time"]} 8H 1H 8W $ttl )

ZONE;

	
	foreach($GLOBALS["database"]->stdList("infrastructureNameServer", array("nameSystemID"=>$customer["nameSystemID"]), "hostID") as $hostID) {
		$nameServer = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("hostname", "ipv4Address", "ipv6Address"));
		$zone .= "$name. IN NS {$nameServer["hostname"]}.\n";
		if($nameServer["ipv4Address"] !== null) {
			$zone .= "{$nameServer["hostname"]}. IN A {$nameServer["ipv4Address"]}\n";
		}
		if($nameServer["ipv6Address"] !== null) {
			$zone .= "{$nameServer["hostname"]}. IN AAAA {$nameServer["ipv6Address"]}\n";
		}
	}
	
	$records = buildRecords($domainID, $name);
	$zone .= implode("\n", array_unique(explode("\n", $records)));
	
	return $zone;
}

function buildRecords($domainID, $name)
{
	$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("customerID", "addressType", "mailType"));
	
	$zone = buildPrimaryRecords($domainID, $name);
	
	if($domain["mailType"] == "TREVA") {
		$mailSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$domain["customerID"]), "mailSystemID");
		foreach($GLOBALS["database"]->stdList("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), array("hostID", "primary"), array("primary"=>"DESC", "hostID"=>"ASC")) as $host) {
			$hostname = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$host["hostID"]), "hostname");
			$priority = $host["primary"] ? 10 : 20;
			$zone .= "$name. IN MX $priority $hostname.\n";
		}
	} else if($domain["mailType"] == "CUSTOM") {
		foreach($GLOBALS["database"]->stdList("dnsMailServer", array("domainID"=>$domainID), array("name", "priority"), array("priority"=>"ASC", "mailServerID"=>"ASC")) as $mailServer) {
			$zone .= "$name. IN MX {$mailServer["priority"]} {$mailServer["name"]}.\n";
		}
	}
	
	foreach($GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID), array("type", "value"), array("recordID"=>"ASC")) as $record) {
		if($domain["addressType"] == "IP" && ($record["type"] == "A" || $record["type"] == "AAAA")) {
			continue;
		}
		$zone .= "$name. IN {$record["type"]} {$record["value"]}\n";
	}
	
	foreach($GLOBALS["database"]->stdList("dnsDomain", array("parentDomainID"=>$domainID), array("domainID", "name"), array("name"=>"ASC")) as $subDomain) {
		$zone .= buildRecords($subDomain["domainID"], $subDomain["name"] . "." . $name);
	}
	
	return $zone;
}

function buildPrimaryRecords($domainID, $name)
{
	$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("customerID", "parentDomainID", "addressType", "cnameTarget", "trevaDelegationNameSystemID", "subdomainsIncluded", "mailType"));

	if($domain["addressType"] == "INHERIT") {
		if($domain["parentDomainID"] === null) {
			return "";
		} else {
			return buildPrimaryRecords($domain["parentDomainID"], $name);
		}
	} else if($domain["addressType"] == "TREVA-WEB") {
		$fileSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$domain["customerID"]), "fileSystemID");
		$zone = "";
		foreach($GLOBALS["database"]->stdList("infrastructureWebServer", array("fileSystemID"=>$fileSystemID), "hostID") as $hostID) {
			$host = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("ipv4Address", "ipv6Address"));
			$zone .= "$name. IN A {$host["ipv4Address"]}\n";
			if($host["ipv6Address"] !== null) {
				$zone .= "$name. IN AAAA {$host["ipv6Address"]}\n";
			}
		}
		if($domain["subdomainsIncluded"]) {
			$zone .= "*.$name. IN CNAME $name.\n";
		}
		return $zone;
	} else if($domain["addressType"] == "IP") {
		$zone = "";
		foreach($GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID, "type"=>"A"), "value") as $value) {
			$zone .= "$name. IN A $value\n";
		}
		foreach($GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA"), "value") as $value) {
			$zone .= "$name. IN AAAA $value\n";
		}
		if($domain["subdomainsIncluded"]) {
			$zone .= "*.$name. IN CNAME $name.\n";
		}
		return $zone;
	} else if($domain["addressType"] == "CNAME") {
		$zone = "$name. IN CNAME {$domain["cnameTarget"]}.\n";
		if($domain["subdomainsIncluded"]) {
			$zone .= "*.$name. IN CNAME {$domain["cnameTarget"]}.\n";
		}
		return $zone;
	} else if($domain["addressType"] == "DELEGATION") {
		$zone = "";
		foreach($GLOBALS["database"]->stdList("dnsDelegatedNameServer", array("domainID"=>$domainID), array("hostname", "ipv4Address", "ipv6Address")) as $nameServer) {
			$zone .= "$name. IN NS {$nameServer["hostname"]}.\n";
			if($nameServer["ipv4Address"] !== null) {
				$zone .= "{$nameServer["hostname"]}. IN A {$nameServer["ipv4Address"]}\n";
			}
			if($nameServer["ipv6Address"] !== null) {
				$zone .= "{$nameServer["hostname"]}. IN AAAA {$nameServer["ipv6Address"]}\n";
			}
		}
		return $zone;
	} else if($domain["addressType"] == "TREVA-DELEGATION") {
		$zone = "";
		foreach($GLOBALS["database"]->stdList("infrastructureNameServer", array("nameSystemID"=>$domain["trevaDelegationNameSystemID"]), "hostID") as $hostID) {
			$nameServer = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("hostname", "ipv4Address", "ipv6Address"));
			$zone .= "$name. IN NS {$nameServer["hostname"]}.\n";
			if($nameServer["ipv4Address"] !== null) {
				$zone .= "{$nameServer["hostname"]}. IN A {$nameServer["ipv4Address"]}\n";
			}
			if($nameServer["ipv6Address"] !== null) {
				$zone .= "{$nameServer["hostname"]}. IN AAAA {$nameServer["ipv6Address"]}\n";
			}
		}
		return $zone;
	} else {
		return "";
	}
}

function getFullDomainName($domainID)
{
	$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("name", "domainTldID", "parentDomainID"));
	if($domain["domainTldID"] !== null) {
		$tld = $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$domain["domainTldID"]), "name");
		return $domain["name"] . "." . $tld;
	} else if($domain["parentDomainID"] !== null) {
		return $domain["name"] . "." . getFullDomainName($domain["parentDomainID"]);
	} else {
		return $domain["name"];
	}
}

?>