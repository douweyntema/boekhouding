<?php

require_once(dirname(__FILE__) . "/../common.php");

function menuDomains()
{
	if($GLOBALS["menuComponent"] == "domains") {
		return <<<HTML
<li>
<a href="{$GLOBALS["rootHtml"]}domains/">Domains</a>
<ul>
<li><a href="{$GLOBALS["rootHtml"]}domains/functie1.php">Functie 1</a></li>
<li><a href="{$GLOBALS["rootHtml"]}domains/functie2.php">Functie 2</a></li>
</ul>
</li>

HTML;
	} else {
		return "<li><a href=\"{$GLOBALS["rootHtml"]}domains/\">Domains</a></li>\n";
	}
}

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
	$html = "";
	$domains = array();
	foreach($GLOBALS["database"]->stdList("dnsDomain", array("customerID"=>customerID()), array("domainID", "parentDomainID", "name")) as $domainPart) {
		$domainName = fullDomainName($domainPart["name"], $domainPart["parentDomainID"]);
		$domains[$domainName] = array("domainID"=>$domainPart["domainID"], "name"=>$domainName);
	}
	ksort($domains);
	$html .= <<<HTML
<div class="list">
<table>
<thead>
<tr><th>Domain</th></tr>
</thead>
<tbody>
HTML;
	foreach($domains as $domain) {
		$html .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}domains/domain.php?id={$domain["domainID"]}\">{$domain["name"]}</a></td></tr>\n";
	}
	$html .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $html;
}

function addDomainDetails($domainID)
{
	$html = "TODO";
	
	return $html;
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