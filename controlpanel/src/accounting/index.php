<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	$content = makeHeader("Boekhouding", accountingBreadcrumbs());
	$content .= accountList();
	$content .= addAccountForm(0, "STUB");
	echo page($content);
}

main();

?>