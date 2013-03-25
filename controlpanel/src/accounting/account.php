<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	$accountID = get("id");
	$content = makeHeader("Boekhouding", accountBreadcrumbs($accountID));
	
	$content .= transactionList($accountID);
	if($GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$accountID), "isDirectory") == 0) {
		$content .= addTransactionForm($accountID);
	}
	echo page($content);
}

main();

?>