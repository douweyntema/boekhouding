<?php

require_once("common.php");

function main()
{
	doMysql();
	
	$check = function($condition, $error) {
		$title = "<h1>Add database</h1>\n";
		$breadcrumbs = mysqlBreadcrumbs(array(array("name"=>"Add database", "url"=>"{$GLOBALS["root"]}mysql/adddatabase.php")));
		if(!$condition) die(page($title . $breadcrumbs . addDatabaseForm($error, $_POST)));
	};
	
	$name = post("databaseName");
	
	$check(validDatabaseName($name), "Invalid database name");
	$check(!mysqlDatabaseExists($name), "A database with the chosen name already exists");
	$check(post("confirm") !== null, null);
	
	mysqlCreateDatabase($name);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mysql/");
}

main();

?>