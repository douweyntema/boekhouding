<?php

require_once("common.php");

function main()
{
	$accountID = get("id");
	doAccountingAccount($accountID);
	
	$content = makeHeader("Boekhouding", accountBreadcrumbs($accountID));
	$content .= transactionList($accountID);
	if($GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$accountID), "isDirectory")) {
		$content .= addAccountForm($accountID, "STUB");
	}
	$content .= editAccountForm($accountID, "STUB");
	$content .= moveAccountForm($accountID, "STUB");
	if(
		!$GLOBALS["database"]->stdExists("accountingTransactionLine", array("accountID"=>$accountID))
		&& !$GLOBALS["database"]->stdExists("accountingAccount", array("parentAccountID"=>$accountID)))
	{
		$content .= deleteAccountForm($accountID);
	}
	echo page($content);
}

main();

?>