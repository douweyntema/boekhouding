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
		$componentID = $component["componentID"];
		if(isset($_POST["right" . $componentID])) {
			$rights[$componentID] = true;
		} else {
			$rights[$componentID] = false;
		}
	}
	
	$customerHtml = htmlentities($customer["name"]);
	
	$content = "<h1>Customers - $customerHtml</h1>\n";
	$content .= breadcrumbs(array(
		array("name"=>"Customers", "url"=>"{$GLOBALS["root"]}customers/"),
		array("name"=>$customer["name"], "url"=>"{$GLOBALS["root"]}customers/customer.php?id=" . $customerID),
		array("name"=>"Edit customer rights", "url"=>"{$GLOBALS["root"]}customers/editcustomerrights.php?id=" . $customerID)
		));
	
	if(!isset($_POST["posted"])) {
		$content .= editCustomerRightsForm($customerID);
		die(page($content));
	}
	
	if(!isset($_POST["confirm"])) {
		$content .= editCustomerRightsForm($customerID, null, $rights);
		die(page($content));
	}
	
	$GLOBALS["database"]->stdDel("adminCustomerRight", array("customerID"=>$customerID));
	foreach($components as $component) {
		$componentID = $component["componentID"];
		if($rights[$componentID] && $component["rootOnly"] == 0) {
			$GLOBALS["database"]->stdNew("adminCustomerRight", array("componentID"=>$componentID, "customerID"=>$customerID));
		} else {
			foreach($GLOBALS["database"]->stdList("adminUser", array("customerID"=>$customerID), "userID") as $userID) {
				$GLOBALS["database"]->stdDel("adminUserRight", array("userID"=>$userID, "componentID"=>$componentID));
			}
		}
	}
	
	// Distribute the accounts database
	updateAccounts($customerID);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}customers/customer.php?id=$customerID");
}

main();

?>