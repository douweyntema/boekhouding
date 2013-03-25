<?php

require_once("common.php");

function main()
{
	$accountID = get("id");
	doAccountingAccount($accountID);
	if($accountID == 0) error404();
	
	$check = function($condition, $error) use($accountID) {
		if(!$condition) die(page(makeHeader("Delete account", accountingBreadcrumbs(), crumbs("Delete account", "deleteaccount.php?id=$accountID")) . deleteAccountForm($accountID, $error, $_POST)));
	};
	
	$check(!$GLOBALS["database"]->stdExists("accountingTransactionLine", array("accountID"=>$accountID)), "Account is still in use.");
	$check(!$GLOBALS["database"]->stdExists("accountingAccount", array("parentAccountID"=>$accountID)), "Account is still in use.");
	$check(post("confirm") !== null, null);
	
	accountingDeleteAccount($accountID);
	
	redirect("accounting/index.php");
}

main();

?>