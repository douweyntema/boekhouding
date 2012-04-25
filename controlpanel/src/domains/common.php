<?php

require_once(dirname(__FILE__) . "/../common.php");
require_once(dirname(__FILE__) . "/../ticket/api.php");

function doDomains()
{
	useComponent("domains");
	$GLOBALS["menuComponent"] = "domains";
}

function doDomain($domainID)
{
	doDomains();
	useCustomer($GLOBALS["database"]->stdGetTry("dnsDomain", array("domainID"=>$domainID), "customerID", false));
	$rootDomainID = rootDomainID($domainID);
	if(domainsDomainStatus($rootDomainID) == "expired") {
		removeDomain($rootDomainID);
		useCustomer(false);
	}
}

function crumb($name, $filename)
{
	return array("name"=>$name, "url"=>"{$GLOBALS["root"]}domains/$filename");
}

function crumbs($name, $filename)
{
	return array(crumb($name, $filename));
}

function domainsBreadcrumbs()
{
	return crumbs("Domains", "");
}

function domainBreadcrumbs($domainID)
{
	$parts = array();
	$nextDomainID = $domainID;
	while(true) {
		$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$nextDomainID), array("name", "parentDomainID", "customerID", "domainTldID"));
		if($domain["parentDomainID"] === null) {
			$parts[] = array("id"=>$nextDomainID, "name"=>$domain["name"], "show"=>true);
			$tld = $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$domain["domainTldID"]), "name");
			$parts[] = array("id"=>0, "name"=>$tld, "show"=>false);
			break;
		} else if($domain["customerID"] != customerID()) {
			$parts[] = array("id"=>$nextDomainID, "name"=>domainsFormatDomainName($nextDomainID), "show"=>false);
			break;
		} else {
			$parts[] = array("id"=>$nextDomainID, "name"=>$domain["name"], "show"=>true);
			$nextDomainID = $domain["parentDomainID"];
		}
	}
	
	$parts = array_reverse($parts);
	$crumbs = domainsBreadcrumbs();
	$domainPostfix = "";
	foreach($parts as $part) {
		if($part["show"]) {
			$crumbs[] = crumb($part["name"] . $domainPostfix, "domain.php?id={$part["id"]}");
		}
		$domainPostfix = "." . $part["name"] . $domainPostfix;
	}
	return $crumbs;
}


function domainsList()
{
	$domainIDs = $GLOBALS["database"]->stdList("dnsDomain", array("customerID"=>customerID(), "parentDomainID"=>null), "domainID");
	
	$domains = array();
	foreach($domainIDs as $domainID) {
		if(domainsDomainStatus($domainID) == "expired") {
			removeDomain($domainID);
			continue;
		}
		$domainName = domainsFormatDomainName($domainID);
		$status = domainsDomainStatusDescription($domainID);
		$domains[$domainName] = array("domainID"=>$domainID, "name"=>$domainName, "status"=>$status);
	}
	ksort($domains);
	
	$rows = array();
	foreach($domains as $domain) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["root"]}domains/domain.php?id={$domain["domainID"]}", "text"=>$domain["name"]),
			$domain["status"]
		);
	}
	return listTable(array("Domain", "Status"), $rows, "sortable list");
}

function subDomainsList($parentDomainID)
{
	$domains = array();
	foreach($GLOBALS["database"]->stdList("dnsDomain", array("customerID"=>customerID(), "parentDomainID"=>$parentDomainID), array("domainID", "parentDomainID", "name")) as $domain) {
		$domainName = domainsFormatDomainName($domain["domainID"]);
		$domains[$domainName] = array("domainID"=>$domain["domainID"], "name"=>$domainName);
	}
	ksort($domains);
	
	$rows = array();
	foreach($domains as $domain) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["root"]}domains/domain.php?id={$domain["domainID"]}", "text"=>$domain["name"])
		);
	}
	return listTable(array("Subdomains"), $rows, "sortable list");
}

function domainDetail($domainID)
{
	$domainName = domainsFormatDomainName($domainID);
	$tldID = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "domainTldID");
	return summaryTable("Domain $domainName", array(
		"Name"=>$domainName,
		"Status"=>domainsDomainStatusDescription($domainID),
		"Price per year"=>array("html"=>formatPrice(billingDomainPrice($tldID)))
	));
}

function addDomainForm($error = "", $values = null)
{
	$tlds = array();
	foreach($GLOBALS["database"]->stdList("infrastructureDomainTld", array("active"=>1), array("domainTldID", "name", "price"), array("order"=>"A")) as $tld) {
		$tlds[] = array("value"=>$tld["domainTldID"], "label"=>$tld["name"] . " (" . formatPrice($tld["price"]) . " / year)");
	}
	
	return operationForm("adddomain.php", $error, "Register new domain", "Register domain",
		array(
			array("title"=>"Domain name", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"name", "fill"=>true),
				array("type"=>"html", "html"=>"."),
				array("type"=>"dropdown", "name"=>"tldID", "options"=>$tlds)
			))
		),
		$values);
}

function addSubdomainForm($domainID, $error = "", $values = null)
{
	$addressType = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "addressType");
	if($addressType == "DELEGATION" || $addressType == "TREVA-DELEGATION") {
		return operationForm(null, "", "Add subdomain", null, array(array("type"=>"html", "html"=>"Adding subdomains is not available for this domain, because the domain address is configured as \"Hosted externally\".")), null);
	}
	
	$parentName = domainsFormatDomainName($domainID);
	return operationForm("addsubdomain.php?id=$domainID", $error, "Add subdomain", "Add",
		array(
			array("title"=>"Subdomain name", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"name", "fill"=>true),
				array("type"=>"html", "html"=>"." . htmlentities($parentName))
			))
		),
		$values);
}

function editAddressForm($domainID, $error = "", $values = null)
{
	$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("addressType", "cnameTarget"));
	$ipv4 = $GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID, "type"=>"A"), "value");
	$ipv6 = $GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA"), "value");
	$delegatedNameServers = $GLOBALS["database"]->stdList("dnsDelegatedNameServer", array("domainID"=>$domainID), array("hostname", "ipv4Address", "ipv6Address"));
	
	if($error == "STUB") {
		$form = array();
		if($domain["addressType"] == "NONE") {
			$form[] = array("title"=>"Status", "type"=>"html", "html"=>"No address settings");
		} else if($domain["addressType"] == "INHERIT") {
			$form[] = array("title"=>"Status", "type"=>"html", "html"=>"Inherit from parent");
		} else if($domain["addressType"] == "TREVA-WEB") {
			$form[] = array("title"=>"Status", "type"=>"html", "html"=>"Use our webservers");
		} else if($domain["addressType"] == "IP") {
			$form[] = array("title"=>"Status", "type"=>"html", "html"=>"Custom IPs");
			$form[] = array("title"=>"IPv4 address", "type"=>"html", "html"=>count($ipv4) == 0 ? "None" : implode(" ", $ipv4));
			$form[] = array("title"=>"IPv6 address", "type"=>"html", "html"=>count($ipv6) == 0 ? "None" : implode(" ", $ipv6));
		} else if($domain["addressType"] == "CNAME") {
			$form[] = array("title"=>"Status", "type"=>"html", "html"=>"CNAME");
			$form[] = array("title"=>"CNAME", "type"=>"html", "html"=>htmlentities($domain["cnameTarget"]));
		} else if($domain["addressType"] == "DELEGATION") {
			$form[] = array("title"=>"Status", "type"=>"html", "html"=>"Delegation");
			$form[] = array("title"=>"Nameservers", "type"=>"colspan", "columns"=>array(
				array("type"=>"html", "html"=>"Hostname", "celltype"=>"th", "fill"=>true),
				array("type"=>"html", "html"=>"IPv4 Address", "celltype"=>"th"),
				array("type"=>"html", "html"=>"IPv6 Address", "celltype"=>"th")
			));
			foreach($delegatedNameServers as $server) {
				$form[] = array("title"=>"", "type"=>"colspan", "columns"=>array(
					array("type"=>"html", "html"=>htmlentities($server["hostname"]), "fill"=>true),
					array("type"=>"html", "html"=>$server["ipv4Address"] === null ? "None" : $server["ipv4Address"]),
					array("type"=>"html", "html"=>$server["ipv6Address"] === null ? "None" : $server["ipv6Address"])
				));
			}
		} else {
			$form[] = array("title"=>"Status", "type"=>"html", "html"=>"Unknown");
		}
		
		return operationForm("editaddress.php?id=$domainID", "STUB", "Edit address configuration for " . domainsFormatDomainName($domainID), "Edit", $form, null);
	}
	
	if($values === null || (!isset($values["none"]) && !isset($values["inherit"]) && !isset($values["trevaweb"]) && !isset($values["ip"]) && !isset($values["cname"]) && !isset($values["delegation"])))
	{
		if($domain["addressType"] == "NONE") {
			$values = array("none"=>"1");
		} else if($domain["addressType"] == "INHERIT") {
			$values = array("inherit"=>"1");
		} else if($domain["addressType"] == "TREVA-WEB") {
			$values = array("trevaweb"=>"1");
		} else if($domain["addressType"] == "IP") {
			$values = array("ip"=>"1", "ipv4"=>implode(" ", $ipv4), "ipv6"=>implode(" ", $ipv6));
		} else if($domain["addressType"] == "CNAME") {
			$values = array("cname"=>"1", "cnameTarget"=>$domain["cnameTarget"]);
		} else if($domain["addressType"] == "DELEGATION") {
			$values = array("delegation"=>"1");
			$index = 0;
			foreach($delegatedNameServers as $server) {
				$values["hostname-$index"] = $server["hostname"];
				$values["ipv4Address-$index"] = $server["ipv4Address"];
				$values["ipv6Address-$index"] = $server["ipv6Address"];
				$index++;
			}
		} else {
			$values = array();
		}
	}
	
	$messages = array();
	if($error === null) {
		$warning = array();
		if(isset($values["cname"]) || isset($values["delegation"])) {
			$warning = array();
			if($GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "mailType") != "NONE") {
				$warning[] = "This will disable email for this domain";
			}
			foreach($GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID), array("type", "value")) as $record) {
				if($record["type"] == "A" || $record["type"] == "AAAA") {
					continue;
				}
				$typeHtml = htmlentities($record["type"]);
				$valueHtml = htmlentities($record["value"]);
				$warning[] = "This record will be deleted: $typeHtml: $valueHtml";
			}
		}
		if(isset($values["delegation"])) {
			foreach(subdomains($domainID) as $subDomainID) {
				$warning[] = "This domain will be deleted: " . domainsFormatDomainName($subDomainID);
			}
		}
		if(count($warning) > 0) {
			$messages["confirmdelete"] = implode("<br />", $warning);
		}
	}
	
	return operationForm("editaddress.php?id=$domainID", $error, "Edit address configuration for " . domainsFormatDomainName($domainID), "Edit",
		array(
			array("type"=>"typechooser", "options"=>array(
				isSubDomain($domainID)
					? array("title"=>"Inherit", "submitcaption"=>"Inherit from parent", "name"=>"inherit", "summary"=>"Inherit from parent domain.", "subform"=>array())
					: array("title"=>"None", "submitcaption"=>"No address settings", "name"=>"none", "summary"=>"Do not configure any address for this domain.", "subform"=>array()),
				array("title"=>"Our web servers", "submitcaption"=>"Use our webservers", "name"=>"trevaweb", "summary"=>"Use our web servers.", "subform"=>array()),
				array("title"=>"Custom IPs", "submitcaption"=>"Use these IPs", "name"=>"ip", "summary"=>"Use these custom IPs.", "subform"=>array(
					array("title"=>"IPv4 address", "type"=>"text", "name"=>"ipv4"),
					array("title"=>"IPv6 address", "type"=>"text", "name"=>"ipv6")
				)),
				array("title"=>"CNAME", "submitcaption"=>"Use cname", "name"=>"cname", "summary"=>"Use this cname", "subform"=>array(
					array("title"=>"CNAME", "type"=>"text", "name"=>"cnameTarget")
				)),
				array("title"=>"Delegation", "submitcaption"=>"Use delegation", "name"=>"delegation", "summary"=>"Delegate to these servers.", "subform"=>array(
					array("title"=>"", "type"=>"colspan", "columns"=>array(
						array("type"=>"html", "html"=>"Hostname", "celltype"=>"th", "fill"=>true),
						array("type"=>"html", "html"=>"IPv4 Address", "celltype"=>"th"),
						array("type"=>"html", "html"=>"IPv6 Address", "celltype"=>"th")
					)),
					array("type"=>"array", "field"=>array("title"=>"Delegation server", "type"=>"colspan", "columns"=>array(
						array("type"=>"text", "name"=>"hostname", "fill"=>true),
						array("type"=>"text", "name"=>"ipv4Address"),
						array("type"=>"text", "name"=>"ipv6Address")
					)))
				))
			))
		),
		$values, $messages);
}

function editMailForm($domainID, $error = "", $values = null)
{
	$addressType = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "addressType");
	if($addressType == "CNAME" || $addressType == "DELEGATION" || $addressType == "TREVA-DELEGATION") {
		$type = $addressType == "CNAME" ? "\"CNAME to another site\"" : "\"Hosted externally\"";
		return operationForm(null, "", "Email configuration", null, array(
			array("type"=>"html", "html"=>"Email is not available for this domain, because the domain address is configured as $type.")
		), null);
	}
	
	$mailType = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "mailType");
	$mailServers = $GLOBALS["database"]->stdList("dnsMailServer", array("domainID"=>$domainID), "name", array("priority"=>"ASC"));
	
	if($error == "STUB") {
		$form = array();
		if($mailType == "NONE") {
			$form[] = array("title"=>"Status", "type"=>"html", "html"=>"No email is configured for this domain.");
		} else if($mailType == "TREVA") {
			$form[] = array("title"=>"Status", "type"=>"html", "html"=>"Hosted by Treva");
		} else if($mailType == "CUSTOM") {
			$form[] = array("title"=>"Status", "type"=>"html", "html"=>"Hosted externally");
			if(count($mailServers) == 0) {
				$form[] = array("title"=>"Mailservers", "type"=>"html", "html"=>"None configured");
			} else {
				$form[] = array("title"=>"Mailservers", "type"=>"html", "html"=>htmlentities(array_shift($mailServers)));
				foreach($mailServers as $server) {
					$form[] = array("title"=>"", "type"=>"html", "html"=>htmlentities($server));
				}
			}
		} else {
			$form[] = array("title"=>"Status", "type"=>"html", "html"=>"Unknown");
		}
		
		return operationForm("editmail.php?id=$domainID", "STUB", "Edit email configuration for " . domainsFormatDomainName($domainID), "Edit", $form, null);
	}
	
	if($values === null || (!isset($values["none"]) && !isset($values["treva"]) && !isset($values["custom"]))) {
		if($mailType == "NONE") {
			$values = array("none"=>"1");
		} else if($mailType == "TREVA") {
			$values = array("treva"=>"1");
		} else if($mailType == "CUSTOM") {
			$values = array("custom"=>"1");
			$index = 0;
			foreach($mailServers as $server) {
				$values["server-$index"] = $server;
				$index++;
			}
		} else {
			$values = array();
		}
	}
	
	return operationForm("editmail.php?id=$domainID", $error, "Edit email configuration for " . domainsFormatDomainName($domainID), "Edit",
		array(
			array("type"=>"typechooser", "options"=>array(
				array("title"=>"Disable email", "submitcaption"=>"Disable email", "name"=>"none", "summary"=>"Disable email for this domain.", "subform"=>array()),
				array("title"=>"Our email servers", "submitcaption"=>"Use our email servers", "name"=>"treva", "summary"=>"Enable email for this domain, using our mailservers. If you are unsure and you want email, choose this option.", "subform"=>array()),
				array("title"=>"Your email servers", "submitcaption"=>"Use these email servers", "name"=>"custom", "summary"=>"Enable email for this domain, using your own mailservers. You have to make sure that these are configured correctly to accept email for this domain.", "subform"=>array(
					array("type"=>"array", "field"=>array("title"=>"Mail server", "type"=>"text", "name"=>"server"))
				))
			))
		),
		$values);
}

function withdrawDomainForm($domainID, $error = "", $values = null)
{
	$expiredate = domainsDomainExpiredate($domainID);
	return operationForm("withdrawdomain.php?id=$domainID", $error, "Withdraw domain", "Withdraw domain", array(), $values, array("custom"=>"<p>This will cause the domain to expire on $expiredate.</p>"));
}

function restoreDomainForm($domainID, $error = "", $values = null)
{
	$expiredate = domainsDomainExpiredate($domainID);
	return operationForm("restoredomain.php?id=$domainID", $error, "Restore domain", "Restore domain", array(), $values, array("custom"=>"<p>This will retain the domain indefinitely. If the domain is not restored, it will expire on $expiredate.</p>"));
}

function unregisterDomainForm($domainID, $error = "", $values = null)
{
	return operationForm("unregisterdomain.php?id=$domainID", $error, "Unregister domain", "Unregister domain", array(), $values, array("custom"=>"<p>This will delete the domain immediately.</p>"));
}

function deleteDomainForm($domainID, $error = "", $values = null)
{
	return operationForm("deletedomain.php?id=$domainID", $error, "Delete subdomain", "Delete subdomain", array(), $values, array("custom"=>"<p>Are you sure you want to remove this subdomain, and all it's subdomains?</p>"));
}

function removeDomain($domainID)
{
	foreach($GLOBALS["database"]->stdList("dnsDomain", array("parentDomainID"=>$domainID), "domainID") as $subDomainID) {
		removeDomain($subDomainID);
	}
	$GLOBALS["database"]->stdDel("dnsDelegatedNameServer", array("domainID"=>$domainID));
	$GLOBALS["database"]->stdDel("dnsMailServer", array("domainID"=>$domainID));
	$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID));
	$GLOBALS["database"]->stdDel("dnsDomain", array("domainID"=>$domainID));
}

function subdomains($domainID) {
	$subDomains = $GLOBALS["database"]->stdList("dnsDomain", array("parentDomainID"=>$domainID), "domainID");
	$subSubDomains = array();
	foreach($subDomains as $subDomainID) {
		$subSubDomains = array_merge($subSubDomains, subdomains($subDomainID));
	}
	return array_merge($subDomains, $subSubDomains);
}

function rootDomainID($domainID)
{
	$parentDomainID = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "parentDomainID");
	if($parentDomainID == null) {
		return $domainID;
	} else {
		return rootDomainID($parentDomainID);
	}
}

function isSubDomain($domainID)
{
	return $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "parentDomainID") != null;
}

function validDomainPart($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	if(preg_match('/^[-_a-zA-Z0-9]*$/', $name) != 1) {
		return false;
	}
	return true;
}

function validDomain($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	
	$parts = explode(".", $name);
	if(count($parts) == 0) {
		return false;
	}
	
	foreach($parts as $part) {
		if(!validDomainPart($part)) {
			return false;
		}
	}
	
	return true;
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

function validIPv6($ip) // TODO
{
	$parts = explode(":", $ip);
	if(count($parts) > 8) {
		return false;
	}
	$emptyFound = false;
	for($i = 1; $i < count($parts) - 1; $i++) {
		if($parts[$i] == "") {
			if($emptyFound) {
				return false;
			} else {
				$emptyFound = true;
			}
		}
	}
	if(!$emptyFound && count($parts) != 8) {
		return false;
	}
	foreach($parts as $part) {
		if(strlen($part) > 4) {
			return false;
		}
		for($i = 0; $i < strlen($part); $i++) {
			if(strpos($part[$i], "1234567890abcdefABCDEF") === false) {
				return false;
			}
		}
	}
	return true;
}

?>