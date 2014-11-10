<?php

require_once("common.php");

function main()
{
	doCustomers();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader("Add new customer", customersBreadcrumbs(), crumbs("Add customer", "addcustomer.php")) . addCustomerForm($error, $_POST)));
	};
	
	$notempty = function($field) use($check) {
		$check(trim(post($field)) != "", "Invalid $field");
	};
	
	$notempty("name");
	$notempty("initials");
	$notempty("lastName");
	$notempty("address");
	$notempty("postalCode");
	$notempty("city");
	$notempty("countryCode");
	$notempty("email");
	$notempty("invoiceFrequencyMultiplier");
	$notempty("invoiceFrequencyBase");
	
	$check(ctype_digit(post("invoiceFrequencyMultiplier")), "Invalid invoiceFrequencyMultiplier");
	$check(post("invoiceFrequencyBase") == "DAY" || post("invoiceFrequencyBase") == "MONTH" || post("invoiceFrequencyBase") == "YEAR", "Invalid invoiceFrequencyBase");
	
	$companyName = post("companyName");
	
	if($companyName == "") {
		$companyName = null;
	}
	
	$check(!stdExists("adminCustomer", array("name"=>post("name")), "customerID"), "A customer with the chosen name already exists");
	$check(validAccountName(post("name")), "Invalid account name.");
	$check(!reservedAccountName(post("name")), "An account with the chosen name already exists (reserved).");
	
	$check(post("confirm") !== null, null);
	
	startTransaction();
	$accountID = accountingAddAccount($GLOBALS["customersDirectoryAccountID"], $GLOBALS["defaultCurrencyID"], post("name"), customerAccountDescription(post("name"), post("initials"), post("lastName")), false);
	$customerID = stdNew("adminCustomer", array("accountID"=>$accountID, "name"=>post("name"), "initials"=>post("initials"), "lastName"=>post("lastName"), "companyName"=>$companyName, "address"=>post("address"), "postalCode"=>post("postalCode"), "city"=>post("city"), "countryCode"=>post("countryCode"), "email"=>post("email"), "phoneNumber"=>post("phoneNumber"), "invoiceFrequencyBase"=>post("invoiceFrequencyBase"), "invoiceFrequencyMultiplier"=>post("invoiceFrequencyMultiplier")));
	commitTransaction();
	
	redirect("customers/customer.php?id=$customerID");
}

main();

?>