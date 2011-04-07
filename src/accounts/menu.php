<?php

require_once(dirname(__FILE__) . "/../common.php");

function menuAccounts()
{
	if($GLOBALS["menuComponent"] == "accounts") {
		return <<<HTML
<li>
<a href="{$GLOBALS["rootHtml"]}accounts/">Accounts</a>
<ul>
<li><a href="{$GLOBALS["rootHtml"]}accounts/addaccount.php">New account</a></li>
</ul>
</li>

HTML;
	} else {
		return "<li><a href=\"{$GLOBALS["rootHtml"]}accounts/\">Accounts</a></li>\n";
	}
}

function menuAccountsAdmin()
{
	if($GLOBALS["menuComponent"] == "accounts") {
		return <<<HTML
<li>
<a href="{$GLOBALS["rootHtml"]}accounts/">Admin Accounts</a>
<ul>
<li><a href="{$GLOBALS["rootHtml"]}accounts/addadminaccount.php">New admin account</a></li>
</ul>
</li>

HTML;
	} else {
		return "<li><a href=\"{$GLOBALS["rootHtml"]}accounts/\">Admin Accounts</a></li>\n";
	}
}

if(isRoot()) {
	addMenu("menuAccountsAdmin");
} else if(canAccessComponent("accounts", true)) {
	addMenu("menuAccounts");
} else if(canAccessComponent("accounts")) {
	addAdminMenu("menuAccounts");
}

?>