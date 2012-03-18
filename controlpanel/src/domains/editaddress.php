<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomain($domainID);
	
	$content = "<h1>Domain " . domainsFormatDomainName($domainID) . "</h1>\n";
	
	$content .= domainBreadcrumbs($domainID, array(array("name"=>"Edit address", "url"=>"{$GLOBALS["root"]}domains/editaddress.php?id=$domainID")));
	
	$type = addressTypeFromTitle(post("type"));
	
	$ipv4 = post("ipv4");
	$ipv6 = post("ipv6");
	$cname = post("cname");
	
	if($type == "DELEGATION") {
		$delegationServers = array();
		foreach($_POST as $key=>$value) {
			if(strlen(trim($value)) == 0) {
				continue;
			}
			if(substr($key, 0, strlen("delegationHostname-")) == "delegationHostname-") {
				$number = substr($key, strlen("delegationHostname-"));
				if(!isset($delegationServers[$number])) {
					$delegationServers[$number] = array("hostname"=>null, "ipv4Address"=>null, "ipv6Address"=>null);
				}
				$delegationServers[$number]["hostname"] = $value;
			}
			if(substr($key, 0, strlen("delegationIpv4-")) == "delegationIpv4-") {
				$number = substr($key, strlen("delegationIpv4-"));
				if(!isset($delegationServers[$number])) {
					$delegationServers[$number] = array("hostname"=>null, "ipv4Address"=>null, "ipv6Address"=>null);
				}
				$delegationServers[$number]["ipv4Address"] = $value;
			}
			if(substr($key, 0, strlen("delegationIpv6-")) == "delegationIpv6-") {
				$number = substr($key, strlen("delegationIpv6-"));
				if(!isset($delegationServers[$number])) {
					$delegationServers[$number] = array("hostname"=>null, "ipv4Address"=>null, "ipv6Address"=>null);
				}
				$delegationServers[$number]["ipv6Address"] = $value;
			}
		}
		ksort($delegationServers);
	} else {
		$delegationServers = null;
	}
	
	if($type === null) {
		$content .= editAddressTypeForm($domainID, "");
		die(page($content));
	}
	
	if($type == "INHERIT") {
		if(post("confirm") === null) {
			$content .= editAddressTypeForm($domainID, null, $type, $ipv4, $ipv6, $cname, $delegationServers);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), array("addressType"=>"INHERIT", "cnameTarget"=>null));
		$GLOBALS["database"]->stdDel("dnsDelegatedNameServer", array("domainID"=>$domainID));
		$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID, "type"=>"A"));
		$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA"));
		$GLOBALS["database"]->commitTransaction();
	} else if($type == "TREVA-WEB") {
		if(post("confirm") === null) {
			$content .= editAddressTypeForm($domainID, null, $type, $ipv4, $ipv6, $cname, $delegationServers);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), array("addressType"=>"TREVA-WEB", "cnameTarget"=>null));
		$GLOBALS["database"]->stdDel("dnsDelegatedNameServer", array("domainID"=>$domainID));
		$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID, "type"=>"A"));
		$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA"));
		$GLOBALS["database"]->commitTransaction();
	} else if($type == "IP") {
		if($ipv4 == "" && $ipv6 == "") {
			$content .= editAddressTypeForm($domainID, "Enter at least an IPv4 or IPv6 address", $type, $ipv4, $ipv6, $cname, $delegationServers);
			die(page($content));
		}
		if($ipv4 != "" && !validIPv4($ipv4)) {
			$content .= editAddressTypeForm($domainID, "Invalid IPv4 address", $type, $ipv4, $ipv6, $cname, $delegationServers);
			die(page($content));
		}
		
		if($ipv6 != "" && !validIPv6($ipv6)) {
			$content .= editAddressTypeForm($domainID, "Invalid IPv6 address", $type, $ipv4, $ipv6, $cname, $delegationServers);
			die(page($content));
		}
		
		if(post("confirm") === null) {
			$content .= editAddressTypeForm($domainID, null, $type, $ipv4, $ipv6, $cname, $delegationServers);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), array("addressType"=>"IP", "cnameTarget"=>null));
		$GLOBALS["database"]->stdDel("dnsDelegatedNameServer", array("domainID"=>$domainID));
		$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID, "type"=>"A"));
		$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA"));
		foreach(explode(" ", $ipv4) as $ip) {
			if(trim($ip) == "") {
				continue;
			}
			$GLOBALS["database"]->stdNew("dnsRecord", array("domainID"=>$domainID, "type"=>"A", "value"=>trim($ip)));
		}
		foreach(explode(" ", $ipv6) as $ip) {
			if(trim($ip) == "") {
				continue;
			}
			$GLOBALS["database"]->stdNew("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA", "value"=>trim($ip)));
		}
		$GLOBALS["database"]->commitTransaction();
	} else if($type == "CNAME") {
		if(substr($cname, -1) == ".") {
			$cname = substr($cname, 0, -1);
		} else if(strpos($cname, ".") === false) {
			if(isSubDomain($domainID)) {
				$cname .= "." . domainsFormatDomainName($GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "parentDomainID"));
			} else {
				$cname .= "." . domainsFormatDomainName($domainID);
			}
		}
		
		if(!validDomain($cname)) {
			$content .= editAddressTypeForm($domainID, "Invalid cname target", $type, $ipv4, $ipv6, $cname, $delegationServers);
			die(page($content));
		}
		
		$warning = "";
		if($GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "mailType") != "NONE") {
			$warning .= "<p class=\"confirmdelete\">This will disable email for this domain</p>";
		}
		foreach($GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID), array("type", "value")) as $record) {
			if($record["type"] == "A" || $record["type"] == "AAAA") {
				continue;
			}
			$warning .= "<p class=\"confirmdelete\">This record will be deleted: {$record["type"]}: {$record["value"]}</p>";
		}
		
		if(post("confirm") === null) {
			$content .= editAddressTypeForm($domainID, null, $type, $ipv4, $ipv6, $cname, $delegationServers, $warning);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), array("addressType"=>"CNAME", "cnameTarget"=>$cname, "mailType"=>"NONE"));
		$GLOBALS["database"]->stdDel("dnsDelegatedNameServer", array("domainID"=>$domainID));
		$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID));
		$GLOBALS["database"]->commitTransaction();
	} else if($type == "DELEGATION") {
		$error = "";
		foreach($delegationServers as $server) {
			if(!validDomain($server["hostname"])) {
				$error .= "Invalid hostname: {$server["hostname"]}\n";
			}
			if(!validIPv4($server["ipv4Address"])) {
				$error .= "Invalid ipv4 address: {$server["ipv4Address"]}\n";
			}
			if($server["ipv6Address"] != "" && !validIPv6($server["ipv6Address"])) {
				$error .= "Invalid ipv6 address: {$server["ipv6Address"]}\n";
			}
		}
		if($error != "") {
			$content .= editAddressTypeForm($domainID, $error, $type, $ipv4, $ipv6, $cname, $delegationServers);
			die(page($content));
		}
		
		$warning = "";
		if($GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "mailType") != "NONE") {
			$warning .= "<p class=\"confirmdelete\">This will disable email for this domain</p>";
		}
		foreach($GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID), array("type", "value")) as $record) {
			if($record["type"] == "A" || $record["type"] == "AAAA") {
				continue;
			}
			$warning .= "<p class=\"confirmdelete\">This record will be deleted: {$record["type"]}: {$record["value"]}</p>";
		}
		foreach(subdomains($domainID) as $subDomainID) {
			$warning .= "<p class=\"confirmdelete\">This domain will be deleted: " . domainsFormatDomainName($subDomainID) . "</p>";
		}
		
		if(post("confirm") === null) {
			$content .= editAddressTypeForm($domainID, null, $type, $ipv4, $ipv6, $cname, $delegationServers, $warning);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), array("addressType"=>"DELEGATION", "cnameTarget"=>null, "mailType"=>"NONE"));
		$GLOBALS["database"]->stdDel("dnsDelegatedNameServer", array("domainID"=>$domainID));
		$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID));
		foreach($GLOBALS["database"]->stdList("dnsDomain", array("parentDomainID"=>$domainID), "domainID") as $subDomainID) {
			removeDomain($subDomainID);
		}
		foreach($delegationServers as $server) {
			$GLOBALS["database"]->stdNew("dnsDelegatedNameServer", array_merge(array("domainID"=>$domainID), $server));
		}
		$GLOBALS["database"]->commitTransaction();
	} else {
		die("Internal error");
	}
	
	updateDomains(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id=$domainID");
}

main();

?>