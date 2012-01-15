<?php

$billingTitle = "Billing";
$billingDescription = "Billing";
$billingTarget = "both";

function billingDomainPrice($customerID, $tldID)
{
	return $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$tldID), "price");
}

?>