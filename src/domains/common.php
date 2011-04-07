<?php

require_once(dirname(__FILE__) . "/../common.php");

function doDomains($domainID)
{
	useComponent("domains");
	$GLOBALS["menuComponent"] = "domains";
	if($domainID != null) {
		useCustomer($GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "customerID", false));
	}
}

function addDomainsList()
{
	$output = "";
	$domains = array();
	foreach($GLOBALS["database"]->stdList("dnsDomain", array("customerID"=>customerID()), array("domainID", "parentDomainID", "name")) as $domainPart) {
		$domainName = fullDomainName($domainPart["name"], $domainPart["parentDomainID"]);
		$domains[$domainName] = array("domainID"=>$domainPart["domainID"], "name"=>$domainName);
	}
	ksort($domains);
	$output .= <<<HTML
<div class="list">
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

function addDomainDetails($domainID)
{
	$output = "TODO";
	
	return $output;
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