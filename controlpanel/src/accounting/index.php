<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	accountingAutoDepreciate();
	
	$content = makeHeader("Accounting", accountingBreadcrumbs());
	$content .= accountList();
	$content .= supplierList();
	$content .= fixedAssetList();
	$content .= viewList();
	$content .= addAccountForm(0, "STUB");
	$content .= addSupplierForm("STUB");
	$content .= addFixedAssetForm("STUB");
	$content .= recomputeBalancesForm();
	$content .= addViewForm();
	echo page($content);
}

main();

?>