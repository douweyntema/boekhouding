<?php

require_once("common.php");

function main()
{
	$invoiceID = get("id");
	doAccountingInvoice($invoiceID);
	
	acceptFile("file");
	
	$check = function($condition, $error, $total = null, $balance = null) use($invoiceID) {
		if(!$condition) die(page(makeHeader("Add invoice", suppliersInvoiceBreadcrumbs($invoiceID), crumbs("Edit invoice", "editsupplierinvoice.php?id=$invoiceID")) . editSupplierInvoiceForm($invoiceID, $error, $_POST, $total, $balance)));
	};
	
	$check(($invoiceNumber = post("invoiceNumber")) !== null, "Missing invoice number.");
	$check(($date = parseDate(post("date"))) !== null, "Missing date.");
	$description = post("description");
	$check(($taxAmount = parsePrice(post("taxAmount"))) !== null, "Invalid tax amount.");
	$check($taxAmount >= 0, "Invalid tax amount.");
	$pdfType = post("pdfType");
	
	$supplierID = stdGet("suppliersInvoice", array("invoiceID"=>$invoiceID), "supplierID");
	$accountID = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "accountID");
	$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	if($currencyID != $GLOBALS["defaultCurrencyID"]) {
		$check(($foreignAmount = parsePrice(post("foreignAmount"))) !== null, "Invalid total amount.");
	}
	$transactionID = stdGet("suppliersInvoice", array("invoiceID"=>$invoiceID), "transactionID");
	
	$lines = parseArrayField($_POST, array("accountID", "amount"));
	$parsedLines = array();
	foreach($lines as $line) {
		$amount = parsePrice($line["amount"]);
		if($line["accountID"] == "" && $amount != 0) {
			$check(false, "No account selected.");
		}
		if($amount == 0) {
			continue;
		}
		$check(stdExists("accountingAccount", array("accountID"=>$line["accountID"])), "Invalid account.");
		$parsedLines[] = array("accountID"=>$line["accountID"], "amount"=>$amount);
	}
	$check(count($parsedLines) > 0, "No lines selected.");
	$parsedLines[] = array("accountID"=>$GLOBALS["taxReceivableAccountID"], "amount"=>$taxAmount);
	
	$total = 0;
	foreach($parsedLines as $line) {
		$total += $line["amount"];
	}
	if($currencyID != $GLOBALS["defaultCurrencyID"]) {
		$parsedLines[] = array("accountID"=>$accountID, "amount"=>-$foreignAmount);
		$balance = accountingTransactionBalance($parsedLines);
	} else {
		$parsedLines[] = array("accountID"=>$accountID, "amount"=>-$total);
		$balance = null;
	}
	
	$check(post("confirm") !== null, null, $total, $balance);
	
	if($pdfType == "new") {
		$file = parseFile($_POST, "file");
	}
	
	startTransaction();
	accountingEditTransaction($transactionID, $date, $description, $parsedLines);
	if($pdfType == "current") {
		stdSet("suppliersInvoice", array("invoiceID"=>$invoiceID), array("supplierID"=>$supplierID, "transactionID"=>$transactionID, "invoiceNumber"=>$invoiceNumber));	
	} else {
		stdSet("suppliersInvoice", array("invoiceID"=>$invoiceID), array("supplierID"=>$supplierID, "transactionID"=>$transactionID, "invoiceNumber"=>$invoiceNumber, "pdf"=>($pdfType == "new" ? $file["data"] : null)));
	}
	commitTransaction();
	
	redirect("accounting/supplierinvoice.php?id=$invoiceID");
}

main();

?>