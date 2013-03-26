<?php

require_once("common.php");

function main()
{
	$invoiceID = get("id");
	doAccountingInvoice($invoiceID);
	
	$pdf = stdGet("suppliersInvoice", array("invoiceID"=>$invoiceID), "pdf");
	if($pdf === null) {
		error404();
	}
	
	header("Content-Type: application/pdf");
	header("Content-Length: " . strlen($pdf));
	echo $pdf;
}

main();

?>