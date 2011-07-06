<?php

require_once("common.php");

function main()
{
	doCustomers();
	
	$customerID = get("id");
	$customer = $GLOBALS["database"]->stdGetTry("adminCustomer", array("customerID"=>$customerID), array("name", "realname", "email"), false);
	
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
	
	if($realname === null || $email === null) {
		$content .= editCustomerForm($customerID, "", $customer["realname"], $customer["email"]);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= editCustomerForm($customerID, null, $realname, $email);
		die(page($content));
	}
	
	var_dump($realname);
	
	$GLOBALS["database"]->stdSet("adminCustomer", array("customerID"=>$customerID), array("realname"=>$realname, "email"=>$email));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}customers/customer.php?id=$customerID");
}

main();

?>