<?php

require_once("common.php");

function main()
{
	$accountID = get("id");
	doAccountingAccount($accountID);
	$accountName = stdGet("accountingAccount", array("accountID"=>$accountID), "name");
	$content = makeHeader("Account $accountName", accountBreadcrumbs($accountID));
	
	$content .= accountSummary($accountID);
	$content .= transactionList($accountID);
	if(stdGet("accountingAccount", array("accountID"=>$accountID), "isDirectory")) {
		$content .= addAccountForm($accountID, "STUB");
	} else {
		$content .= addTransactionForm($accountID);
	}
	$content .= editAccountForm($accountID, "STUB");
	$content .= moveAccountForm($accountID, "STUB");
	if(
		!stdExists("accountingTransactionLine", array("accountID"=>$accountID))
		&& !stdExists("accountingAccount", array("parentAccountID"=>$accountID)))
	{
		$content .= deleteAccountForm($accountID);
	}
	echo page($content);
}

main();

?>