<?php

require_once("common.php");

function main()
{
	$paymentID = get("id");
	$payment = stdGet("suppliersPayment", array("paymentID"=>$paymentID), array("supplierID", "transactionID"));
	$supplierID = $payment["supplierID"];
	$transactionID = $payment["transactionID"];
	
	doAccountingSupplier($supplierID);
	
	$check = function($condition, $error) use($supplierID, $paymentID) {
		if(!$condition) die(page(makeHeader(_("Delete payment"), supplierBreadcrumbs($supplierID), crumbs(_("Delete payment"), "deletesupplierpayment.php?id=$paymentID")) . deleteSupplierPaymentForm($paymentID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	startTransaction();
	stdDel("suppliersPayment", array("paymentID"=>$paymentID));
	accountingDeleteTransaction($transactionID);
	commitTransaction();
	
	redirect("accounting/supplier.php?id=$supplierID");
}

main();

?>