<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	$content = makeHeader("Accounting", accountingBreadcrumbs());
	$content .= accountList();
	$content .= addAccountForm(0, "STUB");
	$content .= addSupplierForm("STUB");
	echo page($content);
}

main();

?>