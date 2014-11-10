<?php

require_once("common.php");

function main()
{
	$accountID = get("id");
	doAccountingAccount($accountID);
	if($accountID == 0) error404();
	
	$check = function($condition, $error) use($accountID) {
		if(!$condition) die(page(makeHeader(_("Move account"), accountBreadcrumbs($accountID), crumbs(_("Move account"), "moveaccount.php?id=$accountID")) . moveAccountForm($accountID, $error, $_POST)));
	};
	
	$check(($parentAccountID = post("parentAccountID")) !== null, "");
	if($parentAccountID == 0) {
		$parentAccountID = null;
	}
	
	$check($parentAccountID === null || stdExists("accountingAccount", array("accountID"=>$parentAccountID)), _("Invalid target account"));
	$target = $parentAccountID;
	while($target !== null) {
		$check($target != $accountID, _("Invalid target account"));
		$target = stdGet("accountingAccount", array("accountID"=>$target), "parentAccountID");
	}
	
	$check(post("confirm") !== null, null);
	
	accountingMoveAccount($accountID, $parentAccountID);
	
	redirect("accounting/account.php?id=$accountID");
}

main();

?>