<?php

require_once("common.php");

function main()
{
	$supplierID = get("id");
	
	doAccountingSupplier($supplierID);
	
	$name = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "name");
	$content = makeHeader(sprintf(_("Supplier %s"), $name), supplierBreadcrumbs($supplierID));
	
	$content .= supplierSummary($supplierID);
	
	$content .= supplierInvoiceList($supplierID);
	$content .= supplierPaymentList($supplierID);

	$content .= addSupplierInvoiceForm($supplierID);
	$content .= addSupplierPaymentForm($supplierID);

	$content .= editSupplierForm($supplierID, "STUB");
	if(supplierEmpty($supplierID)) {
		$content .= deleteSupplierForm($supplierID);
	}

	echo page($content);
}

main();

?>