<?php

require_once(dirname(__FILE__) . "/../common.php");

function crumb($name, $filename)
{
	return array("name"=>$name, "url"=>"{$GLOBALS["root"]}accounting/$filename");
}

function crumbs($name, $filename)
{
	return array(crumb($name, $filename));
}

function accountingBreadcrumbs()
{
	return crumbs("Boekhouding", "");
}

function accountList()
{
	$rootNodes = $GLOBALS["database"]->stdList("accountingAccount", array("parentAccountID"=>null), "accountID");
	$accountList = array();
	foreach($rootNodes as $rootNode) {
		$accountTree = accountTree($rootNode);
		$accountList = array_merge($accountList, flattenAccountTree($accountTree));
	}
	
	
	$rows = array();
	foreach($accountList as $account) {
		$rows[] = array("id"=>$account["id"], "class"=>($account["parentID"] === null ? null : "child-of-account-{$account["parentAccountID"]}"), "cells"=>array(
			array("url"=>"account.php?id={$account["accountID"]}", "text"=>$account["name"]),
			array("html"=>formatPrice($account["balance"])),
		));
	}
	return listTable(array("Rekening", "Saldo"), $rows, null, true, "list tree");
}

function accountTree($accountID)
{
	$output = $GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$accountID), array("accountID", "name", "description", "isDirectory", "currency", "balance"));
	$output["subaccounts"] = subAccountTrees($accountID);
	return $output;
}

function subAccountTrees($parentAccountID)
{
	$output = array();
	foreach($GLOBALS["database"]->stdList("accountingAccount", array("parentAccountID"=>$parentAccountID), array("accountID", "parentAccountID", "name", "description", "isDirectory", "currency", "balance")) as $subaccount) {
		$subaccount["subaccounts"] = subAccountTrees($subaccount["accountID"]);
		$output[] = $subaccount;
	}
	return $output;
}

function flattenAccountTree($tree, $parentID = null)
{
	$id = "account-" . $tree["accountID"];
	$output = array();
	
	$output[] = array_merge($tree, array("id"=>$id, "parentID"=>$parentID));
	foreach($tree["subaccounts"] as $account) {
		$output = array_merge($output, flattenAccountTree($account, $id));
	}
	return $output;
}


?>