<?php

require_once("common.php");

function main()
{
	$accountID = get("id");
	doAccountingAccount($accountID);
	if($accountID == 0) error404();
	
	$check = function($condition, $error) use($accountID) {
		if(!$condition) die(page(makeHeader("Edit account", accountBreadcrumbs($accountID), crumbs("Edit account", "editaccount.php?id=$accountID")) . editAccountForm($accountID, $error, $_POST)));
	};
	
	$check(($name = post("name")) !== null, "");
	$check(($description = post("description")) !== null, "");
	
	$check($name != "", "Missing account name.");
	$check(post("confirm") !== null, null);
	
	accountingEditAccount($accountID, $name, $description);
	
	redirect("accounting/account.php?id=$accountID");
}

main();

?>