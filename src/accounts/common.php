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
	useCustomer(stdGetTry("adminUser", array("userID"=>$userID), "customerID", false));
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
	return crumbs(_("Accounts"), "");
}

function accountBreadcrumbs($userID)
{
	return array_merge(accountsBreadcrumbs(), crumbs(stdGet("adminUser", array("userID"=>$userID), "username"), "adminaccount.php?id=$userID"));
}

function adminAccountList()
{
	$rows = array();
	foreach(stdList("adminUser", array(), array("userID", "username"), array("username"=>"ASC")) as $account) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}accounts/adminaccount.php?id={$account["userID"]}", "text"=>$account["username"])
		);
	}
	return listTable(array(_("Account name")), $rows, null, true, "list sortable");
}

function addAdminAccountForm($error = "", $values = null)
{
	return operationForm("addadminaccount.php", $error, _("Add admin account"), _("Add"),
		array(
			array("title"=>_("Username"), "type"=>"text", "name"=>"username"),
			array("title"=>_("Password"), "type"=>"password", "name"=>"password", "confirmtitle"=>_("Confirm password")),
		),
		$values);
}

function changeAdminAccountPasswordForm($userID, $error = "", $values = null)
{
	return operationForm("editadminpassword.php?id=$userID", $error, _("Change password"), _("Change Password"), array(
		array("title"=>_("Password"), "type"=>"password", "name"=>"password", "confirmtitle"=>_("Confirm password")),
	), $values);
}

function removeAdminAccountForm($userID, $error, $values = null)
{
	return operationForm("removeadminaccount.php?id=$userID", $error, _("Remove admin account"), _("Remove Account"), array(), $values, array("confirmdelete"=>_("Are you sure you want to remove this admin account?")));
}

?>