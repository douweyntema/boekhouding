<?php

require_once("common.php");

function main()
{
	$viewID = get("id");
	if(get("type") == "balance") {
		doAccountingBalanceView($viewID);
		
		$check = function($condition, $error) use($viewID) {
			if(!$condition) die(page(makeHeader(_("Edit view"), balanceViewBreadcrumbs($viewID), crumbs(_("Edit view"), "editview.php?id=$viewID&type=balance")) . editBalanceViewForm($viewID, $error, $_POST)));
		};
	} else {
		doAccountingIncomeExpenseView($viewID);
		
		$check = function($condition, $error) use($viewID) {
			if(!$condition) die(page(makeHeader(_("Edit view"), incomeExpenseViewBreadcrumbs($viewID), crumbs(_("Edit view"), "editview.php?id=$viewID&type=incomeexpence")) . editIncomeExpenseViewForm($viewID, $error, $_POST)));
		};
	}
	
	$check(($name = post("name")) !== null, "");
	$check(($description = post("description")) !== null, "");
	$check(($type = searchKey($_POST, "balance", "incomeexpences")) !== null, "");
	
	foreach($_POST as $key=>$value) {
		if(substr($key, 0, 8) != "account-") {
			continue;
		}
		$check(in_array($value, array("INHERIT", "VISIBLE", "COLLAPSED", "HIDDEN")), "");
		$accountID = substr($key, 8);
		if($value == "INHERIT") {
			$check(stdGet("accountingAccount", array("accountID"=>$accountID), "parentAccountID") !== null, "");
		}
	}
	
	if($type == "balance") {
		$check(($balanceDate = parseRelativeTime($_POST, "balance")) !== null, _("Invalid date specified"));
	} else {
		$check(($incomeExpencesStartDate = parseRelativeTime($_POST, "incomeExpencesStart")) !== null, _("Invalid start date specified"));
		$check(($incomeExpencesEndDate = parseRelativeTime($_POST, "incomeExpencesEnd")) !== null, _("Invalid end date specified"));
	}
	
	$check(post("confirm") !== null, null);
	
	startTransaction();
	if(get("type") == "balance") {
		stdDel("accountingBalanceViewAccount", array("balanceViewID"=>$viewID));
		stdDel("accountingBalanceView", array("balanceViewID"=>$viewID));
	} else {
		stdDel("accountingIncomeExpenseViewAccount", array("incomeExpenseViewID"=>$viewID));
		stdDel("accountingIncomeExpenseView", array("incomeExpenseViewID"=>$viewID));
	}
	
	if($type == "balance") {
		$viewID = stdNew("accountingBalanceView", array(
			"name"=>$name,
			"description"=>$description,
			"dateBase"=>$balanceDate["base"],
			"dateOffsetType"=>$balanceDate["offsetType"],
			"dateOffsetAmount"=>$balanceDate["offsetAmount"],
		));
		$table = "accountingBalanceViewAccount";
		$viewIDField = "balanceViewID";
	} else {
		$viewID = stdNew("accountingIncomeExpenseView", array(
			"name"=>$name,
			"description"=>$description,
			"startDateBase"=>$incomeExpencesStartDate["base"],
			"startDateOffsetType"=>$incomeExpencesStartDate["offsetType"],
			"startDateOffsetAmount"=>$incomeExpencesStartDate["offsetAmount"],
			"endDateBase"=>$incomeExpencesEndDate["base"],
			"endDateOffsetType"=>$incomeExpencesEndDate["offsetType"],
			"endDateOffsetAmount"=>$incomeExpencesEndDate["offsetAmount"],
		));
		$table = "accountingIncomeExpenseViewAccount";
		$viewIDField = "incomeExpenseViewID";
	}
	
	foreach($_POST as $key=>$value) {
		if(substr($key, 0, 8) != "account-") {
			continue;
		}
		$accountID = substr($key, 8);
		if($value != "INHERIT") {
			stdNew($table, array($viewIDField=>$viewID, "accountID"=>$accountID, "visibility"=>$value));
		}
	}
	
	commitTransaction();
	
	redirect("accounting/" . ($type == "balance" ? "balanceview.php" : "incomeexpenseview.php") . "?id=" . $viewID);
}

main();

?>