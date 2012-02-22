<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	doMysql();
	
	$content = "<h1>MySQL</h1>\n";
	
	$content .= mysqlBreadcrumbs();
	
	$content .= databaseList();
	$content .= addDatabaseForm();
	
	echo page($content);
}

main();

?>