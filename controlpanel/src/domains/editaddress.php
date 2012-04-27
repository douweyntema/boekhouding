<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomain($domainID);
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(makeHeader("Domain " . domainsFormatDomainName($domainID), domainBreadcrumbs($domainID), crumbs("Edit address", "editaddress.php?id=$domainID")) . editAddressForm($domainID, $error, $_POST)));
	};
	
	$check(($type = searchKey($_POST, "none", "inherit", "trevaweb", "ip", "cname", "delegation")) !== null, "");
	
	$remove = function() use($domainID, $check) {
		$check(post("confirm") !== null, null);
		
		$GLOBALS["database"]->startTransaction();
		$GLOBALS["database"]->stdDel("dnsDelegatedNameServer", array("domainID"=>$domainID));
		$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID, "type"=>"A"));
		$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA"));
	};
	
	if($type == "none") {
		$check(!isSubDomain($domainID), "");
		
		$remove();
		$function = array("addressType"=>"NONE");
	} else if($type == "inherit") {
		$check(isSubDomain($domainID), "");
		
		$remove();
		$function = array("addressType"=>"INHERIT");
	} else if($type == "trevaweb") {
		$remove();
		$function = array("addressType"=>"TREVA-WEB");
	} else if($type == "ip") {
		$ipv4 = post("ipv4");
		$ipv6 = post("ipv6");
		
		$check($ipv4 != "" || $ipv6 != "", "Please enter at least an IPv4 address or an IPv6 address.");
		$check($ipv4 == "" || validIpv4($ipv4), "Invalid IPv4 address.");
		$check($ipv6 == "" || validIpv6($ipv6), "Invalid IPv6 address.");
		
		$remove();
		if($ipv4 != "") {
			$GLOBALS["database"]->stdNew("dnsRecord", array("domainID"=>$domainID, "type"=>"A", "value"=>$ipv4));
		}
		if($ipv6 != "") {
			$GLOBALS["database"]->stdNew("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA", "value"=>$ipv6));
		}
		$function = array("addressType"=>"IP");
	} else if($type == "cname") {
		$check(($target = post("cnameTarget")) !== null, "");
		
		if(substr($target, -1) == ".") {
			$_POST["cnameTarget"] = substr($target, 0, -1);
			$target = post("cnameTarget");
		} else if(strpos($target, ".") === false) {
			if(isSubDomain($domainID)) {
				$_POST["cnameTarget"] .= "." . domainsFormatDomainName($GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "parentDomainID"));
			} else {
				$_POST["cnameTarget"] .= "." . domainsFormatDomainName($domainID);
			}
			$target = post("cnameTarget");
		}
		
		$check(validDomain($target), "Invalid target domain name.");
		
		$remove();
		$function = array("addressType"=>"CNAME", "cnameTarget"=>$target);
	} else if($type == "delegation") {
		$delegations = parseArrayField($_POST, array("hostname", "ipv4Address", "ipv6Address"));
		
		$error = array();
		foreach($delegations as $server) {
			if(!validDomain($server["hostname"])) {
				$error[] = "Invalid hostname: " . htmlentities($server["hostname"]);
			}
			if(!validIPv4($server["ipv4Address"])) {
				$error[] = "Invalid ipv4 address: " . htmlentities($server["ipv4Address"]);
			}
			if(trim($server["ipv6Address"]) != "" && !validIPv6($server["ipv6Address"])) {
				$error[] = "Invalid ipv6 address: " . htmlentities($server["ipv6Address"]);
			}
		}
		if(count($error) > 0) {
			$check(false, implode("<br />", $error));
		}
		
		$remove();
		foreach($delegations as $server) {
			$GLOBALS["database"]->stdNew("dnsDelegatedNameServer", array("domainID"=>$domainID, "hostname"=>trim($server["hostname"]), "ipv4Address"=>trim($server["ipv4Address"]), "ipv6Address"=>trim($server["ipv6Address"]) == "" ? null : trim($server["ipv6Address"])));
		}
		$function = array("addressType"=>"DELEGATION");
	} else {
		die("Internal error");
	}
	
	$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), array_merge(array("cnameTarget"=>null), $function));
	$GLOBALS["database"]->commitTransaction();
	
	updateDomains(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id=$domainID");
}

main();

?>