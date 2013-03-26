<?php

require_once("common.php");

function main()
{
	$supplierID = get("id");
	
	doAccountingSupplier($supplierID);
	
	$name = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "name");
	$content = makeHeader("Supplier " . $name, supplierBreadcrumbs($supplierID));
	
	$content .= supplierSummary($supplierID);
	
// 	$content .= supplierInvoiceList($supplierID);
// 	$content .= supplierPaymentList($supplierID);

// 	$content .= addSupplierInvoiceForm($supplierID);
// 	$content .= addSupplierPaymentForm($supplierID);

	$content .= editSupplierForm($supplierID, "STUB");
// 	$content .= deleteSupplierForm($supplierID, "STUB");

	echo page($content);
}

main();

?>