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
	if($domainID != null) {
		useCustomer($GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "customerID", false));
	}
}

function domainBreadcrumbs($domainID, $postfix = array())
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
			$parts[] = array("id"=>$nextDomainID, "name"=>fullDomainName($domain["name"], $domain["parentDomainID"]), "show"=>false);
			break;
		} else {
			$parts[] = array("id"=>$nextDomainID, "name"=>$domain["name"], "show"=>true);
			$nextDomainID = $domain["parentDomainID"];
		}
	}
	
	$parts = array_reverse($parts);
	$crumbs = array();
	$crumbs[] = array("name"=>"Domains", "url"=>"{$GLOBALS["root"]}domains/");
	$domainPostfix = "";
	while(count($parts) > 0) {
		$part = array_shift($parts);
		if($part["show"]) {
			$crumbs[] = array("name"=>$part["name"] . $domainPostfix, "url"=>"{$GLOBALS["root"]}domains/domain.php?id={$part["id"]}");
		}
		$domainPostfix = "." . $part["name"] . $domainPostfix;
	}
	return breadcrumbs(array_merge($crumbs, $postfix));
}


function domainsList()
{
	$output = "";
	$customerIDEscaped = $GLOBALS["database"]->addSlashes(customerID());
	$ownDomains = $GLOBALS["database"]->query("SELECT domainID, parentDomainID, name FROM dnsDomain AS child WHERE customerID='$customerIDEscaped' AND (parentDomainID IS NULL OR (SELECT customerID FROM dnsDomain AS parent WHERE parent.domainID = child.parentDomainID) IS NULL OR customerID <> (SELECT customerID FROM dnsDomain AS parent WHERE parent.domainID = child.parentDomainID))")->fetchList();
	
	$domains = array();
	foreach($ownDomains as $ownDomain) {
		$domainName = fullDomainName($ownDomain["name"], $ownDomain["parentDomainID"]);
		$domains[$domainName] = array("domainID"=>$ownDomain["domainID"], "name"=>$domainName);
	}
	ksort($domains);
	
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Domain</th></tr>
</thead>
<tbody>
HTML;
	foreach($domains as $domain) {
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}domains/domain.php?id={$domain["domainID"]}\">{$domain["name"]}</a></td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function subDomainsList($parentDomainID)
{
	$output = "";
	
	$domains = array();
	foreach($GLOBALS["database"]->stdList("dnsDomain", array("customerID"=>customerID(), "parentDomainID"=>$parentDomainID), array("domainID", "parentDomainID", "name")) as $domain) {
		$domainName = fullDomainName($domain["name"], $domain["parentDomainID"]);
		$domains[$domainName] = array("domainID"=>$domain["domainID"], "name"=>$domainName);
	}
	ksort($domains);
	
	if(count($domains) == 0) {
		return "";
	}
	
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Subdomains</th></tr>
</thead>
<tbody>
HTML;
	foreach($domains as $domain) {
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}domains/domain.php?id={$domain["domainID"]}\">{$domain["name"]}</a></td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function domainDetail($domainID)
{
	$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("name", "parentDomainID"));
	$tld = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domain["parentDomainID"]), "name");
	$domainName = domainName($domainID);
	$domainNameHtml = htmlentities($domainName);
	
	$output  = "<div class=\"operation\">\n";
	$output .= "<h2>Domain $domainNameHtml</h2>";
	$output .= "<table>";
	$output .= "<tr><th>Name:</th><td class=\"stretch\">{$domainNameHtml}</td></tr>";
	if(!isSubDomain($domainID)) {
		$status = domainsDomainStatusDescription($domainName);
		$price = formatPrice(billingDomainPrice(customerID(), $tld));
		$output .= "<tr><th>Status:</th><td class=\"stretch\">{$status}</td></tr>";
		$output .= "<tr><th>Price per year:</th><td class=\"stretch\">{$price}</td></tr>";
	}
	$output .= "</table>";
	$output .= "</div>";
	return $output;
}

function addressForm($domainID, $error = "")
{
	$trevaServers = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "useTrevaWebservers");
	
	if($trevaServers["useTrevaWebservers"] == 1) {
		$webservers = "Treva webservers";
	} else {
		$a = $GLOBALS["database"]->stdGetTry("dnsRecord", array("domainID"=>$domainID, "type"=>"A"), "value");
		$aaaa = $GLOBALS["database"]->stdGetTry("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA"), "value");
		if($a == null && $aaaa == null) {
			$webservers = "No webservers configured";
		} else if($a == null) {
			$webservers = $aaaa;
		} else if($aaaa == null) {
			$webservers = $a;
		} else {
			$webservers = $a . ", " . $aaaa;
		}
	}



	return <<<HTML
<div class="operation">
<h2>Address</h2>
<table>

<tr class="submit"><td colspan="2"><input type="submit" value="Edit"></td></tr>
</table>
</div>

HTML;
}

function addDomainForm($error = "", $rootDomainID = null, $name = null)
{
	if($error === null) {
		$rootDomainName = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$rootDomainID), "name");
		$price = formatPrice(billingDomainPrice(customerID(), $rootDomainName));
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$messageHtml .= "<p class=\"billing\">Registering this domain will cost $price per year</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = "readonly=\"readonly\"";
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
	}
	
	$parentDomainIDs = "";
	if($readonly == "") {
		$parentDomainIDs .= "<select name=\"rootDomainID\">";
		$rootDomains = $GLOBALS["database"]->stdList("dnsDomain", array("customerID"=>null), array("domainID", "name"));
		foreach($rootDomains as $domain) {
			if($rootDomainID == $domain["domainID"]) {
				$selected = "selected=\"selected\"";
			} else {
				$selected = "";
			}
			$parentDomainIDs .= "<option value=\"{$domain["domainID"]}\" $selected>" . htmlentities($domain["name"]) . " (" . formatPrice(billingDomainPrice(customerID(), $domain["name"])) . " / year)</option>\n";
		}
		$parentDomainIDs .= "</select>";
	} else {
		$rootDomainName = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$rootDomainID), "name");
		$parentDomainIDs .= "<input type=\"hidden\" name=\"rootDomainID\" value=\"$rootDomainID\" />{$rootDomainName}";
	}
	
	$nameValue = inputValue($name);
	return <<<HTML
<div class="operation">
<h2>Add domain</h2>
$messageHtml
<form action="adddomain.php" method="post">
$confirmHtml
<table>
<tr><th>Domain name:</th><td class="stretch"><input type="text" name="name" $nameValue $readonly /></td><td style="white-space: nowrap;">.$parentDomainIDs</td></tr>
<tr class="submit"><td colspan="3"><input type="submit" value="Register domain"></td></tr>
</table>
</form>
</div>

HTML;
}

function addSubdomainForm($domainID, $error = "", $name = null)
{
	$parentName = domainName($domainID);
	$parentNameHtml = htmlentities($parentName);
	
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = "readonly=\"readonly\"";
		$stub = false;
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
		$stub = false;
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
		$stub = false;
	}
	
	$nameValue = inputValue($name);
	return <<<HTML
<div class="operation">
<h2>Add subdomain</h2>
$messageHtml
<form action="addsubdomain.php?id=$domainID" method="post">
$confirmHtml
<table>
<tr><th>Subdomain name:</th><td class="stretch"><input type="text" name="name" $nameValue /></td><td style="white-space: nowrap;">.$parentNameHtml</td></tr>
<tr class="submit"><td colspan="3"><input type="submit" value="Add"></td></tr>
</table>
</form>
</div>

HTML;
}

function removeSubdomain($domainID)
{
	$subDomains = $GLOBALS["database"]->stdList("dnsDomain", array("parentDomainID"=>$domainID), "domainID");
	foreach($subDomains as $subdomain) {
		removeSubdomain($domainID);
	}
	$GLOBALS["database"]->stdDel("dnsDomain", array("domainID"=>$domainID));
}

function editHostsForm($domainID, $error = "", $x = null)
{
	if($error == "STUB") {
		$trevaServers = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("useTrevaNameservers", "useTrevaMailservers", "useTrevaWebservers"));
		
		if($trevaServers["useTrevaWebservers"] == 1) {
			$webservers = "Treva webservers";
		} else {
			$a = $GLOBALS["database"]->stdGetTry("dnsRecord", array("domainID"=>$domainID, "type"=>"A"), "value");
			$aaaa = $GLOBALS["database"]->stdGetTry("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA"), "value");
			if($a == null && $aaaa == null) {
				$webservers = "No webservers configured";
			} else if($a == null) {
				$webservers = $aaaa;
			} else if($aaaa == null) {
				$webservers = $a;
			} else {
				$webservers = $a . ", " . $aaaa;
			}
		}
		
		if($trevaServers["useTrevaMailservers"] == 1) {
			$mailservers = "Treva mailservers";
		} else {
			$mx = $GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID, "type"=>"MX"), "value");
			if(count($mx) == 0) {
				$mailservers = "No mailservers configured";
			} else {
				$mailservers = implode(",", $mx);
			}
		}
		
		if($trevaServers["useTrevaNameservers"] == 1) {
			$nameservers = "Treva nameservers";
		} else {
			$ns = $GLOBALS["database"]->stdGet("dnsRecord", array("domainID"=>$domainID, "type"=>"NS"), "value");
			if(count($ns) == 0) {
				$nameservers = "No nameservers configured";
			} else {
				$nameservers = implode(",", $ns);
			}
		}
		
		$others = "";
		$records = $GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID), array("type", "value"));
		foreach($records as $record) {
			if(in_array(strtoupper($record["type"]), array("A", "AAAA", "MX", "NS"))) {
				continue;
			}
			$others .= "<tr><th>{$record["type"]}:</th><td>{$record["value"]}</td></tr>";
		}
		
		return <<<HTML
<div class="operation">
<h2>Host settings</h2>
<form action="edithost.php?id=$domainID" method="post">
<table>
<tr><th>Webservers:</th><td>$webservers</td></tr>
<tr><th>Mailservers:</th><td>$mailservers</td></tr>
<tr><th>Nameservers:</th><td>$nameservers</td></tr>
$others
<tr class="submit"><td colspan="2"><input type="submit" value="Edit"></td></tr>
</table>
</form>
</div>
HTML;
	} else {
		if($error == "") {
		
		} else if($error === null) {
		
		} else {
		
		}
	return <<<HTML
<div class="operation">
<h2>Host settings</h2>
<form>
<table>

<tr class="submit"><td colspan="2"><input type="submit" value="Edit"></td></tr>
</table>
</form>
</div>

HTML;
	}
}

function isSubDomain($domainID)
{
	$parentDomainID = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "parentDomainID");
	return $parentDomainID != null;
}

function domainName($domainID)
{
	$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("parentDomainID", "name"));
	
	return fullDomainName($domain["name"], $domain["parentDomainID"]);
}

function fullDomainName($domainName, $parentID)
{
	if($parentID == null) {
		return $domainName;
	}
	$next = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$parentID), array("parentDomainID", "name"));
	return fullDomainName($domainName . "." . $next["name"], $next["parentDomainID"]);
}

function validDomainPart($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	if(preg_match('/^[-a-zA-Z0-9]*$/', $name) != 1) {
		return false;
	}
	return true;
}

?>