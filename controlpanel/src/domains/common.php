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

function domainsList()
{
	$output = "";
	$domains = array();
	foreach($GLOBALS["database"]->stdList("dnsDomain", array("customerID"=>customerID()), array("domainID", "parentDomainID", "name")) as $domainPart) {
		$domainName = fullDomainName($domainPart["name"], $domainPart["parentDomainID"]);
		$domains[$domainName] = array("domainID"=>$domainPart["domainID"], "name"=>$domainName);
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

function domainDetail($domainID)
{
	$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("name", "parentDomainID"));
	$tld = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domain["parentDomainID"]), "name");
	$domainName = domainName($domainID);
	$domainNameHtml = htmlentities($domainName);
	
	try {
		$details = domainsDomainDetails($domainName);
		$verloopdatum = $details["verloopdatum"];
		$status = $details["status"];
		$autorenew = $details["autorenew"] ? "enabled" : "disabled";
	} catch(DomainResellerError $e) {
		$verloopdatum = "Unavailable";
		$status = "Unavailable";
		$autorenew = "Unavailable";
	}
	
	$price = formatPrice(billingDomainPrice(customerID(), $tld));
	
	$output  = "<div class=\"operation\">\n";
	$output .= "<h2>Domain $domainNameHtml</h2>";
	$output .= "<table>";
	$output .= "<tr><th>Name:</th><td class=\"stretch\">{$domainNameHtml}</td></tr>";
	$output .= "<tr><th>Autorenew:</th><td class=\"stretch\">{$autorenew}</td></tr>";
	$output .= "<tr><th>Expire date:</th><td class=\"stretch\">{$verloopdatum}</td></tr>";
	$output .= "<tr><th>Status:</th><td class=\"stretch\">{$status}</td></tr>";
	$output .= "<tr><th>Price per year:</th><td class=\"stretch\">{$price}</td></tr>";
	$output .= "</table>";
	$output .= "</div>";
	return $output;
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

function addSubdomain($domainID, $error = "", $name = null, $trevaNameServers = null, $trevaMailServers = null)
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
	
	if($stub) {
		$operationsHtml = "";
		$submitHTML = "<tr class=\"submit\"><td colspan=\"3\"><input type=\"submit\" value=\"Add\"></td></tr>";
	} else {
		$operationsHtml = pathFunctionSubform($readonly != "", $type, null, $hostedUserID, $hostedPath, $redirectTarget, $mirrorTargetPathID);
		$submitHTML = "";
	}
	
	$nameValue = inputValue($name);
	return <<<HTML
<div class="operation">
<h2>Add subdomain</h2>
$messageHtml
<form action="addsubdomain.php?id=$domainID" method="post">
$confirmHtml
<table>
<tr><th>Subdomain name:</th><td class="stretch"><input type="text" name="name" $nameValue /></td><td>.$parentNameHtml</td></tr>
$submitHTML
</table>

$operationsHtml

</form>
</div>

HTML;
}

function domainName($domainID)
{
	$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("parentDomainID", "name"));
	return fullDomainName($domain["name"], $domain["parentDomainID"]);
}

function fullDomainName($domainName, $parentID)
{
	if($parentID == 0) {
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