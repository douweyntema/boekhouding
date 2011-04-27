<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doHttpDomain($domainID);
	
	if(isStubDomain($domainID)) {
		error404();
	}
	
	$content = "<h1>Web hosting - " . domainName($domainID) . "</h1>\n";
	
	$content .= domainBreadcrumbs($domainID, array(array("url"=>"{$GLOBALS["root"]}http/removedomain.php?id=$domainID", "name"=>"Remove domain")));
	
	$keepsubs = post("keepsubs") == "keep";
	
	// TODO: check voor aliassen, als deze er zijn naar een te verwijderen domain, maar zelf niet verwijderd wordt, dan weigeren te verwijderen.
	
	if(post("confirm") === null) {
		$content .= removeDomainForm($domainID, null, $keepsubs);
		die(page($content));
	}
	
	$parentDomainID = $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$domainID), "parentDomainID");
	
	$GLOBALS["database"]->startTransaction();
	removeDomain($domainID, $keepsubs);
	$GLOBALS["database"]->commitTransaction();
	
	// Distribute the accounts database
	updateHttp(customerID());
	
	header("HTTP/1.1 303 See Other");
	if($parentDomainID === null) {
		header("Location: {$GLOBALS["root"]}http/");
	} else {
		header("Location: {$GLOBALS["root"]}http/domain.php?id=$parentDomainID");
	}
}

main();

?>