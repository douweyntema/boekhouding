<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doCustomer($customerID);
	
	$customerName = stdGet("adminCustomer", array("customerID"=>$customerID), "name");
	$customerNameHtml = htmlentities($customerName);
	
	$check = function($condition, $error) use($customerID, $customerNameHtml) {
		if(!$condition) die(page(makeHeader("Customers - $customerNameHtml", customerBreadcrumbs($customerID), crumbs(_("Edit customer"), "editcustomer.php?id=$customerID")) . editCustomerForm($customerID, $error, $_POST)));
	};
	$notempty = function($field) use($check) {
		$check(trim(post($field)) != "", sprintf(_("Invalid %s"), $field));
	};
	
// 	$notempty("initials");
	$notempty("lastName");
// 	$notempty("address");
// 	$notempty("postalCode");
// 	$notempty("city");
// 	$notempty("countryCode");
	$notempty("email");
	$notempty("invoiceFrequencyMultiplier");
	$notempty("invoiceFrequencyBase");
	
	$check(ctype_digit(post("invoiceFrequencyMultiplier")), _("Invalid invoiceFrequencyMultiplier"));
	$check(post("invoiceFrequencyBase") == "DAY" || post("invoiceFrequencyBase") == "MONTH" || post("invoiceFrequencyBase") == "YEAR", _("Invalid invoiceFrequencyBase"));
	$check(post("btwStatus") == "excludingBTW" || post("btwStatus") == "includingBTW", _("Invalid BTW status"));
	
	$companyName = post("companyName");
	
	if($companyName == "") {
		$companyName = null;
	}
	
	$check(post("confirm") !== null, null);
	
	startTransaction();
	$accountID = stdGet("adminCustomer", array("customerID"=>$customerID), "accountID");
	accountingEditAccount($accountID, $customerName, customerAccountDescription(post("name"), post("initials"), post("lastName")));
	stdSet("adminCustomer", array("customerID"=>$customerID), array("initials"=>post("initials"), "lastName"=>post("lastName"), "companyName"=>$companyName, "address"=>post("address"), "postalCode"=>post("postalCode"), "city"=>post("city"), "countryCode"=>post("countryCode"), "email"=>post("email"), "phoneNumber"=>post("phoneNumber"), "invoiceFrequencyBase"=>post("invoiceFrequencyBase"), "invoiceFrequencyMultiplier"=>post("invoiceFrequencyMultiplier"), "btwStatus"=>post("btwStatus")));
	commitTransaction();
	
	redirect("customers/customer.php?id=$customerID");
}

main();

?>