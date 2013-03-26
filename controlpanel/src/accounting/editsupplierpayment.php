<?php

require_once("common.php");

function main()
{
	$paymentID = get("id");
	$payment = stdGet("suppliersPayment", array("paymentID"=>$paymentID), array("supplierID", "transactionID"));
	$supplierID = $payment["supplierID"];
	
	doAccountingSupplier($supplierID);
	
	$check = function($condition, $error, $balance = null) use($supplierID, $paymentID) {
		if(!$condition) die(page(makeHeader("Edit payment", supplierBreadcrumbs($supplierID), crumbs("Edit payment", "editsupplierpayment.php?id=$paymentID")) . editSupplierPaymentForm($paymentID, $error, $_POST, $balance)));
	};
	
	$check(($description = post("description")) != "", "No description given.");
	$check(($date = parseDate(post("date"))) !== null, "Invalid date.");
	$check(($amount = parsePrice(post("amount"))) !== null, "Invalid amount.");
	$check($amount > 0, "Invalid amount.");
	$check(($paymentAccount = post("paymentAccount")) !== null, "Invalid payment account.");
	$check(stdExists("accountingAccount", array("accountID"=>$paymentAccount)), "Invalid payment account.");
	
	$accountID = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "accountID");
	$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	
	if($GLOBALS["defaultCurrencyID"] != $currencyID) {
		$check(($supplierAmount = parsePrice(post("foreignAmount"))) !== null, "Invalid amount.");
		$check($supplierAmount > 0, "Invalid amount.");
	} else {
		$supplierAmount = $amount;
	}
	
	$lines = array(
		array("accountID"=>$paymentAccount, "amount"=>$amount * -1),
		array("accountID"=>$accountID, "amount"=>$supplierAmount),
	);
	
	$balance = accountingTransactionBalance($lines);
	$check($balance !== false, "No valid transaction lines.");
	$check(post("confirm") !== null, null, $balance);
	
	accountingEditTransaction($payment["transactionID"], $date, $description, $lines);
	
	redirect("accounting/supplier.php?id=$supplierID");
}

main();

?>