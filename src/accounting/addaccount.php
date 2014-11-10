<?php

require_once("common.php");

function main()
{
	$accountID = get("id");
	doAccountingAccount($accountID);
	if($accountID != 0 && !stdGet("accountingAccount", array("accountID"=>$accountID), "isDirectory")) {
		error404();
	}
	
	$check = function($condition, $error) use($accountID) {
		if(!$condition) die(page(makeHeader(_("Add account"), accountBreadcrumbs($accountID), crumbs(_("Add account"), "addaccount.php?id=$accountID")) . addAccountForm($accountID, $error, $_POST)));
	};
	
	$isDirectory = post("type") == "directory";
	$description = post("description");
	
	$check(($name = post("name")) !== null, "");
	$check(($currencyID = post("currencyID")) != null, "");
	
	$check($name != "", _("Missing account name."));
	$check(stdExists("accountingCurrency", array("currencyID"=>$currencyID)), _("Invalid currency."));
	$check(post("confirm") !== null, null);
	
	$newAccountID = accountingAddAccount($accountID == 0 ? null : $accountID, $currencyID, $name, $description, $isDirectory);
	
	redirect("accounting/account.php?id=$newAccountID");
}

main();

?>