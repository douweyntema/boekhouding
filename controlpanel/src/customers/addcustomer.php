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
	
	$name = post("customerName");
	$email = post("customerEmail");
	
	if(trim($name) == "") {
		$content .= addCustomerForm("", $name, $email);
		die(page($content));
	}
	
	$exists = $GLOBALS["database"]->stdGetTry("adminCustomer", array("name"=>$name), "customerID", false) !== false;
	if($exists) {
		$content .= addCustomerForm("A customer with the chosen name already exists.", $name, $email);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= addCustomerForm(null, $name, $email);
		die(page($content));
	}
	
	$customerID = $GLOBALS["database"]->stdNew("adminCustomer", array("name"=>$name, "email"=>$email));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}customers/customer.php?id=$customerID");
}

main();

?>