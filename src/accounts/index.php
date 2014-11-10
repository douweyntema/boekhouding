<?php

require_once("common.php");

function main()
{
	doAccountsAdmin();
	
		$content = makeHeader("Admin Accounts", accountsBreadcrumbs());
	$content .= adminAccountList();
	$content .= addAdminAccountForm();
	echo page($content);
}

main();

?>