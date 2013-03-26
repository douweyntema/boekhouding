<?php

require_once("common.php");

function main()
{
	$invoiceID = get("id");
	
	doAccountingInvoice($invoiceID);
	
	$invoiceNumber = stdGet("suppliersInvoice", array("invoiceID"=>$invoiceID), "invoiceNumber");
	$content = makeHeader("Invoice " . $invoiceNumber, suppliersInvoiceBreadcrumbs($invoiceID));
	
	$content .= supplierInvoiceSummary($invoiceID);
	
	$content .= editSupplierInvoiceForm($invoiceID);
	$content .= deleteSupplierInvoiceForm($invoiceID);
	
	echo page($content);
}

main();

?>