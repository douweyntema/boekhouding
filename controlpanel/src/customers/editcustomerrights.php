<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doCustomer($customerID);
	
	$customerName = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "name");
	$customerNameHtml = htmlentities($customerName);
	
	$check = function($condition, $error) use($customerID, $customerNameHtml) {
		if(!$condition) die(page(makeHeader("Customers - $customerNameHtml", customerBreadcrumbs($customerID), crumbs("Edit customer rights", "editcustomerrights.php?id=$customerID")) . editCustomerRightsForm($customerID, $error, $_POST)));
	};
	
	$rights = array();
	foreach(rights() as $right) {
		if(post("right-" . $right["name"]) !== null) {
			$rights[$right["name"]] = true;
		} else {
			$rights[$right["name"]] = false;
		}
	}
	
	$check(post("posted") !== null, "");
	$check(post("confirm") !== null, null);
	
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
			// No change, customer already doesn't have the right
		}
	}
	$GLOBALS["database"]->commitTransaction();
	
	// Distribute the accounts database
	updateAccounts($customerID);
	
	redirect("customers/customer.php?id=$customerID");
}

main();

?>