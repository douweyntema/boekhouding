<?php

require_once("common.php");

function main()
{
	$accountID = get("id");
	doAccountingAccount($accountID);
	if($accountID != 0 && !$GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$accountID), "isDirectory")) {
		error404();
	}
	
	$check = function($condition, $error) use($accountID) {
		if(!$condition) die(page(makeHeader("Add account", accountingBreadcrumbs(), crumbs("Add account", "addaccount.php?id=$accountID")) . addAccountForm($accountID, $error, $_POST)));
	};
	
	$isDirectory = post("type") == "directory";
	
	$check(($name = post("name")) !== null, "");
	$check(($currencyID = post("currencyID")) != null, "");
	$check(($description = post("description")) != null, "");
	
	$check($name != "", "Missing account name.");
	$check($GLOBALS["database"]->stdExists("accountingCurrency", array("currencyID"=>$currencyID)), "Invalid currency.");
	$check(post("confirm") !== null, null);
	
	$newAccountID = accountingAddAccount($accountID == 0 ? null : $accountID, $currencyID, $name, $description, $isDirectory);
	
	redirect("accounting/account.php?id=$newAccountID");
}

main();

?>