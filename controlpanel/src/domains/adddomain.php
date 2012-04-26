<?php

require_once("common.php");

function main()
{
	doDomains();
	doDomainsBilling();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader("Register new domain", domainsBreadcrumbs(), crumbs("Register domain", "adddomain.php")) . addDomainForm($error, $_POST)));
	};
	
	$check(($domainName = post("name")) !== null, "");
	$check(($tldID = post("tldID")) !== null, "");
	$check(($tldName = $GLOBALS["database"]->stdGetTry("infrastructureDomainTld", array("domainTldID"=>$tldID), "name", null)) !== null, "");
	$check(validDomainPart($domainName), "Invalid domain name.");
	$check(!$GLOBALS["database"]->stdExists("dnsDomain", array("domainTldID"=>$tldID, "name"=>"domainName")), "The chosen domain name is already registered.");
	$check(domainsDomainAvailable($domainName, $tldID), "The chosen domain name is already registered.");
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->startTransaction();
	$domainID = $GLOBALS["database"]->stdNew("dnsDomain", array("customerID"=>customerID(), "domainTldID"=>$tldID, "name"=>$domainName, "addressType"=>"NONE", "mailType"=>"NONE"));
	$ok = domainsRegisterDomain(customerID(), $domainName, $tldID);
	if(!$ok) {
		$GLOBALS["database"]->rollbackTransaction();
		$check(false, "An error occured while registering this domain. Please try again later or <a href=\"{$GLOBALS["root"]}ticket/addthread.php\">contact us</a>.");
	}
	$GLOBALS["database"]->commitTransaction();
	
	updateDomains(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id={$domainID}");
}

main();

?>