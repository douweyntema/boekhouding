<?php

function renderField($field, $values, $readOnly, $columns)
{
	if($field["type"] == "multipart") {
		$output = "";
		foreach($field["parts"] as $part) {
			$output .= renderField($part, $values, $readOnly, $columns - count($field["parts"]) + 1);
		}
		return $output;
	}
	
	if($field["type"] == "custom") {
		return $field["html"];
	}
	
	$output = "<td";
	if(isset($field["fill"]) && $field["fill"]) {
		if($columns != 1) {
			$output .= " colspan=\"$columns\"";
		}
		if(isset($field["class"])) {
			$output .= " class=\"{$field["class"]} stretch\"";
		} else {
			$output .= " class=\"stretch\"";
		}
	} else {
		if(isset($field["class"])) {
			$output .= " class=\"{$field["class"]}\"";
		}
	}
	$output .= ">";
	
	if(isset($field["header"])) {
		$output .= $field["header"];
	}
	
	if($field["type"] == "text") {
		$output .= "<input type=\"text\" name=\"{$field["name"]}\"";
		if($readOnly) {
			$output .= " readonly=\"readonly\"";
		}
		if(isset($values[$field["name"]])) {
			$value = htmlentities($values[$field["name"]]);
			$output .= " value=\"$value\"";
		}
		$output .= " />";
	} else if($field["type"] == "checkbox") {
	
	} else if($field["type"] == "dropdown") {
	
	} else if($field["type"] == "label") {
		$output .= $field["html"];
	} else {
		die("Invalid field type {$field["type"]}");
	}
	
	if(isset($field["footer"])) {
		$output .= $field["footer"];
	}
	
	$output .= "</td>\n";
	return $output;
}

function operationForm($postUrl, $error, $title, $submitCaption, $fields, $values)
{
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readOnly = true;
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readOnly = false;
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readOnly = false;
	}
	
	$content = "";
	$columns = 2;
	foreach($fields as $field) {
		if($field["type"] == "multipart") {
			if(count($field["parts"]) + 1 > $columns) {
				$columns = count($field["parts"]) + 1;
			}
		}
	}
	foreach($fields as $field) {
		$field["fill"] = true; // Ignored for multipart
		$content .= "<tr>\n<th>{$field["title"]}:</th>\n" . renderField($field, $values, $readOnly, $columns - 1) . "</tr>\n";
	}
	
	return <<<HTML
<div class="operation">
<h2>$title</h2>
$messageHtml<form action="$postUrl" method="post">
$confirmHtml<table>
$content<tr class="submit"><td colspan="$columns"><input type="submit" value="$submitCaption" /></td></tr>
</table>
</form>
</div>

HTML;
}

/*
$domainID = 17;

echo <<<HTML
<html>
<head>
<link rel="stylesheet" type="text/css" href="/controlpanel/layout.css" />
</head>
<body>

HTML;

echo operationForm("ui.php?id=$domainID", "", "Add alias", "Save",
	array(
		array("title"=>"Alias", "type"=>"multipart", "parts"=>array(
			array("type"=>"text", "name"=>"localpart", "fill"=>true),
			array("type"=>"label", "html"=>"@henk.nl")
		)),
		array("title"=>"Target address", "type"=>"text", "name"=>"targetAddress")
	),
	$_POST);
*/