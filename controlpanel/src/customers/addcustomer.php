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
	
	$check(!$GLOBALS["database"]->stdExists("adminCustomer", array("name"=>post("name")), "customerID"), "A customer with the chosen name already exists");
	$check(validAccountName(post("name")), "Invalid account name.");
	$check(!reservedAccountName(post("name")), "An account with the chosen name already exists (reserved).");
	$check($GLOBALS["database"]->stdGetTry("adminUser", array("username"=>post("name")), "customerID", false) === false, "An account with the chosen name already exists.");
	
	$password = checkPassword($check, "password");
	
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->startTransaction();
	$customerID = $GLOBALS["database"]->stdNew("adminCustomer", array("name"=>post("name"), "initials"=>post("initials"), "lastName"=>post("lastName"), "companyName"=>$companyName, "address"=>post("address"), "postalCode"=>post("postalCode"), "city"=>post("city"), "countryCode"=>post("countryCode"), "email"=>post("email"), "phoneNumber"=>post("phoneNumber"), "groupname"=>post("groupname"), "diskQuota"=>$diskQuota, "mailQuota"=>$mailQuota, "fileSystemID"=>post("fileSystemID"), "mailSystemID"=>post("mailSystemID"), "nameSystemID"=>post("nameSystemID"), "invoiceFrequencyBase"=>post("invoiceFrequencyBase"), "invoiceFrequencyMultiplier"=>post("invoiceFrequencyMultiplier")));
	foreach(rights() as $right) {
		if(post("right-" . $right["name"]) !== null) {
			$GLOBALS["database"]->stdNew("adminCustomerRight", array("customerID"=>$customerID, "right"=>$right["name"]));
		}
	}
	
	$accountID = $GLOBALS["database"]->stdNew("adminUser", array("customerID"=>$customerID, "username"=>post("name"), "password"=>hashPassword($password)));
	$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$accountID, "customerRightID"=>null));
	$GLOBALS["database"]->commitTransaction();
	
	domainsUpdateContactInfo($customerID);
	
	redirect("customers/customer.php?id=$customerID");
}

main();

?>