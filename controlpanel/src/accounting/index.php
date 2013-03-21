<?php

require_once("common.php");

function main()
{
	$content = makeHeader("Boekhouding", accountingBreadcrumbs());
	$content .= accountList();
	echo page($content);
}

accountingFsck();
main();

?>