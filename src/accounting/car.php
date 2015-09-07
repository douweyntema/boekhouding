<?php

require_once("common.php");

function main()
{
	$carID = get("id");
	doAccountingCar($carID);
	
	$name = stdGet("accountingCar", array("carID"=>$carID), "name");
	$content = makeHeader($name, carBreadcrumbs($carID));
	
	$content .= carSummary($carID);
	$content .= addTravelExpencesForm($carID);
	
	$content .= transactionList(stdGet("accountingCar", array("carID"=>$carID), "drivenKmAccountID"));
	
	echo page($content);
}

main();

?>