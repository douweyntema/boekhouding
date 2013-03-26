<?php

require_once("common.php");

function main()
{
	$invoiceID = get("id");
	$invoice = stdGet("suppliersInvoice", array("invoiceID"=>$invoiceID), array("supplierID", "transactionID"));
	$supplierID = $invoice["supplierID"];
	$transactionID = $invoice["transactionID"];
	
	doAccountingSupplier($supplierID);
	
	$check = function($condition, $error) use($supplierID, $invoiceID) {
		if(!$condition) die(page(makeHeader("Delete invoice", supplierBreadcrumbs($supplierID), crumbs("Delete invoice", "deletesupplierinvoice.php?id=$invoiceID")) . deleteSupplierInvoiceForm($invoiceID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	startTransaction();
	stdDel("suppliersInvoice", array("invoiceID"=>$invoiceID));
	accountingDeleteTransaction($transactionID);
	commitTransaction();
	
	redirect("accounting/supplier.php?id=$supplierID");
}

main();

?>