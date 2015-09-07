<?php

require_once("common.php");

function main()
{
	$supplierID = get("id");
	doAccountingSupplier($supplierID);
	
	acceptFile("file");
	
	$check = function($condition, $error, $total = null, $balance = null) use($supplierID) {
		if(!$condition) die(page(makeHeader(_("Add invoice"), supplierBreadcrumbs($supplierID), crumbs(_("Add invoice"), "addsupplierinvoice.php?id=$supplierID")) . addSupplierInvoiceForm($supplierID, $error, $_POST, $total, $balance)));
	};
	$check(($invoiceNumber = post("invoiceNumber")) !== null, _("Missing invoice number."));
	$check($invoiceNumber != "", _("Missing invoice number."));
	$description = post("description");
	if($description == "") {
		$description = sprintf(_('Invoice %1$s from supplier %2$s'), $invoiceNumber, stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "name"));
		$_POST["description"] = $description;
	}
	$check(($date = parseDate(post("date"))) !== null, _("Missing date."));
	$check(($taxAmount = parsePrice(post("taxAmount"))) !== null, _("Invalid tax amount."));
	$pdfType = post("pdfType");
	
	$accountID = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "accountID");
	$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	if($currencyID != $GLOBALS["defaultCurrencyID"]) {
		$check(($foreignAmount = parsePrice(post("foreignAmount"))) !== null, _("Invalid total amount."));
	}
	
	$lines = parseArrayField($_POST, array("accountID", "amount"));
	$parsedLines = array();
	foreach($lines as $line) {
		$amount = parsePrice($line["amount"]);
		if($line["accountID"] == "" && $amount != 0) {
			$check(false, _("No account selected."));
		}
		if($amount == 0) {
			continue;
		}
		$check(stdExists("accountingAccount", array("accountID"=>$line["accountID"])), _("Invalid account."));
		$parsedLines[] = array("accountID"=>$line["accountID"], "amount"=>$amount);
	}
	$check(count($parsedLines) > 0, _("No lines selected."));
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
	
	if(post("payment") == "yes") {
		$payment = true;
		$paymentDescription = sprintf(_("Payment for %s"), $invoiceNumber);
		$check(($paymentDate = parseDate(trim(post("paymentDate")) == "" ? post("date") : post("paymentDate"))) !== null, _("Invalid date."));
		$check(($paymentBankAccount = post("paymentBankAccount")) !== null, _("Invalid bank account."));
		$check(stdExists("accountingAccount", array("accountID"=>$paymentBankAccount)), _("Invalid bank account."));
		if($currencyID != $GLOBALS["defaultCurrencyID"]) {
			
		}
		$paymentLines = array();
		$paymentLines[] = array("accountID"=>$paymentBankAccount, "amount"=>$total * -1);
		if($currencyID != $GLOBALS["defaultCurrencyID"]) {
			$paymentLines[] = array("accountID"=>$accountID, "amount"=>$foreignAmount);
		} else {
			$paymentLines[] = array("accountID"=>$accountID, "amount"=>$total);
		}
	} else {
		$payment = false;
	}
	
	$check(post("confirm") !== null, null, $total, $balance);
	
	if($pdfType == "new") {
		$file = parseFile($_POST, "file");
	} else {
		$file = null;
	}
	
	startTransaction();
	$transactionID = accountingAddTransaction($date, $description, $parsedLines);
	$invoiceID = stdNew("suppliersInvoice", array("supplierID"=>$supplierID, "transactionID"=>$transactionID, "invoiceNumber"=>$invoiceNumber, "pdf"=>($file === null ? null : $file["data"])));
	commitTransaction();
	
	if($payment) {
		startTransaction();
		$transactionID = accountingAddTransaction($paymentDate, $paymentDescription, $paymentLines);
		stdNew("suppliersPayment", array("supplierID"=>$supplierID, "transactionID"=>$transactionID));
		commitTransaction();
	}
	
	redirect("accounting/supplier.php?id=$supplierID");
}

main();

?>