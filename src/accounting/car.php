<?php

require_once("common.php");

function main()
{
	$carID = get("id");
	doAccountingCar($carID);
	
	$name = stdGet("accountingCar", array("carID"=>$carID), "name");
	$content = makeHeader($name, carBreadcrumbs($carID));
	
// 	$content .= carSummary($carID);
// 	$content .= editCarForm($fixedAssetID);
	
	$content .= addTravelExpencesForm($carID);
	
	echo page($content);
}

main();

?>