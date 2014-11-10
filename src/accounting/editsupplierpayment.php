<?php

require_once("common.php");

function main()
{
	$paymentID = get("id");
	$payment = stdGet("suppliersPayment", array("paymentID"=>$paymentID), array("supplierID", "transactionID"));
	$supplierID = $payment["supplierID"];
	
	doAccountingSupplier($supplierID);
	
	$check = function($condition, $error, $balance = null) use($supplierID, $paymentID) {
		if(!$condition) die(page(makeHeader(_("Edit payment"), supplierBreadcrumbs($supplierID), crumbs(_("Edit payment"), "editsupplierpayment.php?id=$paymentID")) . editSupplierPaymentForm($paymentID, $error, $_POST, $balance)));
	};
	
	$check(($description = post("description")) != "", _("No description given."));
	$check(($date = parseDate(post("date"))) !== null, _("Invalid date."));
	$check(($amount = parsePrice(post("amount"))) !== null, _("Invalid amount."));
	$check($amount > 0, _("Invalid amount."));
	$check(($bankAccount = post("bankAccount")) !== null, _("Invalid bank account."));
	$check(stdExists("accountingAccount", array("accountID"=>$bankAccount)), _("Invalid bank account."));
	
	$accountID = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "accountID");
	$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	
	if($GLOBALS["defaultCurrencyID"] != $currencyID) {
		$check(($supplierAmount = parsePrice(post("foreignAmount"))) !== null, _("Invalid amount."));
		$check($supplierAmount > 0, _("Invalid amount."));
	} else {
		$supplierAmount = $amount;
	}
	
	$lines = array(
		array("accountID"=>$bankAccount, "amount"=>$amount * -1),
		array("accountID"=>$accountID, "amount"=>$supplierAmount),
	);
	
	$balance = accountingTransactionBalance($lines);
	$check($balance !== false, _("No valid transaction lines."));
	$check(post("confirm") !== null, null, $balance);
	
	accountingEditTransaction($payment["transactionID"], $date, $description, $lines);
	
	redirect("accounting/supplier.php?id=$supplierID");
}

main();

?>