<?php

require_once("common.php");

function main()
{
	$supplierID = get("id");
	doAccountingSupplier($supplierID);
	
	acceptFile("file");
	
	$check = function($condition, $error) use($supplierID) {
		if(!$condition) die(page(makeHeader("Add invoice", supplierBreadcrumbs($supplierID), crumbs("Add invoice", "addinvoice.php?id=$supplierID")) . addSupplierInvoiceForm($supplierID, $error, $_POST)));
	};
	
	$check(($invoiceNumber = post("invoiceNumber")) !== null, "");
	$check(($date = post("date")) !== null, "");
	
	
	$check($invoiceNumber != "", "Missing invoice number.");
	$check($date != "", "Missing date.");
	
	
	
	$check(post("confirm") !== null, null);
	
	
	//$newAccountID = accountingAddAccount($accountID == 0 ? null : $accountID, $currencyID, $name, $description, $isDirectory);
	
	redirect("accounting/invoice.php?id=$newInvoiceID");
}

main();

?>