<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	$content = makeHeader("Boekhouding", accountingBreadcrumbs());
	$content .= accountList();
	echo page($content);
}

accountingFsck();
main();

?>