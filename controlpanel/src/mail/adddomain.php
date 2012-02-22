<?php

require_once("common.php");

function main()
{
	doMail();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(addHeader("Add domain", "adddomain.php") . addMailDomainForm($error, $_POST)));
	};
	
	$domainName = post("domainName");
	
	$check(validDomain($domainName), "Invalid domain name");
	$check(!$GLOBALS["database"]->stdExists("mailDomain", array("name"=>$domainName)), "A domain with the same name already exists");
	$check(post("confirm") !== null, null);
	
	$domainID = $GLOBALS["database"]->stdNew("mailDomain", array("customerID"=>customerID(), "name"=>$domainName));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id=$domainID");
}

main();

?>