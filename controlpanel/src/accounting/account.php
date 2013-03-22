<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	$accountID = get("id");
	$content = makeHeader("Boekhouding", accountBreadcrumbs($accountID));
	
	$content .= transactionList($accountID);
	echo page($content);
}

main();

?>