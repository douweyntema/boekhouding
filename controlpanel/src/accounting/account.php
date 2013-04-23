<?php

require_once("common.php");

function main()
{
	$accountID = get("id");
	doAccountingAccount($accountID);
	$accountName = stdGet("accountingAccount", array("accountID"=>$accountID), "name");
	$content = makeHeader("Account $accountName", accountBreadcrumbs($accountID));
	
	$content .= accountSummary($accountID);
	$content .= accountList($accountID);
	if(stdGet("accountingAccount", array("accountID"=>$accountID), "isDirectory")) {
		$content .= addAccountForm($accountID, "STUB");
	} else {
		$content .= addTransactionForm($accountID);
	}
	$content .= transactionList($accountID);
	$content .= editAccountForm($accountID, "STUB");
	$content .= moveAccountForm($accountID, "STUB");
	if(accountEmpty($accountID)) {
		$content .= deleteAccountForm($accountID);
	}
	echo page($content);
}

main();

?>