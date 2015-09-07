<?php

require_once("common.php");

function main()
{
	$carID = get("id");
	doAccountingCar($carID);
	
	$check = function($condition, $error) use($carID) {
		if(!$condition) die(page(makeHeader(_("Edit car"), accountingBreadcrumbs(), crumbs(_("Edit car"), "editcar.php?id=$carID")) . editCarForm($carID, $error, $_POST)));
	};
	
	$check(($name = post("name")) !== null, "");
	$check($name != "", _("Missing car name."));
	$check(($description = post("description")) != "", _("Missing car description. Mention make, type and licence plate number here."));
	
	$check(($expensesAccountID = post("expencesAccountID")) !== null, "");
	$check(stdGetTry("accountingAccount", array("accountID"=>$expensesAccountID), "isDirectory", "1") == "0", _("Invalid expense account."));
	
	$check(($defaultBankAccountID = post("defaultBankAccountID")) !== null, "");
	$check(stdGetTry("accountingAccount", array("accountID"=>$defaultBankAccountID), "isDirectory", "1") == "0", _("Invalid default bank account."));
	
	$check(($kmfee = (int)(post("kmFee")*100)) !== "", _("Invalid km fee"));
	$check(($kmCurrency = stdGetTry("accountingCurrency", array("name"=>"km"), "currencyID")) !== null, _("Currency \"km\" not found, please contact your system administrator."));
	
	$check(post("confirm") !== null, null);
	
	startTransaction();
	accountingEditAccount(stdGet("accountingCar", array("carID"=>$carID), "drivenKmAccountID"), $name, $description);
	
	stdSet("accountingCar", array("carID"=>$carID), array(
		"name"=>$name,
		"description"=>$description,
		"expencesAccountID"=>$expensesAccountID,
		"defaultBankAccountID"=>$defaultBankAccountID,
		"kmFee"=>$kmfee,
		));
	commitTransaction();
	
	redirect("accounting/car.php?id=$carID");
}

main();

?>