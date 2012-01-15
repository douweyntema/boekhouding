<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomain($domainID);
	$domainName = domainName($domainID);
	
	$content = "<h1>Domain " . $domainName . "</h1>\n";
	
	$content .= domainBreadcrumbs($domainID, array(array("url"=>"{$GLOBALS["root"]}domains/removedomain.php?id=$domainID", "name"=>"Remove subdomain")));
	
	if(!isSubDomain($domainID)) {
		$content .= <<<HTML
<div class="operation">
<h2>Remove subdomain</h2>
<p class="error">This is not a subdomain and can not be removed. Please withdraw it.</p>
<p><a href="{$GLOBALS["root"]}domains/domain.php?id={$domainID}">Return to the domain</a></p>
</div>

HTML;
		die(page($content));
	}
	
	checkTrivialAction($content, "{$GLOBALS["root"]}domains/removedomain.php?id=$domainID", "Remove subdomain", "Are you sure you want to remove this subdomain, and all it's subdomains?");
	
	$parentDomainID = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "parentDomainID");
	
	$GLOBALS["database"]->startTransaction();
	removeDomain($domainID);
	$GLOBALS["database"]->commitTransaction();
	
	updateDomains(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id=$parentDomainID");
}

main();

?>