<?php

require_once("common.php");

function main()
{
	$carID = get("id");
	doAccountingCar($carID);
	
	$check = function($condition, $error) use ($carID) {
		if(!$condition) die(page(makeHeader(_("Add Travel Expences"), accountingBreadcrumbs(), crumbs(_("Add travel expences"), "addtravelexpences.php?id=$carID")) . addTravelExpencesForm($carID, $error, $_POST)));
	};
	
	$check(post("date") !== null, "");
	$check(($date = parseDate(post("date"))) !== null, _("Invalid date."));
	$check(($startKm = post("startKm")) != "", _("Invalid Km."));
	$check(($endKm = post("endKm")) != "", _("Invalid Km."));
	$check(($destination = post("destination")) != "", _("Invalid destination."));
	$check(($occasion = post("occasion")) != "", _("Invalid occasion."));
	$check(ctype_digit($startKm), _("Invalid Km."));
	$check(ctype_digit($endKm), _("Invalid Km."));
	$check($startKm < $endKm, _("Invalid Km."));
	$check(($accountID = post("accountID")) != "", _("Invalid account."));
	$check(stdExists("accountingAccount", array("accountID"=>$accountID)), _("Invalid account."));
	$check(post("confirm") !== null, null);
	
	$km = $endKm - $startKm;
	$amount = $km * stdGet("accountingCar", array("carID"=>$carID), "kmFee");
	
	$description = "$occasion; Adres: $destination; Start: $startKm km; Einde: $endKm km; Afstand: $km km";
	
	$lines = array();
	$lines[] = array("accountID"=>$accountID, "amount"=>-1 * $amount);
	$lines[] = array("accountID"=>stdGet("accountingCar", array("carID"=>$carID), "expencesAccountID"), "amount"=>$amount);
	$lines[] = array("accountID"=>stdGet("accountingCar", array("carID"=>$carID), "drivenKmAccountID"), "amount"=>100 * $km);
	
	accountingAddTransaction($date, $description, $lines);
	
	redirect("accounting/car.php?id=$carID");
}

main();

?>