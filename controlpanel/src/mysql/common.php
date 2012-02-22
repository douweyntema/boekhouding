<?php

require_once(dirname(__FILE__) . "/../common.php");

function doMysql()
{
	useComponent("mysql");
	$GLOBALS["menuComponent"] = "mysql";
}

function mysqlBreadcrumbs($postfix = array())
{
	$crumbs = array();
	$crumbs[] = array("name"=>"MySQL", "url"=>"{$GLOBALS["root"]}mysql/");
	return breadcrumbs(array_merge($crumbs, $postfix));
}

function databaseList()
{
	$output = "";
	
	$fileSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>customerID()), "fileSystemID");
	$phpMyAdmin = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "phpMyAdmin");
	
	$databases = mysqlListDatabases();
	
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Database name</th></tr>
</thead>
<tbody>
HTML;
	foreach($databases as $database) {
		$output .= "<tr><td><a href=\"{$phpMyAdmin}{$database}\" target=\"_blank\">{$database}</a></td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function addDatabaseForm($error = "", $databaseName = null)
{
	$databaseNameValue = inputValue($databaseName);
	
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = "readonly=\"readonly\"";
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
	}
	
	return <<<HTML
<div class="operation">
<h2>Add database</h2>
$messageHtml
<form action="adddatabase.php" method="post">
$confirmHtml
<table>
<tr>
<th>Database name:</th>
<td class="stretch"><input type="text" name="databaseName" $readonly $databaseNameValue /></td>
</tr>
<tr class="submit"><td colspan="3"><input type="submit" value="Save" /></td></tr>
</table>
</form>
</div>

HTML;
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