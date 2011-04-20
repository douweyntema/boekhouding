<?php

require_once("common.php");

function main()
{
	if(isRoot()) {
		doAccountsAdmin(null);
		
		$content = "<h1>Admin Accounts</h1>\n";
		
		$content .= adminAccountList();
		
		$content .= addAdminAccountForm();
	} else {
		doAccounts(null);
		
		$content = "<h1>Accounts</h1>\n";
		
		$content .= accountList();
		
		$content .= addAccountForm();
	}
	
	echo page($content);
}

main();

?>