<?php

require_once("common.php");

function main()
{
	doAccountsAdmin();
	
	$content = makeHeader(_("Accounts"), accountsBreadcrumbs());
	$content .= adminAccountList();
	$content .= addAdminAccountForm();
	echo page($content);
}

main();

?>