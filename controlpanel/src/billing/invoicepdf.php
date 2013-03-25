<?php

require_once("common.php");

function main()
{
	$invoiceID = get("id");
	doInvoice($invoiceID);
	
	$pdf = stdGet("billingInvoice", array("invoiceID"=>$invoiceID), "pdf");
	if($pdf === null) {
		$tex = stdGet("billingInvoice", array("invoiceID"=>$invoiceID), "tex");
		
		if($tex === null) {
			billingCreateInvoiceTex($invoiceID, false);
		} else {
			billingCreateInvoicePdf($invoiceID, false);
		}
		$pdf = stdGet("billingInvoice", array("invoiceID"=>$invoiceID), "pdf");
		if($pdf === null) {
			die("This invoice is currently unavailable.");
		}
	}
	
	header("Content-Type: application/pdf");
	header("Content-Length: " . strlen($pdf));
	echo $pdf;
}

main();

?>