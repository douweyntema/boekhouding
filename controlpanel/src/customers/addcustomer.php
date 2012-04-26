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
	$notempty("country");
	$notempty("email");
	$notempty("groupname");
	$notempty("invoiceFrequencyMultiplier");
	$notempty("invoiceFrequencyBase");
	
	$check($GLOBALS["database"]->stdExists("infrastructureFileSystem", array("fileSystemID"=>post("fileSystemID"))), "Invalid filesystem");
	$check($GLOBALS["database"]->stdExists("infrastructureMailSystem", array("mailSystemID"=>post("mailSystemID"))), "Invalid mailsystem");
	$check($GLOBALS["database"]->stdExists("infrastructureNameSystem", array("nameSystemID"=>post("nameSystemID"))), "Invalid namesystem");
	
	$check(ctype_digit(post("invoiceFrequencyMultiplier")), "Invalid invoiceFrequencyMultiplier");
	$check(post("invoiceFrequencyBase") == "DAY" || post("invoiceFrequencyBase") == "MONTH" || post("invoiceFrequencyBase") == "YEAR", "Invalid invoiceFrequencyBase");
	
	$diskQuota = post("diskQuota");
	$mailQuota = post("mailQuota");
	$companyName = post("companyName");
	
	if($diskQuota == "") {
		$diskQuota = null;
	}
	if($mailQuota == "") {
		$mailQuota = null;
	}
	if($companyName == "") {
		$companyName = null;
	}
	
	$check($diskQuota === null || ctype_digit($diskQuota), "Invalid disk quota");
	$check($mailQuota === null || ctype_digit($mailQuota), "Invalid mail quota");
	
	$check(!$GLOBALS["database"]->stdExists("adminCustomer", array("name"=>post("nickname")), "customerID"), "A customer with the chosen nickname already exists");
	
	$check(post("confirm") !== null, null);
	
	$customerID = $GLOBALS["database"]->stdNew("adminCustomer", array("name"=>post("name"), "initials"=>post("initials"), "lastName"=>post("lastName"), "companyName"=>$companyName, "address"=>post("address"), "postalCode"=>post("postalCode"), "city"=>post("city"), "countryCode"=>post("country"), "email"=>post("email"), "phoneNumber"=>post("phoneNumber"), "groupname"=>post("groupname"), "diskQuota"=>$diskQuota, "mailQuota"=>$mailQuota, "fileSystemID"=>post("fileSystemID"), "mailSystemID"=>post("mailSystemID"), "nameSystemID"=>post("nameSystemID")));
	
	domainsUpdateContactInfo($customerID);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}customers/customer.php?id=$customerID");
}

main();

?>