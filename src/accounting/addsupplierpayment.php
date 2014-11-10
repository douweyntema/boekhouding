<?php

require_once("common.php");

function main()
{
	$supplierID = get("id");
	
	doAccountingSupplier($supplierID);
	
	$check = function($condition, $error, $balance = null) use($supplierID) {
		if(!$condition) die(page(makeHeader(_("Add payment"), supplierBreadcrumbs($supplierID), crumbs(_("Add payment"), "addsupplierpayment.php?id=$supplierID")) . addSupplierPaymentForm($supplierID, $error, $_POST, $balance)));
	};
	
	$check(($description = post("description")) != "", _("No description given."));
	$check(($date = parseDate(post("date"))) !== null, _("Invalid date."));
	$check(($amount = parsePrice(post("amount"))) !== null, _("Invalid amount."));
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
	
	startTransaction();
	$transactionID = accountingAddTransaction($date, $description, $lines);
	stdNew("suppliersPayment", array("supplierID"=>$supplierID, "transactionID"=>$transactionID));
	commitTransaction();
	
	redirect("accounting/supplier.php?id=$supplierID");
}

main();

?>