<?php

require_once("common.php");

function main()
{
	$supplierID = get("id");
	doAccountingSupplier($supplierID);
	
	$check = function($condition, $error) use($supplierID) {
		if(!$condition) die(page(makeHeader("Edit supplier", supplierBreadcrumbs($supplierID), crumbs("Edit supplier", "editsupplier.php?id=$supplierID")) . editSupplierForm($supplierID, $error, $_POST)));
	};
	
	$check(($name = post("name")) !== null, "");
	$check(($defaultExpenseAccountID = post("defaultExpenseAccountID")) !== null, "");
	
	$check($name != "", "Missing supplier name.");
	$check($defaultExpenseAccountID == "" || stdGetTry("accountingAccount", array("accountID"=>$defaultExpenseAccountID), "isDirectory", "1") == "0", "Invalid expense account.");
	$check(post("confirm") !== null, null);
	
	startTransaction();
	stdSet("suppliersSupplier", array("supplierID"=>$supplierID), array("name"=>$name, "defaultExpenseAccountID"=>($defaultExpenseAccountID == "" ? null : $defaultExpenseAccountID), "description"=>post("description")));
	$accountID = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "accountID");
	stdSet("accountingAccount", array("accountID"=>$accountID), array("name"=>$name, "description"=>supplierAccountDescription($name)));
	commitTransaction();
	
	redirect("accounting/supplier.php?id=$supplierID");
}

main();

?>