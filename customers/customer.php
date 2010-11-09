<?php

require_once("common.php");
doCustomers($_GET["id"]);

function main()
{
	$customerID = $_GET["id"];
	$customer = $GLOBALS["database"]->stdGetTry("adminCustomer", array("customerID"=>$customerID), array("name", "email"), false);
	
	if($customer === false) {
		customerNotFound($customerID);
	}
	
	$components = components();
	$rights = array();
	foreach($components as $component) {
		$rights[$component["componentID"]] = false;
	}
	foreach($GLOBALS["database"]->stdList("adminCustomerRight", array("customerID"=>$customerID), "componentID") as $componentID) {
		$rights[$componentID] = true;
	}
	
	$usernameHtml = htmlentities(username());
	$customerHtml = htmlentities($customer["name"]);
	
	$content = "<h1>Customers - $customerHtml</h1>\n";
	
	$content .= <<<HTML
<div class="operation">
<h2>Login for this customer</h2>
<p><a href="{$GLOBALS["rootHtml"]}index.php?customerID=$customerID">Login as $usernameHtml@$customerHtml</a></p>
</div>

HTML;
	
	$content .= editCustomerForm($customerID, "", $customer["name"], $customer["email"]);
	$content .= editCustomerRightsForm($customerID, "", $rights);
	
	echo page($content);
}

main();

?>