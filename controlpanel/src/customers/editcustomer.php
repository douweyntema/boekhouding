<?php

require_once("common.php");

function main()
{
	doCustomers();
	
	$customerID = get("id");
	$customer = $GLOBALS["database"]->stdGetTry("adminCustomer", array("customerID"=>$customerID), array("name", "initials", "lastName", "companyName", "address", "postalCode", "city", "countryCode", "email", "phoneNumber", "groupname", "diskQuota", "mailQuota"), false);
	
	if($customer === false) {
		customerNotFound($customerID);
	}
	
	$customerHtml = htmlentities($customer["name"]);
	
	$content = "<h1>Customers - $customerHtml</h1>\n";
	
	$content .= breadcrumbs(array(
		array("name"=>"Customers", "url"=>"{$GLOBALS["root"]}customers/"),
		array("name"=>$customer["name"], "url"=>"{$GLOBALS["root"]}customers/customer.php?id=" . $customerID),
		array("name"=>"Edit customer", "url"=>"{$GLOBALS["root"]}customers/editcustomer.php?id=" . $customerID)
		));
	
	$initials = post("customerInitials");
	$lastName = post("customerLastName");
	$companyName = post("customerCompanyName");
	$address = post("customerAddress");
	$postalCode = post("customerPostalCode");
	$city = post("customerCity");
	$countryCode = post("customerCountryCode");
	$email = post("customerEmail");
	$phoneNumber = post("customerPhoneNumber");
	$diskQuota = post("diskQuota");
	$mailQuota = post("mailQuota");
	
	if($diskQuota == "") {
		$diskQuota = null;
	}
	if($mailQuota == "") {
		$mailQuota = null;
	}
	
	if($initials === null || $lastName === null || $email === null) {
		$content .= editCustomerForm($customerID, "", $customer["initials"], $customer["lastName"], $customer["companyName"], $customer["address"], $customer["postalCode"], $customer["city"], $customer["countryCode"], $customer["email"], $customer["phoneNumber"], $customer["diskQuota"], $customer["mailQuota"]);
		die(page($content));
	}
	
	if(($diskQuota !== null && !ctype_digit($diskQuota)) || ($mailQuota !== null && !ctype_digit($mailQuota))) {
		$content .= editCustomerForm($customerID, "Invalid quota", $initials, $lastName, $companyName, $address, $postalCode, $city, $countryCode, $email, $phoneNumber, $diskQuota, $mailQuota);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= editCustomerForm($customerID, null, $initials, $lastName, $companyName, $address, $postalCode, $city, $countryCode, $email, $phoneNumber, $diskQuota, $mailQuota);
		die(page($content));
	}
	
	$GLOBALS["database"]->stdSet("adminCustomer", array("customerID"=>$customerID), array("initials"=>$initials, "lastName"=>$lastName, "companyName"=>$companyName, "address"=>$address, "postalCode"=>$postalCode, "city"=>$city, "countryCode"=>$countryCode, "email"=>$email, "phoneNumber"=>$phoneNumber, "diskQuota"=>$diskQuota, "mailQuota"=>$mailQuota, "mijnDomeinResellerContactID"=>null));
	
	updateMail($customerID);
	updateDomains($customerID);
	domainsUpdateContactInfo($customerID);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}customers/customer.php?id=$customerID");
}

main();

?>