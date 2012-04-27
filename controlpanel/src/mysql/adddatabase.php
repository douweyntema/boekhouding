<?php

require_once("common.php");

function main()
{
	doMysql();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader("Add database", mysqlBreadcrumbs(), crumbs("Add database", "adddatabase.php")) . addDatabaseForm($error, $_POST)));
	};
	
	$name = post("databaseName");
	
	$check(validDatabaseName($name), "Invalid database name.");
	$check(!mysqlDatabaseExists($name), "A database with the chosen name already exists.");
	$check(post("confirm") !== null, null);
	
	mysqlCreateDatabase($name);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mysql/");
}

main();

?>