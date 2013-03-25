<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doCustomer($customerID);
	
	$customerName = stdGet("adminCustomer", array("customerID"=>$customerID), "name");
	$customerNameHtml = htmlentities($customerName);
	
	$check = function($condition, $error) use($customerID, $customerNameHtml) {
		if(!$condition) die(page(makeHeader("Customers - $customerNameHtml", customerBreadcrumbs($customerID), crumbs("Edit customer", "editcustomer.php?id=$customerID")) . editCustomerForm($customerID, $error, $_POST)));
	};
	$notempty = function($field) use($check) {
		$check(trim(post($field)) != "", "Invalid $field");
	};
	
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
	
	$check(post("confirm") !== null, null);
	
	stdSet("adminCustomer", array("customerID"=>$customerID), array("initials"=>post("initials"), "lastName"=>post("lastName"), "companyName"=>$companyName, "address"=>post("address"), "postalCode"=>post("postalCode"), "city"=>post("city"), "countryCode"=>post("countryCode"), "email"=>post("email"), "phoneNumber"=>post("phoneNumber"), "diskQuota"=>$diskQuota, "mailQuota"=>$mailQuota, "invoiceFrequencyBase"=>post("invoiceFrequencyBase"), "invoiceFrequencyMultiplier"=>post("invoiceFrequencyMultiplier"), "mijnDomeinResellerContactID"=>null));
	
	updateMail($customerID);
	updateDomains($customerID);
	domainsUpdateContactInfo($customerID);
	
	redirect("customers/customer.php?id=$customerID");
}

main();

?>