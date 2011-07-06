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
	
	$usernameHtml = htmlentities(username());
	$customerHtml = htmlentities($customer["name"]);
	
	$content = "<h1>Customers - $customerHtml</h1>\n";
	$content .= breadcrumbs(array(
		array("name"=>"Customers", "url"=>"{$GLOBALS["root"]}customers/"),
		array("name"=>$customer["name"], "url"=>"{$GLOBALS["root"]}customers/customer.php?id=" . $customerID)
		));
	
	$content .= <<<HTML
<div class="operation">
<h2>Login for this customer</h2>
<p><a href="{$GLOBALS["rootHtml"]}index.php?customerID=$customerID">Login as $usernameHtml@$customerHtml</a></p>
</div>

HTML;
	
	$content .= editCustomerForm($customerID, "", $customer["name"], $customer["realname"], $customer["email"]);
	$content .= editCustomerRightsForm($customerID);
	
	echo page($content);
}

main();

?>