<?php

require_once("common.php");

function main()
{
	$carID = get("id");
	doAccountingCar($carID);
	
	$check = function($condition, $error) use($carID) {
		if(!$condition) die(page(makeHeader(_("Delete car"), accountingBreadcrumbs(), crumbs(_("Delete car"), "deletecar.php?id=$carID")) . deleteCarForm($carID, $error, $_POST)));
	};
	
	$check(carEmpty($carID), _("This car has driven km in the system and cannot be removed."));
	$check(post("confirm") !== null, null);
	
	startTransaction();
	$accountID = stdGet("accountingCar", array("carID"=>$carID), "drivenKmAccountID");
	stdDel("accountingCar", array("carID"=>$carID));
	accountingDeleteAccount($accountID);
	commitTransaction();
	
	redirect("accounting/index.php");
}

main();

?>