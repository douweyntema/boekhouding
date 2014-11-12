<?php

require_once("common.php");

function main()
{
	$incomeExpenseViewID = get("id");
	doAccountingIncomeExpenseView($incomeExpenseViewID);
	
	$name = stdGet("accountingIncomeExpenseView", array("incomeExpenseViewID"=>$incomeExpenseViewID), "name");
	$now = time();
	$content = makeHeader(sprintf(_("Income / Expense view %s"), $name), incomeExpenseViewBreadcrumbs($incomeExpenseViewID));
	
	$content .= incomeExpenseViewSummary($incomeExpenseViewID, $now);
	$content .= incomeExpenseViewList($incomeExpenseViewID, $now);
// 	$content .= editIncomeExpenseViewForm($incomeExpenseViewID, "STUB");
// 	$content .= deleteIncomeExpenceViewForm($incomeExpenseViewID);
	
	echo page($content);
}

main();

?>