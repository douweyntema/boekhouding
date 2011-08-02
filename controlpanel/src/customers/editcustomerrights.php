<?php

require_once("common.php");

function main()
{
	doCustomers();
	
	$customerID = get("id");
	$customer = $GLOBALS["database"]->stdGetTry("adminCustomer", array("customerID"=>$customerID), array("name", "email"), false);
	
	if($customer === false) {
		customerNotFound($customerID);
	}
	
	$rights = array();
	foreach(rights() as $right) {
		if(post("right-" . $right["name"]) !== null) {
			$rights[$right["name"]] = true;
		} else {
			$rights[$right["name"]] = false;
		}
	}
	
	$customerHtml = htmlentities($customer["name"]);
	
	$content = "<h1>Customers - $customerHtml</h1>\n";
	$content .= breadcrumbs(array(
		array("name"=>"Customers", "url"=>"{$GLOBALS["root"]}customers/"),
		array("name"=>$customer["name"], "url"=>"{$GLOBALS["root"]}customers/customer.php?id=" . $customerID),
		array("name"=>"Edit customer rights", "url"=>"{$GLOBALS["root"]}customers/editcustomerrights.php?id=" . $customerID)
		));
	
	if(post("posted") === null) {
		$content .= editCustomerRightsForm($customerID);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= editCustomerRightsForm($customerID, null, $rights);
		die(page($content));
	}
	
	$GLOBALS["database"]->startTransaction();
	foreach(rights() as $right) {
		$customerRightID = $GLOBALS["database"]->stdGetTry("adminCustomerRight", array("customerID"=>$customerID, "right"=>$right["name"]), "customerRightID", null);
		if($rights[$right["name"]] && $customerRightID !== null) {
			// No change, customer already has the right
		} else if($rights[$right["name"]] && $customerRightID === null) {
			// Add the right
			$GLOBALS["database"]->stdNew("adminCustomerRight", array("customerID"=>$customerID, "right"=>$right["name"]));
		} else if(!$rights[$right["name"]] && $customerRightID !== null) {
			// Remove the right
			$GLOBALS["database"]->stdDel("adminUserRight", array("customerRightID"=>$customerRightID));
			$GLOBALS["database"]->stdDel("adminCustomerRight", array("customerRightID"=>$customerRightID));
		} else {
			// No change, customer already has the right not
		}
	}
	$GLOBALS["database"]->commitTransaction();
	
	// Distribute the accounts database
	updateAccounts($customerID);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}customers/customer.php?id=$customerID");
}

main();

?>