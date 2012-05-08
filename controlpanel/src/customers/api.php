<?php

$customersTitle = "Customers";
$customersTarget = "admin";

function customersOverview()
{
	if(customerID() == 0) {
		return;
	}
	$customer = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>customerID()), array("name", "companyName", "initials", "lastName", "address", "postalCode", "city", "countryCode", "email", "phoneNumber", "diskQuota", "mailQuota"));
	return summaryTable("Your information", array(
		"Name"=>$customer["initials"] . " " . $customer["lastName"],
		$customer["companyName"] === null ? null : "Company Name"=>$customer["companyName"],
		"Address"=>$customer["address"] . "\n" . $customer["postalCode"] . " " . $customer["city"] . "\n" . countryName($customer["countryCode"]),
		"Email"=>$customer["email"],
		"Phone number"=>$customer["phoneNumber"],
		"Account name"=>$customer["name"],
		"Disk quota"=>$customer["diskQuota"] === null ? "unlimited" : $customer["diskQuota"] . " MB",
		"Mail quota"=>$customer["mailQuota"] === null ? "unlimited" : $customer["mailQuota"] . " MB"
	));
}

?>