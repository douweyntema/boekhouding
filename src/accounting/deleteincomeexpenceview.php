<?php

require_once("common.php");

function main()
{
	$viewID = get("id");
	doAccountingIncomeExpenseView($viewID);
	
	$check = function($condition, $error) use($viewID) {
		if(!$condition) die(page(makeHeader(_("Delete view"), incomeExpenseViewBreadcrumbs($viewID), crumbs(_("Delete view"), "deleteincomeexpenceview.php?id=$viewID")) . deleteIncomeExpenceViewForm($viewID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	startTransaction();
	stdDel("accountingIncomeExpenseViewAccount", array("incomeExpenseViewID"=>$viewID));
	stdDel("accountingIncomeExpenseView", array("incomeExpenseViewID"=>$viewID));
	commitTransaction();
	
	redirect("accounting/index.php");
}

main();

?>