<?php

require_once("common.php");
doCustomers(null);

function main()
{
	$content = "<h1>Customers</h1>\n";
	
	$name = "";
	$email = "";
	if(isset($_POST["customerName"])) {
		$name = $_POST["customerName"];
	}
	if(isset($_POST["customerEmail"])) {
		$email = $_POST["customerEmail"];
	}
	
	if(trim($name) == "") {
		$content .= addCustomerForm("", $name, $email);
		die(page($content));
	}
	
	$exists = $GLOBALS["database"]->stdGetTry("adminCustomer", array("name"=>$name), "customerID", false) !== false;
	if($exists) {
		$content .= addCustomerForm("A customer with the chosen name already exists.", $name, $email);
		die(page($content));
	}
	
	if(!isset($_POST["confirm"])) {
		$content .= addCustomerForm(null, $name, $email);
		die(page($content));
	}
	
	$customerID = $GLOBALS["database"]->stdNew("adminCustomer", array("name"=>$name, "email"=>$email));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}customers/customer.php?id=$customerID");
}

main();

?>