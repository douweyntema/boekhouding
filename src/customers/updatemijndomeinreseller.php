<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doCustomer($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader("Update contact information", customerBreadcrumbs($customerID), crumbs("Update contact information", "updatemijndomeinreseller.php?id=$customerID")) . customerMijnDomeinReseller($customerID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	domainsUpdateContactInfo($customerID);
	
	redirect("customers/customer.php?id=$customerID");
}

main();

?>