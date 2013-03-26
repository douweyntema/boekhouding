<?php

require_once("common.php");

function main()
{
	$supplierID = get("id");
	doAccountingSupplier($supplierID);
	
	$check = function($condition, $error) use($supplierID) {
		if(!$condition) die(page(makeHeader("Delete supplier", supplierBreadcrumbs($supplierID), crumbs("Delete supplier", "deletesupplier.php?id=$supplierID")) . deleteSupplierForm($supplierID, $error, $_POST)));
	};
	
	$check(supplierEmpty($supplierID), "Supplier is still in use.");
	$check(post("confirm") !== null, null);
	
	startTransaction();
	$accountID = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "accountID");
	stdDel("suppliersSupplier", array("supplierID"=>$supplierID));
	accountingDeleteAccount($accountID);
	commitTransaction();
	
	redirect("accounting/index.php");
}

main();

?>