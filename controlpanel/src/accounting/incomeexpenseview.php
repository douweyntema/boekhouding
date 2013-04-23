<?php

require_once("common.php");

function main()
{
	$incomeExpenseViewID = get("id");
	doAccountingIncomeExpenseView($incomeExpenseViewID);
	
	$name = stdGet("accountingIncomeExpenseView", array("incomeExpenseViewID"=>$incomeExpenseViewID), "name");
	$now = time();
	$content = makeHeader("Income / Expense view $name", incomeExpenseViewBreadcrumbs($incomeExpenseViewID));
	
	$content .= incomeExpenseViewSummary($incomeExpenseViewID, $now);
	$content .= incomeExpenseViewList($incomeExpenseViewID, $now);
	$content .= deleteIncomeExpenceViewForm($incomeExpenseViewID);
	
	echo page($content);
}

main();

?>