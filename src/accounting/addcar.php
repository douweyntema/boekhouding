<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader(_("Add car"), accountingBreadcrumbs(), crumbs(_("Add car"), "addcar.php")) . addCarForm($error, $_POST)));
	};
	
	$check(($name = post("name")) !== null, "");
	$check($name != "", _("Missing car name."));
	$check(($description = post("description")) != "", _("Missing car description. Mention make, type and licence plate number here."));
	
	$check(($expensesAccountID = post("expencesAccountID")) !== null, "");
	$check(stdGetTry("accountingAccount", array("accountID"=>$expensesAccountID), "isDirectory", "1") == "0", _("Invalid expense account."));
	
	$check(($defaultBankAccountID = post("defaultBankAccountID")) !== null, "");
	$check(stdGetTry("accountingAccount", array("accountID"=>$defaultBankAccountID), "isDirectory", "1") == "0", _("Invalid default bank account."));
	
	$check(($kmfee = (int)(post("kmFee")*100)) !== "", _("Invalid km fee"));
	
	$check(post("confirm") !== null, null);
	
	startTransaction();
	$kmCurrency = stdGetTry("accountingCurrency", array("name"=>"km"), "currencyID");
	if($kmCurrency === null) {
		$kmCurrency = stdNew("accountingCurrency", array("name"=>"km", "symbol"=>"km", "order"=>999));
	}
	$drivenKmAccountID = accountingAddAccount($GLOBALS["travelExpencesAccountID"], $kmCurrency, $name, $description, false);
	
	$carID = stdNew("accountingCar", array(
		"name"=>$name,
		"description"=>$description,
		"drivenKmAccountID"=>$drivenKmAccountID,
		"expencesAccountID"=>$expensesAccountID,
		"defaultBankAccountID"=>$defaultBankAccountID,
		"kmFee"=>$kmfee,
		));
	commitTransaction();
	
	redirect("accounting/car.php?id=$carID");
}

main();

?>