<?php

require_once(dirname(__FILE__) . "/common.php");

if(isRoot()) {
	addMenu("menuAccountsAdmin");
} else if(canAccessComponent("accounts", true)) {
	addMenu("menuAccounts");
} else if(canAccessComponent("accounts")) {
	addAdminMenu("menuAccounts");
}

?>