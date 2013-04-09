<?php

$accountingTitle = "Accounting";
$accountingTarget = "admin";

function accountingAddAccount($parentAccountID, $currencyID, $name, $description, $isDirectory)
{
	if($parentAccountID !== null && stdGet("accountingAccount", array("accountID"=>$parentAccountID), "isDirectory") != 1) {
		throw new AssertionError();
	}
	
	return stdNew("accountingAccount", array("parentAccountID"=>$parentAccountID, "currencyID"=>$currencyID, "name"=>$name, "description"=>$description, "isDirectory"=>($isDirectory ? 1 : 0), "balance"=>0));
}

function accountingEditAccount($accountID, $name, $description)
{
	stdSet("accountingAccount", array("accountID"=>$accountID), array("name"=>$name, "description"=>$description));
}

function accountingMoveAccount($accountID, $parentAccountID)
{
	if($parentAccountID !== null && stdGet("accountingAccount", array("accountID"=>$parentAccountID), "isDirectory") != 1) {
		throw new AssertionError();
	}
	
	startTransaction();
	$balance = stdGet("accountingAccount", array("accountID"=>$accountID), "balance");
	$oldParentAccountID = stdGet("accountingAccount", array("accountID"=>$accountID), "parentAccountID");
	accountingAddBalance($oldParentAccountID, -$balance);
	stdSet("accountingAccount", array("accountID"=>$accountID), array("parentAccountID"=>$parentAccountID));
	accountingAddBalance($parentAccountID, $balance);
	commitTransaction();
}

function accountingDeleteAccount($accountID)
{
	stdDel("accountingAccount", array("accountID"=>$accountID));
}

function accountingAddBalance($accountID, $amount)
{
	if($accountID === null) {
		return;
	}
	startTransaction();
	$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	while($accountID !== null) {
		$account = stdGet("accountingAccount", array("accountID"=>$accountID), array("parentAccountID", "currencyID"));
		if($account["currencyID"] != $currencyID) {
			break;
		}
		
		$amountSql = dbAddSlashes($amount);
		$accountIDSql = dbAddSlashes($accountID);
		setQuery("UPDATE accountingAccount SET balance = balance + '$amountSql' WHERE accountID = '$accountIDSql'");
		
		$accountID = $account["parentAccountID"];
	}
	commitTransaction();
}

function accountingSetTransactionLines($transactionID, $lines)
{
	startTransaction();
	foreach(stdList("accountingTransactionLine", array("transactionID"=>$transactionID), array("transactionLineID", "accountID", "amount")) as $line) {
		accountingAddBalance($line["accountID"], -$line["amount"]);
	}
	stdDel("accountingTransactionLine", array("transactionID"=>$transactionID));
	foreach($lines as $line) {
		accountingAddBalance($line["accountID"], $line["amount"]);
		stdNew("accountingTransactionLine", array("transactionID"=>$transactionID, "accountID"=>$line["accountID"], "amount"=>$line["amount"]));
	}
	commitTransaction();
}

function accountingAddTransaction($date, $description, $lines)
{
	startTransaction();
	$transactionID = stdNew("accountingTransaction", array("date"=>$date, "description"=>$description));
	accountingSetTransactionLines($transactionID, $lines);
	commitTransaction();
	return $transactionID;
}

function accountingEditTransaction($transactionID, $date, $description, $lines)
{
	startTransaction();
	stdSet("accountingTransaction", array("transactionID"=>$transactionID), array("date"=>$date, "description"=>$description));
	accountingSetTransactionLines($transactionID, $lines);
	commitTransaction();
}

function accountingDeleteTransaction($transactionID)
{
	startTransaction();
	accountingSetTransactionLines($transactionID, array());
	stdDel("accountingTransaction", array("transactionID"=>$transactionID));
	commitTransaction();
}

function accountingTransactionBalance($lines)
{
	$valutaTotal = array();
	$currencies = array();
	foreach($lines as $line) {
		$currencyID = stdGet("accountingAccount", array("accountID"=>$line["accountID"]), "currencyID");
		if(!isset($valutaTotal[$currencyID])) {
			$valutaTotal[$currencyID] = 0;
			$currencies[] = $currencyID;
		}
		$valutaTotal[$currencyID] += $line["amount"];
	}
	if(count($valutaTotal) == 0) {
		return false;
	} else if(count($valutaTotal) == 1) {
		return array("totals"=>$valutaTotal, "type"=>"single", "status"=>($valutaTotal[$currencies[0]] == 0));
	} else if(count($valutaTotal) == 2) {
		return array("totals"=>$valutaTotal, "type"=>"double", "rates"=>array(
			array("from"=>$currencies[0], "to"=>$currencies[1], "rate"=>$valutaTotal[$currencies[1]] / $valutaTotal[$currencies[0]] * -100),
			array("from"=>$currencies[1], "to"=>$currencies[0], "rate"=>$valutaTotal[$currencies[0]] / $valutaTotal[$currencies[1]] * -100),
		));
	} else {
		return array("totals"=>$valutaTotal, "type"=>"multiple");
	}
}


function accountingAccountType($accountID)
{
	if(($customerID = stdGetTry("adminCustomer", array("accountID"=>$accountID), "customerID")) !== null) {
		return array("type"=>"CUSTOMER", "customerID"=>$customerID);
	}
	if(($supplierID = stdGetTry("suppliersSupplier", array("accountID"=>$accountID), "supplierID")) !== null) {
		return array("type"=>"SUPPLIER", "supplierID"=>$supplierID);
	}
	if(($fixedAssetID = stdGetTry("accountingFixedAsset", array("accountID"=>$accountID), "fixedAssetID")) !== null) {
		return array("type"=>"FIXEDASSETVALUE", "fixedAssetID"=>$fixedAssetID);
	}
	if(($fixedAssetID = stdGetTry("accountingFixedAsset", array("depreciationAccountID"=>$accountID), "fixedAssetID")) !== null) {
		return array("type"=>"FIXEDASSETDEPRICIATION", "fixedAssetID"=>$fixedAssetID);
	}
	if(($fixedAssetID = stdGetTry("accountingFixedAsset", array("expenseAccountID"=>$accountID), "fixedAssetID")) !== null) {
		return array("type"=>"FIXEDASSETEXPENSE", "fixedAssetID"=>$fixedAssetID);
	}
	return array("type"=>"NONE");
}

function accountingTransactionType($transactionID)
{
	if(($invoiceID = stdGetTry("billingInvoice", array("transactionID"=>$transactionID), "invoiceID")) !== null) {
		return array("type"=>"CUSTOMERINVOICE", "invoiceID"=>$invoiceID);
	}
	if(($paymentID = stdGetTry("billingPayment", array("transactionID"=>$transactionID), "paymentID")) !== null) {
		return array("type"=>"CUSTOMERPAYMENT", "paymentID"=>$paymentID);
	}
	if(($invoiceID = stdGetTry("suppliersInvoice", array("transactionID"=>$transactionID), "invoiceID")) !== null) {
		return array("type"=>"SUPPLIERINVOICE", "invoiceID"=>$invoiceID);
	}
	if(($paymentID = stdGetTry("suppliersPayment", array("transactionID"=>$transactionID), "paymentID")) !== null) {
		return array("type"=>"SUPPLIERPAYMENT", "paymentID"=>$paymentID);
	}
	return array("type"=>"NONE");
}

function accountingFsck()
{
	$accounts = query("SELECT account.accountID AS accountID, account.balance AS balance, SUM(transactionLine.amount) AS sum FROM accountingAccount AS account LEFT JOIN accountingTransactionLine AS transactionLine USING(accountID) WHERE account.isDirectory = 0 GROUP BY account.accountID, account.balance")->fetchList();
	foreach($accounts as $account) {
		if($account["sum"] === null) {
			$account["sum"] = 0;
		}
		if($account["balance"] != $account["sum"]) {
			throw new AssertionError();
		}
	}
	
	$accounts = query("SELECT account.accountID AS accountID, account.balance AS balance, SUM(subaccount.balance) AS sum FROM accountingAccount AS account LEFT JOIN accountingAccount AS subaccount ON account.accountID = subaccount.parentAccountID AND account.currencyID = subaccount.currencyID WHERE account.isDirectory = 1 GROUP BY account.accountID, account.balance")->fetchList();
	foreach($accounts as $account) {
		if($account["sum"] === null) {
			$account["sum"] = 0;
		}
		if($account["balance"] != $account["sum"]) {
			throw new AssertionError();
		}
	}
	
	$transactions = query("SELECT transactionID, SUM(transactionLine.amount) AS sum FROM accountingTransaction AS transaction INNER JOIN accountingTransactionLine AS transactionLine USING(transactionID) INNER JOIN accountingAccount AS account USING(accountID) GROUP BY transactionID HAVING COUNT(DISTINCT account.currencyID) = 1")->fetchList();
	foreach($transactions as $transaction) {
		if($transaction["sum"] != 0) {
			throw new AssertionError();
		}
	}
}

function accountingCalculateTransactionAmount($transactionID, $accountID, $negate = false)
{
	$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	$currencySymbol = stdGet("accountingCurrency", array("currencyID"=>$currencyID), "symbol");
	if($currencyID != $GLOBALS["defaultCurrencyID"]) {
		$lines = stdList("accountingTransactionLine", array("transactionID"=>$transactionID), array("accountID", "amount"));
		$amount = 0;
		foreach($lines as $line) {
			if($line["accountID"] == $accountID) {
				$foreignAmount = $line["amount"];
			} else {
				$amount += -1 * $line["amount"];
			}
		}
		if($negate) {
			$foreignAmount = -1 * $foreignAmount;
			$amount = -1 * $amount;
		}
		$amountHtml = formatPrice($amount) . " / " . formatPrice($foreignAmount, $currencySymbol);
	} else {
		$amount = stdGet("accountingTransactionLine", array("transactionID"=>$transactionID, "accountID"=>$accountID), "amount");
		if($negate) {
			$amount = -1 * $amount;
		}
		$amountHtml = formatPrice($amount);
	}
	return $amountHtml;
}

function accountingAccountTree($accountID, $excludedAccountID = null)
{
	$accountIDSql = dbAddSlashes($accountID);
	$output = query("SELECT accountID, parentAccountID, accountingAccount.name AS name, description, isDirectory, balance, currencyID, accountingCurrency.symbol AS currencySymbol, accountingCurrency.name AS currencyName FROM accountingAccount INNER JOIN accountingCurrency USING(currencyID) WHERE accountID = '$accountIDSql'")->fetchArray();
	$output["subaccounts"] = array();
	foreach(stdList("accountingAccount", array("parentAccountID"=>$accountID), "accountID", array("isDirectory"=>"DESC", "name"=>"ASC")) as $subAccountID) {
		if($excludedAccountID !== null && $subAccountID["accountID"] == $excludedAccountID) {
			continue;
		}
		$output["subaccounts"][] = accountingAccountTree($subAccountID);
	}
	return $output;
}

function accountingFlattenAccountTree($tree, $parentID = null, $depth = 0)
{
	$id = "account-" . $tree["accountID"];
	$output = array();
	
	$output[] = array_merge($tree, array("id"=>$id, "parentID"=>$parentID, "depth"=>$depth));
	foreach($tree["subaccounts"] as $account) {
		$output = array_merge($output, accountingFlattenAccountTree($account, $id, $depth + 1));
	}
	return $output;
}

function accountingAccountOptions($rootNode = null, $allowEmpty = false)
{
	$accountList = array();
	if(is_array($rootNode)) {
		foreach($rootNode as $node) {
			$accountTree = accountingAccountTree($node);
			$accountList = array_merge($accountList, accountingFlattenAccountTree($accountTree));
		}
	} else {
		$rootNodes = stdList("accountingAccount", array("parentAccountID"=>$rootNode), "accountID");
		foreach($rootNodes as $rootNode) {
			$accountTree = accountingAccountTree($rootNode);
			$accountList = array_merge($accountList, accountingFlattenAccountTree($accountTree));
		}
	}
	
	$accountOptions = array();
	if($allowEmpty) {
		$accountOptions[] = array("label"=>"", "value"=>"");
	}
	foreach($accountList as $account) {
		$accountOptions[] = array("label"=>str_repeat("&nbsp;&nbsp;&nbsp;", $account["depth"]) . $account["name"], "value"=>$account["accountID"], "disabled"=>$account["isDirectory"] ? true : false);
	}
	
	return $accountOptions;
}

function accountingDepreciateFixedAsset($fixedAssetID, $until = null)
{
	if($until === null) {
		$until = time();
	}
	
	$fixedAsset = stdGet("accountingFixedAsset", array("fixedAssetID"=>$fixedAssetID), array("accountID", "depreciationAccountID", "name", "depreciationFrequencyBase", "depreciationFrequencyMultiplier", "nextDepreciationDate", "totalDepreciations", "performedDepreciations", "residualValuePercentage"));
	
	while(($fixedAsset["nextDepreciationDate"] < $until) && ($fixedAsset["performedDepreciations"] < $fixedAsset["totalDepreciations"])) {
		$fixedAsset["performedDepreciations"]++;
		$nextDate = billingCalculateNextDate($fixedAsset["nextDepreciationDate"], $fixedAsset["depreciationFrequencyBase"], $fixedAsset["depreciationFrequencyMultiplier"]);
		
		startTransaction();
		$value = stdGet("accountingAccount", array("accountID"=>$fixedAsset["accountID"]), "balance");
		$depreciatedValue = stdGet("accountingAccount", array("accountID"=>$fixedAsset["depreciationAccountID"]), "balance");
		$purchaseValue = $value + $depreciatedValue;
		$residualValue = round($purchaseValue * $fixedAsset["residualValuePercentage"] / 100);
		$targetValue = round($purchaseValue - (($purchaseValue - $residualValue) * $fixedAsset["performedDepreciations"] / $fixedAsset["totalDepreciations"]));
		
		if($value > $targetValue) {
			$startDate = date("d-m-Y", $fixedAsset["nextDepreciationDate"]);
			$endDate = date("d-m-Y", $nextDate);
			$amount = $value - $targetValue;
			
			accountingAddTransaction($fixedAsset["nextDepreciationDate"], "Depreciation of {$fixedAsset["name"]} for period $startDate - $endDate", array(
				array("accountID"=>$fixedAsset["accountID"], "amount"=>-$amount),
				array("accountID"=>$fixedAsset["depreciationAccountID"], "amount"=>$amount)
				));
		}
		
		$fixedAsset["nextDepreciationDate"] = $nextDate;
		
		stdSet("accountingFixedAsset", array("fixedAssetID"=>$fixedAssetID), array("nextDepreciationDate"=>$fixedAsset["nextDepreciationDate"], "performedDepreciations"=>$fixedAsset["performedDepreciations"]));
		commitTransaction();
	}
}

function accountingAutoDepreciate()
{
	if(!$GLOBALS["controlpanelEnableAssetDepreciation"]) {
		return;
	}
	foreach(stdList("accountingFixedAsset", array("automaticDepreciation"=>1), "fixedAssetID") as $fixedAssetID) {
		accountingDepreciateFixedAsset($fixedAssetID);
	}
}

function accountingFormatAccountPrice($accountID, $negate = false)
{
	$account = stdGet("accountingAccount", array("accountID"=>$accountID), array("balance", "currencyID"));
	$currency = stdGet("accountingCurrency", array("currencyID"=>$account["currencyID"]), "symbol");
	return formatPrice(($negate ? -1 : 1) * $account["balance"], $currency);
}

function accountingRecomputeBalancesAccount($accountID)
{
	$accountIDSql = dbAddSlashes($accountID);
	$account = stdGet("accountingAccount", array("accountID"=>$accountID), array("currencyID", "isDirectory"));
	if($account["isDirectory"] == 1) {
		foreach(stdList("accountingAccount", array("parentAccountID"=>$accountID), "accountID") as $subAccountID) {
			accountingRecomputeBalancesAccount($subAccountID);
		}
		$record = query("SELECT SUM(balance) AS total FROM accountingAccount WHERE parentAccountID = '$accountIDSql' AND currencyID='{$account["currencyID"]}'")->fetchArray();
		stdSet("accountingAccount", array("accountID"=>$accountID), array("balance"=>$record["total"]));
	} else {
		$record = query("SELECT SUM(amount) AS total FROM accountingTransactionLine WHERE accountID = '$accountIDSql'")->fetchArray();
		stdSet("accountingAccount", array("accountID"=>$accountID), array("balance"=>$record["total"]));
	}
}

function accountingRecomputeBalances()
{
	startTransaction();
	setQuery("LOCK TABLES accountingAccount WRITE, accountingTransaction WRITE, accountingTransactionLine WRITE");
	foreach(stdList("accountingAccount", array("parentAccountID"=>null), "accountID") as $accountID) {
		accountingRecomputeBalancesAccount($accountID);
	}
	setQuery("UNLOCK TABLES");
	commitTransaction();
}

?>