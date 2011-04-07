<?php

require_once("common.php");

function main()
{
	if(isRoot()) {
		doAccountsAdmin(null);
		
		$content = "<h1>Admin Accounts</h1>\n";
		
		$content .= adminAccountList();
	} else {
		doAccounts(null);
		
		$content = "<h1>Accounts</h1>\n";
		
		$content .= accountList();
	}
	
	echo page($content);
}

main();

?>