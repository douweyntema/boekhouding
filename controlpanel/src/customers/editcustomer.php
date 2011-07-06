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
	
	$nickname = post("customerNickname");
	$name = post("customerName");
	$email = post("customerEmail");
	
	if($nickname === null || $name === null || $email === null) {
		$content .= editCustomerForm($customerID, "", $customer["name"], $customer["realname"], $customer["email"]);
		die(page($content));
	}
	
	$oldCustomerID = $GLOBALS["database"]->stdGetTry("adminCustomer", array("name"=>$name), "customerID", false);
	$exists = ($oldCustomerID !== false && $oldCustomerID != $customerID);
	
	if($exists) {
		$content .= editCustomerForm($customerID, "A customer with the chosen name already exists.", $name, $email);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= editCustomerForm($customerID, null, $nickname, $name, $email);
		die(page($content));
	}
	
	$GLOBALS["database"]->stdSet("adminCustomer", array("customerID"=>$customerID), array("name"=>$nickname, "realname"=>$name, "email"=>$email));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}customers/customer.php?id=$customerID");
}

main();

?>