<?php

require_once("common.php");

function main()
{
	doMysql();
	
	$content = "<h1>Add database</h1>\n";
	
	$content .= mysqlBreadcrumbs(array(array("name"=>"Add database", "url"=>"{$GLOBALS["root"]}mysql/adddatabase.php")));
	
	$name = post("databaseName");
	
	if(!validDatabaseName($name)) {
		$content .= addDatabaseForm("Invalid database name", $name);
		die(page($content));
	}
	
	if(mysqlDatabaseExists($name)) {
		$content .= addDatabaseForm("A database with the chosen name already exists", $name);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= addDatabaseForm(null, $name);
		die(page($content));
	}
	
	mysqlCreateDatabase($name);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mysql/");
}

main();

?>