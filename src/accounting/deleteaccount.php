<?php

require_once("common.php");

function main()
{
	$accountID = get("id");
	doAccountingAccount($accountID);
	if($accountID == 0) error404();
	
	$check = function($condition, $error) use($accountID) {
		if(!$condition) die(page(makeHeader(_("Delete account"), accountBreadcrumbs($accountID), crumbs(_("Delete account"), "deleteaccount.php?id=$accountID")) . deleteAccountForm($accountID, $error, $_POST)));
	};
	
	$check(accountEmpty($accountID), _("Account is still in use."));
	$check(post("confirm") !== null, null);
	
	accountingDeleteAccount($accountID);
	
	redirect("accounting/index.php");
}

main();

?>