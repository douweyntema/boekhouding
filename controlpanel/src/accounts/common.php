<?php

require_once(dirname(__FILE__) . "/../common.php");

function doAccounts()
{
	useComponent("accounts");
	$GLOBALS["menuComponent"] = "accounts";
}

function doAccount($userID)
{
	doAccounts();
	useCustomer($GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$userID), "customerID", false));
}

function doAccountsAdmin()
{
	doAccounts();
	useCustomer(0);
}

function crumb($name, $filename)
{
	return array("name"=>$name, "url"=>"{$GLOBALS["root"]}accounts/$filename");
}

function crumbs($name, $filename)
{
	return array(crumb($name, $filename));
}

function accountsBreadcrumbs()
{
	return crumbs("Accounts", "");
}

function accountBreadcrumbs($userID)
{
	return array_merge(accountsBreadcrumbs(), crumbs($GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "username"), "account.php?id=$userID"));
}

function accountList()
{
	$rows = array();
	foreach($GLOBALS["database"]->stdList("adminUser", array("customerID"=>customerID()), array("userID", "username"), array("username"=>"ASC")) as $account) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}accounts/account.php?id={$account["userID"]}", "text"=>$account["username"]),
			($GLOBALS["database"]->stdExists("adminUserRight", array("userID"=>$account["userID"], "customerRightID"=>null))) ? "Full access" : "Limited rights"
		);
	}
	return listTable(array("Account name", "Type"), $rows, null, array("Accounts", "No accounts have been created."), "list sortable");
}

function adminAccountList()
{
	$rows = array();
	foreach($GLOBALS["database"]->stdList("adminUser", array("customerID"=>null), array("userID", "username"), array("username"=>"ASC")) as $account) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}accounts/adminaccount.php?id={$account["userID"]}", "text"=>$account["username"])
		);
	}
	return listTable(array("Account name"), $rows, null, true, "list sortable");
}

function addAccountForm($error = "", $values = null)
{
	$rights = array();
	foreach(rights() as $right) {
		if(!$GLOBALS["database"]->stdExists("adminCustomerRight", array("customerID"=>customerID(), "right"=>$right["name"]))) {
			continue;
		}
		$rights[] = array("type"=>"checkbox", "label"=>htmlentities($right["description"]), "name"=>"right-{$right["name"]}");
	}
	if($values === null) {
		$values = array("rights"=>"full");
	}
	return operationForm("addaccount.php", $error, "Add account", "Add",
		array(
			array("title"=>"Username", "type"=>"text", "name"=>"username"),
			array("title"=>"Password", "type"=>"password", "name"=>"password", "confirmtitle"=>"Confirm password"),
			array("title"=>"Rights", "type"=>"subformchooser", "name"=>"rights", "rowclass"=>"collapse-disable", "subforms"=>array(
				array("value"=>"full", "label"=>"Full access", "subform"=>array()),
				array("value"=>"limited", "label"=>"Limited rights", "subform"=>$rights)
			))
		),
		$values);
}

function addAdminAccountForm($error = "", $values = null)
{
	return operationForm("addadminaccount.php", $error, "Add admin account", "Add",
		array(
			array("title"=>"Username", "type"=>"text", "name"=>"username"),
			array("title"=>"Password", "type"=>"password", "name"=>"password", "confirmtitle"=>"Confirm password"),
		),
		$values);
}

function changeAccountRightsForm($userID, $error = "", $values = null)
{
	if($values === null) {
		$values = array();
		if($GLOBALS["database"]->stdExists("adminUserRight", array("userID"=>$userID, "customerRightID"=>null))) {
			$values["rights"] = "full";
		} else {
			$values["rights"] = "limited";
			foreach($GLOBALS["database"]->stdList("adminCustomerRight", array("customerID"=>customerID()), array("customerRightID", "right")) as $right) {
				$values["right-{$right["right"]}"] = $GLOBALS["database"]->stdExists("adminUserRight", array("userID"=>$userID, "customerRightID"=>$right["customerRightID"])) ? "checked" : null;
			}
		}
	}
	$rights = array();
	foreach(rights() as $right) {
		if(!$GLOBALS["database"]->stdExists("adminCustomerRight", array("customerID"=>customerID(), "right"=>$right["name"]))) {
			continue;
		}
		$rights[] = array("type"=>"checkbox", "label"=>htmlentities($right["description"]), "name"=>"right-{$right["name"]}");
	}
	return operationForm("editrights.php?id=$userID", $error, "Change account access rights", "Change",
		array(
			array("title"=>"Rights", "type"=>"subformchooser", "name"=>"rights", "rowclass"=>"collapse-disable", "subforms"=>array(
				array("value"=>"full", "label"=>"Full access", "subform"=>array()),
				array("value"=>"limited", "label"=>"Limited rights", "subform"=>$rights)
			))
		),
		$values);
}

function changeAccountPasswordForm($userID, $error = "", $values = null)
{
	return operationForm("editpassword.php?id=$userID", $error, "Change password", "Change Password", array(
		array("title"=>"Password", "type"=>"password", "name"=>"password", "confirmtitle"=>"Confirm password"),
	), $values);
}

function changeAdminAccountPasswordForm($userID, $error = "", $values = null)
{
	return operationForm("editadminpassword.php?id=$userID", $error, "Change password", "Change Password", array(
		array("title"=>"Password", "type"=>"password", "name"=>"password", "confirmtitle"=>"Confirm password"),
	), $values);
}

function removeAccountForm($userID, $error = "", $values = null)
{
	return operationForm("removeaccount.php?id=$userID", $error, "Remove account", "Remove Account", array(), $values, array("confirmdelete"=>"Are you sure you want to remove this account?"));
}

function removeAdminAccountForm($userID, $error, $values = null)
{
	return operationForm("removeadminaccount.php?id=$userID", $error, "Remove admin account", "Remove Account", array(), $values, array("confirmdelete"=>"Are you sure you want to remove this admin account?"));
}

?>