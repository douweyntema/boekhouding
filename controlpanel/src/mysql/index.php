<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	doMysql();
	
	$content = makeHeader("MySQL", mysqlBreadcrumbs());
	$content .= databaseList();
	$content .= addDatabaseForm();
	echo page($content);
}

main();

?>