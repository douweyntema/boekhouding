<?php

$mailTitle = "Email";
$mailDescription = "Email management";
$mailTarget = "customer";

function updateMail($customerID)
{
	$mailSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "mailSystemID");
	$GLOBALS["database"]->stdIncrement("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "version", 1000000000);
	
	$hosts = $GLOBALS["database"]->stdList("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), "hostID");
	updateHosts($hosts, "update-treva-dovecot");
	updateHosts($hosts, "update-treva-exim");
}

?>