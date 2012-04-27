<?php

require_once("common.php");

function main()
{
	doMail();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader("Add domain", mailBreadcrumbs(), crumbs("Add domain", "adddomain.php")) . addMailDomainForm($error, $_POST)));
	};
	
	$domainName = post("domainName");
	
	$check(validDomain($domainName), "Invalid domain name");
	
	$tld = $GLOBALS["database"]->stdGetTry("infrastructureDomainTld", array("domainTldID"=>post("domainTldID")), "name", false);
	$check($tld !== false, "");
	$fullDomainNameSql = $GLOBALS["database"]->addSlashes("$domainName.$tld");
	$check($GLOBALS["database"]->query("SELECT `mailDomain`.`domainID` FROM `mailDomain` LEFT JOIN `infrastructureDomainTld` USING(`domainTldID`) WHERE CONCAT_WS('.', `mailDomain`.`name`, `infrastructureDomainTld`.`name`) = '$fullDomainNameSql'")->numRows() == 0, "A domain with the same name already exists");
	
	$check(post("confirm") !== null, null);
	
	$domainID = $GLOBALS["database"]->stdNew("mailDomain", array("customerID"=>customerID(), "domainTldID"=>post("domainTldID"), "name"=>$domainName));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id=$domainID");
}

main();

?>