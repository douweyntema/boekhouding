<?php

require_once("common.php");

function main()
{
	doMail();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader("Add domain", mailBreadcrumbs(), crumbs("Add domain", "adddomain.php")) . addMailDomainForm($error, $_POST)));
	};
	
	$domainName = post("domainName");
	
	$check(validDomain($domainName), "Invalid domain name.");
	
	$tld = stdGetTry("infrastructureDomainTld", array("domainTldID"=>post("domainTldID")), "name", false);
	$check($tld !== false, "");
	$fullDomainNameSql = dbAddSlashes("$domainName.$tld");
	$check(query("SELECT `mailDomain`.`domainID` FROM `mailDomain` INNER JOIN `infrastructureDomainTld` USING(`domainTldID`) WHERE CONCAT_WS('.', `mailDomain`.`name`, `infrastructureDomainTld`.`name`) = '$fullDomainNameSql'")->numRows() == 0, "A domain with the chosen name already exists.");
	
	$check(post("confirm") !== null, null);
	
	$domainID = stdNew("mailDomain", array("customerID"=>customerID(), "domainTldID"=>post("domainTldID"), "name"=>$domainName));
	
	updateMail(customerID());
	
	redirect("mail/domain.php?id=$domainID");
}

main();

?>