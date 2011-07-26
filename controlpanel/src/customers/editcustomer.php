<?php

require_once("common.php");

function main()
{
	doCustomers();
	
	$customerID = get("id");
	$customer = $GLOBALS["database"]->stdGetTry("adminCustomer", array("customerID"=>$customerID), array("name", "realname", "email", "mailQuota"), false);
	
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
	
	$realname = post("customerName");
	$email = post("customerEmail");
	$mailQuota = post("mailQuota");
	
	if($realname === null || $email === null || $mailQuota === null) {
		$content .= editCustomerForm($customerID, "", $customer["realname"], $customer["email"], $customer["mailQuota"]);
		die(page($content));
	}
	
	if(!is_numeric($mailQuota) || $mailQuota < 1) {
		$content .= editCustomerForm($customerID, "Invalid mail quota", $realname, $email, $mailQuota);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= editCustomerForm($customerID, null, $realname, $email, $mailQuota);
		die(page($content));
	}
	
	var_dump($realname);
	
	$GLOBALS["database"]->stdSet("adminCustomer", array("customerID"=>$customerID), array("realname"=>$realname, "email"=>$email, "mailQuota"=>$mailQuota));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}customers/customer.php?id=$customerID");
}

main();

?>