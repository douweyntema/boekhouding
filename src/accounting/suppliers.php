<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	$content = makeHeader(_("Suppliers"), suppliersBreadcrumbs());
	$content .= supplierList();
	echo page($content);
}

main();

?>