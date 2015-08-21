<?php

require_once("common.php");

function main()
{
	$customerID = get("customerID");
	doBillingAdmin($customerID);
	$lineIDs = explode(",", get("lines"));
	$invoiceLines = array();
	foreach($lineIDs as $lineID) {
		$line = stdGet("billingSubscriptionLine", array("subscriptionLineID"=>$lineID), array("description", "periodStart", "periodEnd", "price", "discount"));
		
		$priceWithoutTax = round($line["price"] / (1 + $GLOBALS["taxRate"]));
		$discountWithoutTax = round($line["discount"] / (1 + $GLOBALS["taxRate"]));

		$amount = $line["price"] - $line["discount"];
		$amountWithoutTax = $priceWithoutTax - $discountWithoutTax;
		
		$tax = $amount - $amountWithoutTax;
		
		$invoiceLines[] = array("description"=>$line["description"], "periodStart"=>$line["periodStart"], "periodEnd"=>$line["periodEnd"], "price"=>$priceWithoutTax, "discount"=>$discountWithoutTax, "tax"=>$tax, "taxRate"=>("" . $GLOBALS["taxRate"] * 100));
	}
	$tex = billingCreateInvoiceTexRaw($customerID, time(), "EXAMPLE", $invoiceLines);
	$pdf = pdfLatex($tex);
	if($pdf === null) {
		die("This invoice is currently unavailable.");
	}
	
	header("Content-Type: application/pdf");
	header("Content-Length: " . strlen($pdf));
	header("Content-Disposition: attachment; filename=factuur-voorbeeld.pdf");
	echo $pdf;
}

main();

?>