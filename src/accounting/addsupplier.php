<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader(_("Add supplier"), accountingBreadcrumbs(), crumbs(_("Add supplier"), "addsupplier.php")) . addSupplierForm($error, $_POST)));
	};
	
	$check(($name = post("name")) !== null, "");
	$check(($currencyID = post("currencyID")) !== null, "");
	$check(($defaultExpenseAccountID = post("defaultExpenseAccountID")) !== null, "");
	
	$check($name != "", "Missing supplier name.");
	$check(stdExists("accountingCurrency", array("currencyID"=>$currencyID)), _("Invalid currency."));
	$check($defaultExpenseAccountID == "" || stdGetTry("accountingAccount", array("accountID"=>$defaultExpenseAccountID), "isDirectory", "1") == "0", _("Invalid expense account."));
	$check(post("confirm") !== null, null);
	
	startTransaction();
	$accountID = accountingAddAccount($GLOBALS["suppliersDirectoryAccountID"], $currencyID, $name, supplierAccountDescription($name), false);
	$supplierID = stdNew("suppliersSupplier", array("accountID"=>$accountID, "defaultExpenseAccountID"=>($defaultExpenseAccountID == "" ? null : $defaultExpenseAccountID), "name"=>$name, "description"=>post("description")));
	commitTransaction();
	
	redirect("accounting/supplier.php?id=$supplierID");
}

main();

?>