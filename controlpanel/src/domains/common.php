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
	$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), array("name"));
	$domainName = domainName($domainID);
	$domainNameHtml = htmlentities($domainName);
	
	$output  = "<div class=\"operation\">\n";
	$output .= "<h2>Domain $domainNameHtml</h2>";
	$output .= "<table>";
	$output .= "<tr><th>Name:</th><td class=\"stretch\">$domainNameHtml</td></tr>";
	$output .= "<tr><th>...</th><td class=\"stretch\">...</td></tr>";
	$output .= "</table>";
	$output .= "</div>";
	return $output;
}

function addDomain()
{
	
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
	} else if($error == "STUB") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
		$stub = true;
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

?>