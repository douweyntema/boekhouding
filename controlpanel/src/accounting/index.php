<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	$content = makeHeader("Accounting", accountingBreadcrumbs());
	$content .= accountList();
	$content .= supplierList();
	$content .= addAccountForm(0, "STUB");
	echo page($content);
}

main();

?>