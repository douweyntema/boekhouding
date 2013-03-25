<?php

require_once("common.php");

function main()
{
	$transactionID = get("id");
	$accountID = get("accountID");
	
	doAccountingTransaction($transactionID);
	
	$date = stdGet("accountingTransaction", array("transactionID"=>$transactionID), "date");
	$content = makeHeader("Transaction on " . date("d-m-Y", $date), transactionBreadcrumbs($transactionID, $accountID));
	
	$content .= editTransactionForm($transactionID, $accountID);
	$content .= deleteTransactionForm($transactionID, $accountID);
	
	echo page($content);
}

main();

?>