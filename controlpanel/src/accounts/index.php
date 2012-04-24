<?php

require_once("common.php");

function main()
{
	if(isRoot()) {
		doAccountsAdmin();
		
		$content = makeHeader("Admin Accounts", accountsBreadcrumbs());
		$content .= adminAccountList();
		$content .= addAdminAccountForm();
		echo page($content);
	} else {
		doAccounts();
		
		$content = makeHeader("Accounts", accountsBreadcrumbs());
		$content .= accountList();
		$content .= addAccountForm();
		echo page($content);
	}
}

main();

?>