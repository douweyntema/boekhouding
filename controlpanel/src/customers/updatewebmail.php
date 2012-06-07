<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doCustomer($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader("Update webmail", customerBreadcrumbs($customerID), crumbs("Update webmail", "updatewebmail.php?id=$customerID")) . editCustomerWebmail($customerID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdSet("adminCustomer", array("customerID"=>$customerID), array("webmail"=>post("webmail") == "" ? null : post("webmail")));
	
	redirect("customers/customer.php?id=$customerID");
}

main();

?>