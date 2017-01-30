<?php

require_once("common.php");

function main()
{
	$transactionID = get("id");
	$accountID = get("accountID");
	
	doAccountingTransaction($transactionID);
	
	$check = function($condition, $error) use($transactionID, $accountID) {
		$date = stdGet("accountingTransaction", array("transactionID"=>$transactionID), "date");
		$content = makeHeader(sprintf(_("Transaction on %s"), date("d-m-Y", $date)), transactionBreadcrumbs($transactionID, $accountID));
		if(!$condition) die(page(makeHeader(sprintf(_("Transaction on %s"), date("d-m-Y", $date)), transactionBreadcrumbs($transactionID, $accountID), crumbs(_("Delete transaction"), "deletetransaction.php?id=$transactionID&accountID=$accountID")) . deleteTransactionForm($transactionID, $accountID, $error, $_POST)));
	};
	
	$type = accountingTransactionType($transactionID);
	$check(($type["type"] == "NONE"), _("This transaction is linked to an invoice or payment."));
	$check(post("confirm") !== null, null);
	
	accountingDeleteTransaction($transactionID);
	
	if($accountID === null || $accountID == "") {
		redirect("accounting/index.php");
	} else {
		redirect("accounting/account.php?id=$accountID");
	}
}

main();

?>