<?php

require_once(dirname(__FILE__) . "/../common.php");

function doMysql()
{
	useComponent("mysql");
	$GLOBALS["menuComponent"] = "mysql";
}

function crumb($name, $filename)
{
	return array("name"=>$name, "url"=>"{$GLOBALS["root"]}mysql/$filename");
}

function crumbs($name, $filename)
{
	return array(crumb($name, $filename));
}

function mysqlBreadcrumbs()
{
	return crumbs("MySQL", "");
}

function databaseList()
{
	$fileSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>customerID()), "fileSystemID");
	$phpMyAdmin = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "phpMyAdmin");
	
	$rows = array();
	foreach(mysqlListDatabases() as $database) {
		$rows[] = array(array("url"=>"{$phpMyAdmin}{$database}", "text"=>$database));
	}
	return listTable(array("Database name"), $rows, "sortable list");
}

function addDatabaseForm($error = "", $values = null)
{
	return operationForm("adddatabase.php", $error, "Add database", "Save",
		array(
			array("title"=>"Database name", "type"=>"text", "name"=>"databaseName")
		), $values);
}

function validDatabaseName($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	if(preg_match('/^[-a-zA-Z0-9_]*$/', $name) != 1) {
		return false;
	}
	return true;
}

?>