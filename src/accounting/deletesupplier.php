<?php

require_once("common.php");

function main()
{
	$supplierID = get("id");
	doAccountingSupplier($supplierID);
	
	$check = function($condition, $error) use($supplierID) {
		if(!$condition) die(page(makeHeader(_("Delete supplier"), supplierBreadcrumbs($supplierID), crumbs(_("Delete supplier"), "deletesupplier.php?id=$supplierID")) . deleteSupplierForm($supplierID, $error, $_POST)));
	};
	
	$check(supplierEmpty($supplierID), _("Supplier is still in use."));
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