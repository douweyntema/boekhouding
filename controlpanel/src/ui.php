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
		if(isset($values[$field["name"]]) && $values[$field["name"]] !== null) {
			$value = htmlentities($values[$field["name"]]);
			$output .= " value=\"$value\"";
		}
		$output .= " />";
	} else if($field["type"] == "textarea") {
		$output .= "<textarea name=\"{$field["name"]}\"";
		if($readOnly) {
			$output .= " readonly=\"readonly\"";
		}
		$output .= ">";
		if(isset($values[$field["name"]]) && $values[$field["name"]] !== null) {
			$output .= htmlentities($values[$field["name"]]);
		}
		$output .= "</textarea>";
	} else if($field["type"] == "password") {
		if($readOnly) {
			$oldName = $field["name"] . "-1";
			if(isset($values[$oldName]) && $values[$oldName] !== null) {
				$password = $values[$oldName];
			} else {
				$password = null;
			}
			$output .= "<input type=\"password\"";
			if($password !== null) {
				$masked = str_repeat("*", strlen($password));
				$output .= " value=\"$masked\"";
			}
			$output .= " readonly=\"readonly\" />";
			if($password !== null) {
				$encryptedPassword = encryptPassword($password);
				$output .= "<input type=\"hidden\" name=\"encrypted-{$field["name"]}\" value=\"$encryptedPassword\" />";
			}
		} else {
			$name = (isset($field["confirm"]) && $field["confirm"]) ? $field["name"] . "-1" : $field["name"] . "-2";
			$output .= "<input type=\"password\" name=\"$name\" />";
		}
	} else if($field["type"] == "checkbox") {
	
	} else if($field["type"] == "radio") {
		$first = true;
		foreach($field["options"] as $option) {
			if(!$first) {
				$output .= "<br />\n";
			} else {
				$first = false;
			}
			$output .= "<label><input type=\"radio\" value=\"{$option["value"]}\"";
			if(isset($values[$field["name"]]) && $values[$field["name"]] == $option["value"]) {
				$output .= " checked=\"checked\"";
			}
			if($readOnly) {
				$output .= " disabled=\"disabled\"";
			} else {
				$output .= " name=\"{$field["name"]}\"";
			}
			$output .= " />{$option["title"]}</label>";
		}
		if($readOnly && isset($values[$field["name"]])) {
			$output .= "<input type=\"hidden\" name=\"{$field["name"]}\" value=\"{$values[$field["name"]]}\" />";
		}
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

function operationForm($postUrl, $error, $title, $submitCaption, $fields, $values, $messages = null)
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
	
	if($values === null) {
		$values = array();
	}
	
	if($messages !== null) {
		if(isset($messages["confirmdelete"])) {
			$messageHtml .= "<p class=\"confirmdelete\">" . $messages["confirmdelete"] . "</p>\n";
		}
		if(isset($messages["confirmbilling"])) {
			$messageHtml .= "<p class=\"confirmbilling\">" . $messages["confirmbilling"] . "</p>\n";
		}
		if(isset($messages["custom"])) {
			$messageHtml .= $messages["custom"];
		}
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
		if($field["type"] == "hidden") {
			if(isset($values[$field["name"]])) {
				$valueHtml = htmlentities($values[$field["name"]]);
				$content .= "<input type=\"hidden\" name=\"{$field["name"]}\" value=\"$valueHtml\" />\n";
			}
			continue;
		}
		if($field["type"] != "multipart") {
			$field["fill"] = true;
		}
		if(isset($field["rowclass"])) {
			$rowclass = " class=\"{$field["rowclass"]}\"";
		} else {
			$rowclass = "";
		}
		$content .= "<tr$rowclass>\n<th>{$field["title"]}:</th>\n" . renderField($field, $values, $readOnly, $columns - 1) . "</tr>\n";
		if($field["type"] == "password" && !$readOnly) {
			$field["confirm"] = true;
			$content .= "<tr$rowclass>\n<th>{$field["confirmtitle"]}:</th>\n" . renderField($field, $values, $readOnly, $columns - 1) . "</tr>\n";
		}
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

?>