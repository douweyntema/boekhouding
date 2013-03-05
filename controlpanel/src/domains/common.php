<?php

require_once(dirname(__FILE__) . "/../common.php");
require_once(dirname(__FILE__) . "/../ticket/api.php");

function doDomains()
{
	useComponent("domains");
	$GLOBALS["menuComponent"] = "domains";
}

function doDomainsBilling()
{
	useComponent("billing");
}

function doDomain($domainID)
{
	doDomains();
	useCustomer($GLOBALS["database"]->stdGetTry("dnsDomain", array("domainID"=>$domainID), "customerID", false));
	$rootDomainID = domainsRootDomainID($domainID);
	if(domainsDomainStatus($rootDomainID) == "expired" || domainsDomainStatus($rootDomainID) == "quarantaine") {
		domainsRemoveDomain($rootDomainID);
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
		if(domainsDomainStatus($domainID) == "expired" || domainsDomainStatus($domainID) == "quarantaine") {
			domainsRemoveDomain($domainID);
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
	return listTable(array("Domain name", "Status"), $rows, "Domains", false, "sortable list");
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
	return listTable(array("Subdomains"), $rows, null, false, "sortable list");
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
	
	return operationForm("adddomain.php", $error, "Register new domain", "Register Domain",
		array(
			array("title"=>"Domain name", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"name", "fill"=>true),
				array("type"=>"html", "html"=>"."),
				array("type"=>"dropdown", "name"=>"tldID", "options"=>$tlds)
			)),
			isImpersonating() ? array("label"=>"Database only", "type"=>"checkbox", "name"=>"databaseonly") : null
		),
		$values);
}

function addSubdomainForm($domainID, $error = "", $values = null)
{
	$addressType = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "addressType");
	if($addressType == "DELEGATION" || $addressType == "TREVA-DELEGATION") {
		return operationForm(null, "", "Add subdomain", null, array(array("type"=>"html", "html"=>"Adding subdomains is not available for this domain, because the domain address is currently configured as \"Nameserver delegation\".")), null);
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

function domainAddressForm($domainName, $parentDomainName)
{
	return array(array("type"=>"typechooser", "options"=>array(
		($parentDomainName === null)
			? array("title"=>"None", "submitcaption"=>"No Address", "name"=>"none", "summary"=>"Do not configure any address for this domain.", "subform"=>array())
			: array("title"=>"Inherit", "submitcaption"=>"Use $parentDomainName Settings", "name"=>"inherit", "summary"=>"Use the same settings as <em>$parentDomainName</em>.", "subform"=>array()),
		array("title"=>"Our webservers", "submitcaption"=>"Use Our Webservers", "name"=>"trevaweb", "summary"=>"Use the address of our webservers.", "subform"=>array()),
		array("title"=>"Custom address", "submitcaption"=>"Use These Adresses", "name"=>"ip", "summary"=>"Use your own custom address configuration.", "subform"=>array(
			array("title"=>"IPv4 address", "type"=>"text", "name"=>"ipv4"),
			array("title"=>"IPv6 address", "type"=>"text", "name"=>"ipv6")
		)),
		array("title"=>"Alias", "submitcaption"=>"Use This Alias", "name"=>"cname", "summary"=>"Let <em>$domainName</em> be an alias (also known as a <em>CNAME</em>) for another domain name.", "subform"=>array(
			array("title"=>"Target domain name", "type"=>"text", "name"=>"cnameTarget")
		)),
		array("title"=>"Nameserver delegation", "submitcaption"=>"Use Nameserver Delegation", "name"=>"delegation", "summary"=>"Delegate authority over <em>$domainName</em> to external nameservers.", "subform"=>array(
			array("title"=>"", "type"=>"colspan", "columns"=>array(
				array("type"=>"html", "html"=>"Hostname", "celltype"=>"th", "fill"=>true),
				array("type"=>"html", "html"=>"IPv4 Address", "celltype"=>"th"),
				array("type"=>"html", "html"=>"IPv6 Address", "celltype"=>"th")
			)),
			array("type"=>"array", "field"=>array("title"=>"Nameserver", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"hostname", "fill"=>true),
				array("type"=>"text", "name"=>"ipv4Address"),
				array("type"=>"text", "name"=>"ipv6Address")
			)))
		))
	)));
}

function domainAddressStubForm($type, $parentDomainName, $ipv4Addresses, $ipv6Addresses, $cnameTarget, $delegatedNameServers)
{
	$form = array();
	if($type == "NONE") {
		$form[] = array("title"=>"Address type", "type"=>"html", "html"=>"No address.");
	} else if($type == "INHERIT") {
		$form[] = array("title"=>"Address type", "type"=>"html", "html"=>"Inherit from <em>$parentDomainName</em>");
	} else if($type == "TREVA-WEB") {
		$form[] = array("title"=>"Address type", "type"=>"html", "html"=>"Use our webservers", "url"=>canAccessComponent("http") ? "{$GLOBALS["root"]}http/" : null);
	} else if($type == "IP") {
		$form[] = array("title"=>"Address type", "type"=>"html", "html"=>"Custom address");
		$form[] = array("title"=>"IPv4 address", "type"=>"html", "html"=>count($ipv4Addresses) == 0 ? "None" : implode(" ", $ipv4Addresses));
		$form[] = array("title"=>"IPv6 address", "type"=>"html", "html"=>count($ipv6Addresses) == 0 ? "None" : implode(" ", $ipv6Addresses));
	} else if($type == "CNAME") {
		$form[] = array("title"=>"Address type", "type"=>"html", "html"=>"Alias");
		$form[] = array("title"=>"Target domain name", "type"=>"html", "html"=>htmlentities($cnameTarget));
	} else if($type == "DELEGATION") {
		$form[] = array("title"=>"Address type", "type"=>"html", "html"=>"Nameserver delegation");
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
	return $form;
}

function editAddressForm($domainID, $error = "", $values = null)
{
	$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("addressType", "cnameTarget"));
	$ipv4 = $GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID, "type"=>"A"), "value");
	$ipv6 = $GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA"), "value");
	$delegatedNameServers = $GLOBALS["database"]->stdList("dnsDelegatedNameServer", array("domainID"=>$domainID), array("hostname", "ipv4Address", "ipv6Address"));
	
	$domainName = domainsFormatDomainName($domainID);
	$parentDomainID = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "parentDomainID");
	if($parentDomainID !== null) {
		$parentDomainName = domainsFormatDomainName($parentDomainID);
	} else {
		$parentDomainName = null;
	}
	
	if($error == "STUB") {
		return operationForm("editaddress.php?id=$domainID", "STUB", "Edit address configuration for $domainName", "Edit", domainAddressStubForm($domain["addressType"], $parentDomainName, $ipv4, $ipv6, $domain["cnameTarget"], $delegatedNameServers), null);
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
				$warning[] = "This will disable email for this domain.";
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
	
	return operationForm("editaddress.php?id=$domainID", $error, "Edit address configuration for $domainName", "Edit", domainAddressForm($domainName, $parentDomainName), $values, $messages);
}

function domainMailForm()
{
	return array(
		array("type"=>"typechooser", "options"=>array(
			array("title"=>"No email", "submitcaption"=>"No Email", "name"=>"noemail", "summary"=>"Disable email for this domain.", "subform"=>array()),
			array("title"=>"Email using our mailservers", "submitcaption"=>"Use Our Mailservers", "name"=>"treva", "summary"=>"Enable email for this domain, using our mailservers.", "subform"=>array()),
			array("title"=>"Email using custom mailservers", "submitcaption"=>"Use These Mailservers", "name"=>"custom", "summary"=>"Enable email for this domain, using custom mailservers.", "subform"=>array(
				array("type"=>"array", "field"=>array("title"=>"Mail server", "type"=>"text", "name"=>"server"))
			))
		))
	);
}

function domainMailStubForm($type, $mailServers)
{
	$form = array();
	if($type == "NONE") {
		$form[] = array("title"=>"Status", "type"=>"html", "html"=>"No email");
	} else if($type == "TREVA") {
		$form[] = array("title"=>"Status", "type"=>"html", "html"=>"Enabled using our mailservers", "url"=>canAccessComponent("mail") ? "{$GLOBALS["root"]}mail/" : null);
	} else if($type == "CUSTOM") {
		$form[] = array("title"=>"Status", "type"=>"html", "html"=>"Enabled using custom mailservers");
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
	
	return $form;
}

function editMailForm($domainID, $error = "", $values = null)
{
	$addressType = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "addressType");
	if($addressType == "CNAME" || $addressType == "DELEGATION" || $addressType == "TREVA-DELEGATION") {
		$type = $addressType == "CNAME" ? "\"Alias\"" : "\"Nameserver delegation\"";
		return operationForm(null, "", "Email configuration", null, array(
			array("type"=>"html", "html"=>"Email is not available for this domain, because the domain address is currently configured as $type.")
		), null);
	}
	
	$mailType = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "mailType");
	$mailServers = $GLOBALS["database"]->stdList("dnsMailServer", array("domainID"=>$domainID), "name", array("priority"=>"ASC"));
	
	if($error == "STUB") {
		return operationForm("editmail.php?id=$domainID", "STUB", "Edit email configuration for " . domainsFormatDomainName($domainID), "Edit", domainMailStubForm($mailType, $mailServers), null);
	}
	
	if($values === null || (!isset($values["noemail"]) && !isset($values["treva"]) && !isset($values["custom"]))) {
		if($mailType == "NONE") {
			$values = array("noemail"=>"1");
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
	
	return operationForm("editmail.php?id=$domainID", $error, "Edit email configuration for " . domainsFormatDomainName($domainID), "Edit", domainMailForm(), $values);
}

function withdrawDomainForm($domainID, $error = "", $values = null)
{
	$expiredate = domainsDomainExpiredate($domainID);
	return operationForm("withdrawdomain.php?id=$domainID", $error, "Withdraw domain", "Withdraw Domain", array(), $values, array("custom"=>"<p>This will cause the domain to expire on $expiredate.</p>"));
}

function restoreDomainForm($domainID, $error = "", $values = null)
{
	$expiredate = domainsDomainExpiredate($domainID);
	return operationForm("restoredomain.php?id=$domainID", $error, "Restore domain", "Restore Domain", array(), $values, array("custom"=>"<p>This will retain the domain indefinitely. If the domain is not restored, it will expire on $expiredate.</p>"));
}

function unregisterDomainForm($domainID, $error = "", $values = null)
{
	return operationForm("unregisterdomain.php?id=$domainID", $error, "Unregister domain", "Unregister Domain", array(), $values, array("custom"=>"<p>This will delete the domain immediately.</p>"));
}

function deleteDomainForm($domainID, $error = "", $values = null)
{
	$domainNameHtml = htmlentities(domainsFormatDomainName($domainID));;
	return operationForm("deletedomain.php?id=$domainID", $error, "Delete subdomain", "Delete Subdomain", array(), $values, array("custom"=>"<p>This will remove <em>$domainNameHtml</em> and all of its subdomains.</p>"));
}

function subdomains($domainID) {
	$subDomains = $GLOBALS["database"]->stdList("dnsDomain", array("parentDomainID"=>$domainID), "domainID");
	$subSubDomains = array();
	foreach($subDomains as $subDomainID) {
		$subSubDomains = array_merge($subSubDomains, subdomains($subDomainID));
	}
	return array_merge($subDomains, $subSubDomains);
}

?>