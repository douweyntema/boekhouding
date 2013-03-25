<?php

require_once("common.php");

function main()
{
	$transactionID = get("id");
	$accountID = get("accountID");
	
	doAccountingTransaction($transactionID);
	
	$check = function($condition, $error) use($transactionID, $accountID) {
		$date = stdGet("accountingTransaction", array("transactionID"=>$transactionID), "date");
		$content = makeHeader("Transaction on " . date("d-m-Y", $date), transactionBreadcrumbs($transactionID, $accountID));
		if(!$condition) die(page(makeHeader("Transaction on " . date("d-m-Y", $date), transactionBreadcrumbs($transactionID, $accountID), crumbs("Delete transaction", "deletetransaction.php?id=$transactionID&accountID=$accountID")) . deleteTransactionForm($transactionID, $accountID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	accountingDeleteTransaction($transactionID);
	
	redirect("accounting/account.php?id=$accountID");
}

main();

?>