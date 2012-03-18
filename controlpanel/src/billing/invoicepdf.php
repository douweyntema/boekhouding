<?php

require_once("common.php");

function main()
{
	$invoiceID = get("id");
	doInvoice($invoiceID);
	
	$pdf = $GLOBALS["database"]->stdGet("billingInvoice", array("invoiceID"=>$invoiceID), "pdf");
	
	header("Content-Type: application/pdf");
	header("Content-Length: " . strlen($pdf));
	echo $pdf;
}

main();

?>