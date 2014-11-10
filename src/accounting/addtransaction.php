<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	$accountID = get("id");
	
	$check = function($condition, $error, $balance = null) use($accountID) {
		if(!$condition) die(page(makeHeader(_("Add transaction"), accountBreadcrumbs($accountID), crumbs(_("Add transaction"), "addtransaction.php?id=$accountID")) . addTransactionForm($accountID, $error, $_POST, $balance)));
	};
	
	$check(($description = post("description")) !== null, "");
	$check($description != "", _("No description given."));
	$check(($date = parseDate(post("date"))) !== null, _("Invalid date."));
	
	$lines = parseArrayField($_POST, array("accountID", "amount"));
	
	$parsedLines = array();
	foreach($lines as $line) {
		$amount = parsePrice($line["amount"]);
		if($line["accountID"] == "" && $amount != 0) {
			$check(false, _("No account selected."));
		}
		if($amount == 0) {
			continue;
		}
		$check(stdExists("accountingAccount", array("accountID"=>$line["accountID"])), _("Invalid account."));
		$parsedLines[] = array("accountID"=>$line["accountID"], "amount"=>$amount);
	}
	$balance = accountingTransactionBalance($parsedLines);
	$check($balance !== false, _("No valid transaction lines."));
	if($balance["type"] == "single") {
		$check($balance["status"], _("Transaction not in balance"));
	}
	$check(post("confirm") !== null, null, $balance);
	
	
	accountingAddTransaction($date, $description, $parsedLines);
	
	redirect("accounting/account.php?id=$accountID");
}

main();

?>