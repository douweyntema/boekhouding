<?php

require_once("common.php");

function main()
{
	$balanceViewID = get("id");
	doAccountingBalanceView($balanceViewID);
	
	$name = stdGet("accountingBalanceView", array("balanceViewID"=>$balanceViewID), "name");
	$now = time();
	$content = makeHeader(sprintf(_("Balance %s"), $name), balanceViewBreadcrumbs($balanceViewID));
	
	$content .= balanceViewSummary($balanceViewID, $now);
	$content .= balanceViewList($balanceViewID, $now);
	$content .= editBalanceViewForm($balanceViewID, "STUB");
	$content .= deleteBalanceViewForm($balanceViewID);
	
	echo page($content);
}

main();

?>