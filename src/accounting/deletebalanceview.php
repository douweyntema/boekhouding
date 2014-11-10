<?php

require_once("common.php");

function main()
{
	$viewID = get("id");
	doAccountingBalanceView($viewID);
	
	$check = function($condition, $error) use($viewID) {
		if(!$condition) die(page(makeHeader(_("Delete view"), balanceViewBreadcrumbs($viewID), crumbs(_("Delete view"), "deletebalanceview.php?id=$viewID")) . deleteBalanceViewForm($viewID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	startTransaction();
	stdDel("accountingBalanceViewAccount", array("balanceViewID"=>$viewID));
	stdDel("accountingBalanceView", array("balanceViewID"=>$viewID));
	commitTransaction();
	
	redirect("accounting/index.php");
}

main();

?>