<?php

require_once("common.php");

function main()
{
	doCustomers();
	
	$content = "<h1>Customers</h1>\n";
	$content .= breadcrumbs(array(
		array("name"=>"Customers", "url"=>"{$GLOBALS["root"]}customers/"),
		array("name"=>"Add customer", "url"=>"{$GLOBALS["root"]}customers/addcustomer.php")
		));
	
	$nickname = post("customerNickname");
	$initials = post("customerInitials");
	$lastName = post("customerLastName");
	$companyName = post("customerCompanyName");
	$address = post("customerAddress");
	$postalCode = post("customerPostalCode");
	$city = post("customerCity");
	$countryCode = post("customerCountryCode");
	$email = post("customerEmail");
	$phoneNumber = post("customerPhoneNumber");
	$group = post("customerGroup");
	$diskQuota = post("diskQuota");
	$mailQuota = post("mailQuota");
	$fileSystemID = post("customerFileSystem");
	$mailSystemID = post("customerMailSystem");
	$nameSystemID = post("customerNameSystem");
	
	if($diskQuota == "") {
		$diskQuota = null;
	}
	if($mailQuota == "") {
		$mailQuota = null;
	}
	
	if(trim($nickname) == "" || trim($initials) == "" || trim($lastName) == "" || trim($email) == "" || trim($group) == "" || $GLOBALS["database"]->stdGetTry("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "fileSystemID", null) === null || $GLOBALS["database"]->stdGetTry("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "mailSystemID", null) === null || $GLOBALS["database"]->stdGetTry("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), "nameSystemID", null) === null) {
		$content .= addCustomerForm("Please fill in more fields", $nickname, $initials, $lastName, $companyName, $address, $postalCode, $city, $countryCode, $email, $phoneNumber, $group, $diskQuota, $mailQuota, $fileSystemID, $mailSystemID, $nameSystemID);
		die(page($content));
	}
	
	if(($diskQuota !== null && !ctype_digit($diskQuota)) || ($mailQuota !== null && !ctype_digit($mailQuota))) {
		$content .= addCustomerForm("Invalid quota", $nickname, $initials, $lastName, $companyName, $address, $postalCode, $city, $countryCode, $email, $phoneNumber, $group, $diskQuota, $mailQuota, $fileSystemID, $mailSystemID, $nameSystemID);
		die(page($content));
	}
	
	if($GLOBALS["database"]->stdGetTry("adminCustomer", array("name"=>$nickname), "customerID", false) !== false) {
		$content .= addCustomerForm("A customer with the chosen nickname already exists.", $nickname, $initials, $lastName, $companyName, $address, $postalCode, $city, $countryCode, $email, $phoneNumber, $group, $diskQuota, $mailQuota, $fileSystemID, $mailSystemID, $nameSystemID);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= addCustomerForm(null, $nickname, $initials, $lastName, $companyName, $address, $postalCode, $city, $countryCode, $email, $phoneNumber, $group, $diskQuota, $mailQuota, $fileSystemID, $mailSystemID, $nameSystemID);
		die(page($content));
	}
	
	$customerID = $GLOBALS["database"]->stdNew("adminCustomer", array("name"=>$nickname, "initials"=>$initials, "lastName"=>$lastName, "companyName"=>$companyName, "address"=>$address, "postalCode"=>$postalCode, "city"=>$city, "countryCode"=>$countryCode, "email"=>$email, "phoneNumber"=>$phoneNumber, "groupname"=>$group, "diskQuota"=>$diskQuota, "mailQuota"=>$mailQuota, "fileSystemID"=>$fileSystemID, "mailSystemID"=>$mailSystemID, "nameSystemID"=>$nameSystemID));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}customers/customer.php?id=$customerID");
}

main();

?>