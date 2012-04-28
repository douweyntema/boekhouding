<?php

require_once("common.php");

function main()
{
	doDomains();
	doDomainsBilling();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader("Register new domain", domainsBreadcrumbs(), crumbs("Register domain", "adddomain.php")) . addDomainForm($error, $_POST)));
	};
	
	$databaseOnly = isImpersonating() && post("databaseonly") !== null;
	
	$check(($domainName = post("name")) !== null, "");
	$check(($tldID = post("tldID")) !== null, "");
	if(!isImpersonating()) {
		$messageUrl = urlencode("Domain registration limit reached");
		$check(domainsCustomerUnpaidDomainsPrice(customerID()) < domainsCustomerUnpaidDomainsLimit(customerID()), "Registration limit reached. Please <a href=\"{$GLOBALS["root"]}ticket/addthread.php?title=$messageUrl\">contact us</a> for more information.");
	}
	$check(($tldName = $GLOBALS["database"]->stdGetTry("infrastructureDomainTld", array("domainTldID"=>$tldID), "name", null)) !== null, "");
	$check(validDomainPart($domainName), "Invalid domain name.");
	$check(!$GLOBALS["database"]->stdExists("dnsDomain", array("domainTldID"=>$tldID, "name"=>"domainName")), "The chosen domain name is already registered.");
	if(!$databaseOnly) {
		$check(domainsDomainAvailable($domainName, $tldID), "The chosen domain name is already registered.");
	}
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
		$subscriptionID = billingNewSubscription($customerID, "Registratie domein $domainName", null, 0, 0, $tldID, "YEAR", 1, 0, null);
		$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), array("subscriptionID"=>$subscriptionID));
	}
	$GLOBALS["database"]->commitTransaction();
	
	updateDomains(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id={$domainID}");
}

main();

?>