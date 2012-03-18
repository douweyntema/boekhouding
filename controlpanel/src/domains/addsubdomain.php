<?php

require_once("common.php");

function main()
{
	$parentDomainID = get("id");
	$subDomainName = post("name");
	doDomain($parentDomainID);
	
	$parentDomainName = domainsFormatDomainName($parentDomainID);
	
	$content = "<h1>Add subdomain - $subDomainName.$parentDomainName</h1>\n";
	
	$content .= domainBreadcrumbs($parentDomainID, array(array("name"=>"Add subdomain", "url"=>"{$GLOBALS["root"]}domains/addsubdomain.php?id=$parentDomainID")));
	
	if(!validDomainPart($subDomainName)) {
		$content .= addSubdomainForm($parentDomainID, "Invalid domain name.", $subDomainName);
		die(page($content));
	}
	
	if($GLOBALS["database"]->stdExists("dnsDomain", array("parentDomainID"=>$parentDomainID, "name"=>$subDomainName))) {
		$content .= addSubdomainForm($parentDomainID, "The chosen subdomain already exists.", $subDomainName);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= addSubdomainForm($parentDomainID, null, $subDomainName);
		die(page($content));
	}
	
	$GLOBALS["database"]->startTransaction();
	$domainID = $GLOBALS["database"]->stdNew("dnsDomain", array("customerID"=>customerID(), "parentDomainID"=>$parentDomainID, "name"=>$subDomainName));
	$GLOBALS["database"]->commitTransaction();
	
	updateDomains(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id={$domainID}");
}

main();

?>