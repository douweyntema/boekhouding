<?php

require_once("common.php");

function main()
{
	$accountID = get("id");
	doAccountingAccount($accountID);
	
	$toDate = get("toDate");
	$fromDate = get("fromDate");
	$balanceViewID = get("balanceViewID");
	$incomeExpenseViewID = get("incomeExpenseViewID");
	
	$accountName = stdGet("accountingAccount", array("accountID"=>$accountID), "name");
	$content = makeHeader(sprintf(_("Account %s"), $accountName), accountBreadcrumbs($accountID, $toDate, $fromDate, $balanceViewID, $incomeExpenseViewID));
	
	$content .= accountSummary($accountID, $toDate, $fromDate, $balanceViewID, $incomeExpenseViewID);
	$content .= accountList($accountID, $toDate, $fromDate, $balanceViewID, $incomeExpenseViewID);
	$content .= transactionList($accountID, $toDate, $fromDate);
	if(stdGet("accountingAccount", array("accountID"=>$accountID), "isDirectory")) {
		$content .= addAccountForm($accountID, "STUB");
	} else {
		$content .= addTransactionForm($accountID);
	}
	$content .= editAccountForm($accountID, "STUB");
	$content .= moveAccountForm($accountID, "STUB");
	if(accountEmpty($accountID)) {
		$content .= deleteAccountForm($accountID);
	}
	echo page($content);
}

main();

?>