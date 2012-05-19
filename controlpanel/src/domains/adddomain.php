<?php

require_once("common.php");

function showPage($content)
{
	die(page(makeHeader("Register new domain", domainsBreadcrumbs(), crumbs("Register domain", "adddomain.php")) . $content));
}

function addHiddens($fields, $reserved = null)
{
	$names = $reserved === null ? array() : $reserved;
	foreach($fields as $field) {
		$names = array_merge($names, fieldNames($field));
	}
	foreach($_POST as $key=>$value) {
		if(!in_array($key, $names)) {
			$fields[] = array("type"=>"hidden", "name"=>$key, "value"=>$value);
		}
	}
	return $fields;
}

function domainName()
{
	return post("name") . "." . $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>post("tldID")), "name");
}

function domainNameForm($error)
{
	$tlds = array();
	foreach($GLOBALS["database"]->stdList("infrastructureDomainTld", array("active"=>1), array("domainTldID", "name", "price"), array("order"=>"A")) as $tld) {
		$tlds[] = array("value"=>$tld["domainTldID"], "label"=>$tld["name"] . " (" . formatPrice($tld["price"]) . " / year)");
	}
	
	return operationForm("adddomain.php", $error, "Domain name", "Use This Domain",
		addHiddens(array(
			array("title"=>"Domain name", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"name", "fill"=>true),
				array("type"=>"html", "html"=>"."),
				array("type"=>"dropdown", "name"=>"tldID", "options"=>$tlds)
			)),
			isImpersonating() ? array("label"=>"Database only", "type"=>"checkbox", "name"=>"databaseonly") : null
		)),
		$_POST);
}

function domainNameSummary()
{
	return operationForm("adddomain.php?step=name", "", "Domain name", "Edit", addHiddens(array(
		array("title"=>"Name", "type"=>"html", "html"=>domainName()),
		array("title"=>"Price", "type"=>"html", "html"=>formatPrice($GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>post("tldID")), "price")))
	)), $_POST);
}

function addressTypeForm($error)
{
	return operationForm("adddomain.php", $error, "Address configuration", null, addHiddens(domainAddressForm(domainName(), null), array("none", "trevaweb", "ip", "cname", "delegation")), $_POST);
}

function addressTypeSummary()
{
	$map = array("none"=>"NONE", "trevaweb"=>"TREVA-WEB", "ip"=>"IP", "cname"=>"CNAME", "delegation"=>"DELEGATION");
	$type = $map[searchKey($_POST, "none", "trevaweb", "ip", "cname", "delegation")];
	$delegations = parseArrayField($_POST, array("hostname", "ipv4Address", "ipv6Address"));
	return operationForm("adddomain.php?step=address", "", "Address configuration", "Edit", addHiddens(domainAddressStubForm($type, null, array(post("ipv4")), array(post("ipv6")), post("cnameTarget"), $delegations)), $_POST);
}

function httpTypeForm($error)
{
	return operationForm("adddomain.php", $error, "Web hosting configuration", null, addHiddens(array(httpPathFunctionForm(null)), array("hosted", "redirect", "mirror")), $_POST);
}

function httpTypeSummary()
{
	$map = array("hosted"=>"HOSTED", "redirect"=>"REDIRECT", "mirror"=>"MIRROR");
	$type = $map[searchKey($_POST, "hosted", "redirect", "mirror")];
	return operationForm("adddomain.php?step=http", "", "Web hosting configuration", "Edit", addHiddens(httpPathFunctionStubForm(domainName(), $type, post("documentOwner"), trim(post("documentRoot"), "/"), post("redirectTarget"), post("mirrorTarget"), "Unknown")), $_POST);
}

function mailTypeForm($error)
{
	return operationForm("adddomain.php", $error, "Mail configuration", null, addHiddens(domainMailForm(), array("noemail", "treva", "custom")), $_POST);
}

function mailTypeSummary()
{
	$map = array("noemail"=>"NONE", "treva"=>"TREVA", "custom"=>"CUSTOM");
	$type = $map[searchKey($_POST, "noemail", "treva", "custom")];
	$mailServers = array();
	foreach(parseArrayField($_POST, array("server")) as $s) {
		$mailServers[] = $s["server"];
	}
	return operationForm("adddomain.php?step=mail", "", "Mail configuration", "Edit", addHiddens(domainMailStubForm($type, $mailServers)), $_POST);
}

function confirmForm($error)
{
	$name = domainName();
	$price = formatPrice($GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>post("tldID")), "price"));
	return operationForm("adddomain.php", $error, "Confirmation", "Register Domain", addHiddens(array()), $_POST, array("confirmbilling"=>"Are you sure you want to register the domain <strong>$name</strong> as configured above at a cost of <strong>$price</strong> per year?"));
}

function main()
{
	doDomains();
	doDomainsBilling();
	
	$check = function($condition, $error) {
		if(!$condition) showPage(domainNameForm($error));
	};
	
	$databaseOnly = isImpersonating() && post("databaseonly") !== null;
	
	$check(($domainName = post("name")) !== null, "");
	$check(($tldID = post("tldID")) !== null, "");
	$tld = $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$tldID), "name");
	
	if(!isImpersonating() && billingDomainPrice($tldID) > 0) {
		$messageUrl = urlencode("Domain registration limit reached");
		$check(domainsCustomerUnpaidDomainsPrice(customerID()) < domainsCustomerUnpaidDomainsLimit(customerID()), "Registration limit reached. Please <a href=\"{$GLOBALS["root"]}ticket/addthread.php?title=$messageUrl\">contact us</a> for more information.");
	}
	$check($GLOBALS["database"]->stdExists("infrastructureDomainTld", array("domainTldID"=>$tldID)), "");
	
	$check(validDomainPart($domainName), "Invalid domain name.");
	
	$check(!$GLOBALS["database"]->stdExists("dnsDomain", array("domainTldID"=>$tldID, "name"=>"domainName")), "The chosen domain name is already registered.");
	
	$fullDomainNameSql = $GLOBALS["database"]->addSlashes("$domainName.$tld");
	$check($GLOBALS["database"]->query("SELECT `httpDomain`.`domainID` FROM `httpDomain` INNER JOIN `infrastructureDomainTld` USING(`domainTldID`) WHERE CONCAT_WS('.', `httpDomain`.`name`, `infrastructureDomainTld`.`name`) = '$fullDomainNameSql'")->numRows() == 0, "The chosen domain name is already registered.");
	$check($GLOBALS["database"]->query("SELECT `mailDomain`.`domainID` FROM `mailDomain` INNER JOIN `infrastructureDomainTld` USING(`domainTldID`) WHERE CONCAT_WS('.', `mailDomain`.`name`, `infrastructureDomainTld`.`name`) = '$fullDomainNameSql'")->numRows() == 0, "The chosen domain name is already registered.");
	
	if(!$databaseOnly) {
		$check(domainsDomainAvailable($domainName, $tldID), "The chosen domain name is already registered.");
	}
	
	$check(get("step") != "name", "");
	
	
	
	$check = function($condition, $error) {
		if(!$condition) showPage(domainNameSummary() . addressTypeForm($error));
	};
	
	$check(($addressType = searchKey($_POST, "none", "trevaweb", "ip", "cname", "delegation")) !== null, "");
	
	if($addressType == "ip") {
		$ipv4 = post("ipv4");
		$ipv6 = post("ipv6");
		
		$check($ipv4 != "" || $ipv6 != "", "Please enter at least an IPv4 address or an IPv6 address.");
		$check($ipv4 == "" || validIpv4($ipv4), "Invalid IPv4 address.");
		$check($ipv6 == "" || validIpv6($ipv6), "Invalid IPv6 address.");
	} else if($addressType == "cname") {
		$check(($target = post("cnameTarget")) !== null, "");
		
		$target = trim($target, ".");
		
		$check(validDomain($target), "Invalid target domain name.");
	} else if($addressType == "delegation") {
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
	}
	$check(get("step") != "address", "");
	
	
	
	$useWeb = ($addressType == "trevaweb");
	if($useWeb) {
		$check = function($condition, $error) {
			if(!$condition) showPage(domainNameSummary() . addressTypeSummary() . httpTypeForm($error));
		};
		
		if(post("documentRoot") == null) {
			$_POST["documentRoot"] = domainName();
		}
		
		$check(($httpType = searchKey($_POST, "hosted", "redirect", "mirror")) !== null, "");
		
		if($httpType == "hosted") {
			$userID = post("documentOwner");
			$directory = trim(post("documentRoot"), "/");
			
			$check($GLOBALS["database"]->stdExists("adminUser", array("userID"=>$userID, "customerID"=>customerID())), "");
			$check(validDocumentRoot($directory), "Invalid document root.");
		} else if($httpType == "mirror") {
			$mirrorTarget = post("mirrorTarget");
			
			$check(($path = $GLOBALS["database"]->stdGetTry("httpPath", array("pathID"=>$mirrorTarget), array("domainID", "type"))) !== null, "");
			$check($path["type"] != "MIRROR", "");
			$check($GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$path["domainID"]), "customerID") == customerID(), "");
		}
		$check(get("step") != "http", "");
	}
	
	
	
	$check = function($condition, $error) use($useWeb) {
		if(!$condition) showPage(domainNameSummary() . addressTypeSummary() . ($useWeb ? httpTypeSummary() : "") . mailTypeForm($error));
	};
	
	$check(($mailType = searchKey($_POST, "noemail", "treva", "custom")) !== null, "");
	
	if($mailType == "noemail") {
		$function = array("mailType"=>"NONE");
	} else if($mailType == "treva") {
		$function = array("mailType"=>"TREVA");
	} else if($mailType == "custom") {
		$servers = parseArrayField($_POST, array("server"));
		
		$error = array();
		foreach($servers as $server) {
			if(!validDomain($server["server"])) {
				$error[] = "Invalid mailserver: " . htmlentities($server["server"]);
			}
		}
		if(count($error) > 0) {
			$check(false, implode("<br />", $error));
		}
	}
	$check(get("step") != "mail", "");
	
	
	
	$check = function($condition, $error) use($useWeb) {
		if(!$condition) showPage(domainNameSummary() . addressTypeSummary() . ($useWeb ? httpTypeSummary() : "") . mailTypeSummary() . confirmForm($error));
	};
	
	$check(post("confirm") !== null, null);
	
	
	
	$GLOBALS["database"]->startTransaction();
	$domainID = $GLOBALS["database"]->stdNew("dnsDomain", array("customerID"=>customerID(), "domainTldID"=>$tldID, "name"=>$domainName, "addressType"=>"NONE", "mailType"=>"NONE"));
	if(!$databaseOnly) {
		$ok = domainsRegisterDomain(customerID(), $domainName, $tldID);
		if(!$ok) {
			$GLOBALS["database"]->rollbackTransaction();
			$check(false, "An error occured while registering this domain. Please try again later or <a href=\"{$GLOBALS["root"]}ticket/addthread.php\">contact us</a>.");
		}
	}
	if($GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$tldID), array("price")) > 0) {
		$subscriptionID = billingNewSubscription(customerID(), "Registratie domein $domainName", null, 0, 0, $tldID, "YEAR", 1, 0, null);
		$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), array("subscriptionID"=>$subscriptionID));
	}
	
	if($addressType == "none") {
		$function = array("addressType"=>"NONE");
	} else if($addressType == "inherit") {
		$function = array("addressType"=>"INHERIT");
	} else if($addressType == "trevaweb") {
		$function = array("addressType"=>"TREVA-WEB");
	} else if($addressType == "ip") {
		$ipv4 = post("ipv4");
		$ipv6 = post("ipv6");
		
		if($ipv4 != "") {
			$GLOBALS["database"]->stdNew("dnsRecord", array("domainID"=>$domainID, "type"=>"A", "value"=>$ipv4));
		}
		if($ipv6 != "") {
			$GLOBALS["database"]->stdNew("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA", "value"=>$ipv6));
		}
		$function = array("addressType"=>"IP");
	} else if($addressType == "cname") {
		$function = array("addressType"=>"CNAME", "cnameTarget"=>trim(post("cnameTarget"), "."));
	} else if($addressType == "delegation") {
		$delegations = parseArrayField($_POST, array("hostname", "ipv4Address", "ipv6Address"));
		
		foreach($delegations as $server) {
			$GLOBALS["database"]->stdNew("dnsDelegatedNameServer", array("domainID"=>$domainID, "hostname"=>trim($server["hostname"]), "ipv4Address"=>trim($server["ipv4Address"]), "ipv6Address"=>trim($server["ipv6Address"]) == "" ? null : trim($server["ipv6Address"])));
		}
		$function = array("addressType"=>"DELEGATION");
	} else {
		die("Internal error");
	}
	$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), array_merge(array("cnameTarget"=>null), $function));
	
	if($useWeb) {
		if($httpType == "hosted") {
			$userID = post("documentOwner");
			$directory = trim(post("documentRoot"), "/");
			
			$function = array("type"=>"HOSTED", "hostedUserID"=>$userID, "hostedPath"=>$directory);
		} else if($httpType == "redirect") {
			$function = array("type"=>"REDIRECT", "redirectTarget"=>post("redirectTarget"));
		} else if($httpType == "mirror") {
			$mirrorTarget = post("mirrorTarget");
			
			$function = array("type"=>"MIRROR", "mirrorTargetPathID"=>$mirrorTarget);
		} else {
			die("Internal error");
		}
		$httpDomainID = $GLOBALS["database"]->stdNew("httpDomain", array("customerID"=>customerID(), "domainTldID"=>$tldID, "parentDomainID"=>null, "name"=>$domainName));
		$GLOBALS["database"]->stdNew("httpPath", array_merge(array("parentPathID"=>null, "domainID"=>$httpDomainID, "name"=>null), $function));
	}
	
	if($mailType == "noemail") {
		$function = array("mailType"=>"NONE");
	} else if($mailType == "treva") {
		$GLOBALS["database"]->stdNew("mailDomain", array("customerID"=>customerID(), "domainTldID"=>$tldID, "name"=>$domainName));
		
		$function = array("mailType"=>"TREVA");
	} else if($mailType == "custom") {
		$servers = parseArrayField($_POST, array("server"));
		
		$index = 0;
		foreach($servers as $server) {
			$GLOBALS["database"]->stdNew("dnsMailServer", array("domainID"=>$domainID, "name"=>$server["server"], "priority"=>(10 * ++$index)));
		}
		$function = array("mailType"=>"CUSTOM");
	} else {
		die("Internal error");
	}
	$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), $function);
	
	$GLOBALS["database"]->commitTransaction();
	
	updateDomains(customerID());
	
	redirect("domains/domain.php?id=$domainID");
}

main();

?>