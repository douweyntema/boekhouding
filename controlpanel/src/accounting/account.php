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
	echo page($content);
}

main();

?>