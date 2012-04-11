<?php

function getHtmlID()
{
	static $id = 1;
	return "l" . ($id++);
}

function getField($key/*, sources...*/)
{
	$sources = func_get_args();
	array_shift($sources);
	foreach($sources as $source) {
		if(isset($source[$key])) {
			return $source[$key];
		}
	}
	return null;
}

function renderCell($cell, $values, $readOnly)
{
	$output = array();
	if(isset($cell["cellclass"])) {
		$output["cellclass"] = $cell["cellclass"];
	}
	$output["content"] = "";
	
	if(isset($cell["name"])) {
		$oldName = isset($cell["confirm"]) ? "{$cell["name"]}-{$cell["confirm"]}" : $cell["name"];
		$value = isset($values[$oldName]) ? $values[$oldName] : null;
		$valueHtml = $value === null ? null : htmlentities($value);
		$name = $readOnly ? $cell["name"] : $oldName;
	}
	$fieldClass = isset($cell["fieldClass"]) ? $cell["fieldClass"] : null;
	
	if($cell["type"] == "html") {
		$output["content"] = $cell["html"];
	} else if($cell["type"] == "label") {
		$output["content"] .= "<label for=\"{$cell["id"]}\">{$cell["label"]}</label>";
	} else if($cell["type"] == "text") {
		$output["content"] = "<input type=\"text\" name=\"$name\"";
		if($fieldClass !== null) {
			$output["content"] .= " class=\"$fieldClass\"";
		}
		if($readOnly) {
			$output["content"] .= " readonly=\"readonly\"";
		}
		if($value !== null) {
			$output["content"] .= " value=\"$valueHtml\"";
		}
		$output["content"] .= " />";
	} else if($cell["type"] == "textarea") {
		$output["content"] = "<textarea name=\"$name\"";
		if($fieldClass !== null) {
			$output["content"] .= " class=\"$fieldClass\"";
		}
		if($readOnly) {
			$output["content"] .= " readonly=\"readonly\"";
		}
		$output["content"] .= ">";
		if($value !== null) {
			$output["content"] .= $valueHtml;
		}
		$output["content"] .= "</textarea>";
	} else if($cell["type"] == "password") {
		if($readOnly) {
			$output["content"] = "<input type=\"password\" readonly=\"readonly\"";
			if($fieldClass !== null) {
				$output["content"] .= " class=\"$fieldClass\"";
			}
			if($value !== null) {
				$masked = str_repeat("*", strlen($value));
				$output["content"] .= " value=\"$masked\"";
			}
			$output["content"] .= " />";
			if($value !== null) {
				$encryptedPassword = encryptPassword($value);
				$output["content"] .= "<input type=\"hidden\" name=\"encrypted-$name\" value=\"$encryptedPassword\" />";
			}
		} else {
			$output["content"] = "<input type=\"password\" name=\"$name\"";
			if($fieldClass !== null) {
				$output["content"] .= " class=\"$fieldClass\"";
			}
			$output["content"] .= " />";
		}
	} else if($cell["type"] == "radioentry") {
		$output["content"] = "<label><input type=\"radio\" value=\"{$cell["value"]}\"";
		if($value == $cell["value"]) {
			$output["content"] .= " checked=\"checked\"";
		}
		if($readOnly) {
			$output["content"] .= " disabled=\"disabled\"";
		} else {
			$output["content"] .= " name=\"$name\"";
		}
		if($fieldClass !== null) {
			$output["content"] .= " class=\"$fieldClass\"";
		}
		$output["content"] .= " /> {$cell["label"]}</label>";
		if($readOnly && ($value == $cell["value"])) {
			$output["content"] .= "<input type=\"hidden\" name=\"$name\" value=\"$valueHtml\" />";
		}
	} else if($cell["type"] == "bareradioentry") {
		$output["content"] = "<input type=\"radio\" value=\"{$cell["value"]}\" id=\"{$cell["id"]}\"";
		if($value == $cell["value"]) {
			$output["content"] .= " checked=\"checked\"";
		}
		if($readOnly) {
			$output["content"] .= " disabled=\"disabled\"";
		} else {
			$output["content"] .= " name=\"$name\"";
		}
		if($fieldClass !== null) {
			$output["content"] .= " class=\"$fieldClass\"";
		}
		$output["content"] .= " />";
		if($readOnly && ($value == $cell["value"])) {
			$output["content"] .= "<input type=\"hidden\" name=\"$name\" value=\"$valueHtml\" />";
		}
	} else if($cell["type"] == "checkbox") {
		$output["content"] = "<label><input type=\"checkbox\"";
		if(!isset($cell["value"]) || $cell["value"] === null) {
			$valueHtml = "1";
			$checked = ($value !== null);
		} else {
			$valueHtml = htmlentities($cell["value"]);
			$checked = ($value == $cell["value"]);
		}
		$output["content"] .= " value=\"$valueHtml\"";
		if($checked) {
			$output["content"] .= " checked=\"checked\"";
		}
		if($fieldClass !== null) {
			$output["content"] .= " class=\"$fieldClass\"";
		}
		if($readOnly) {
			$output["content"] .= " disabled=\"disabled\"";
		} else {
			$output["content"] .= " name=\"$name\"";
		}
		$output["content"] .= " /> {$cell["label"]}</label>";
		if($readOnly && $checked) {
			$output["content"] .= "<input type=\"hidden\" name=\"$name\" value=\"$valueHtml\" />";
		}
	} else if($cell["type"] == "dropdown") {
		$output["content"] = "<select name=\"$name\"";
		if($fieldClass !== null) {
			$output["content"] .= " class=\"$fieldClass\"";
		}
		if($readOnly) {
			$output["content"] .= " readonly=\"readonly\"";
		}
		$output["content"] .= ">\n";
		foreach($cell["options"] as $option) {
			if($readOnly && $option["value"] != $value) {
				continue;
			}
			$valueHtml = htmlentities($option["value"]);
			$output["content"] .= "<option value=\"$valueHtml\"";
			if($option["value"] == $value) {
				$output["content"] .= " selected=\"selected\"";
			}
			$output["content"] .= ">{$option["label"]}</option>\n";
		}
		$output["content"] .= "</select>";
	} else {
		die("Invalid field type {$cell["type"]}");
	}
	
	if(isset($cell["header"]) && $cell["header"] !== null) {
		$output["content"] = $cell["header"] . $output["content"];
	}
	if(isset($cell["footer"]) && $cell["footer"] !== null) {
		$output["content"] .= $cell["footer"];
	}
	
	return $output;
}

function renderRow($row, $values, $readOnly)
{
	$output = array();
	$output["cells"] = array();
	if($row["type"] == "colspan") {
		foreach($row["columns"] as $column) {
			$c = $column;
			$c["fieldclass"] = getField("fieldclass", $column, $row);
			$c["cellclass"] = getField("cellclass", $column, $row);
			
			$cell = renderCell($c, $values, $readOnly);
			if(isset($column["fill"]) && $column["fill"]) {
				$cell["width"] = "stretch";
			} else {
				$cell["width"] = null;
			}
			$output["cells"][] = $cell;
		}
	} else if($row["type"] == "splitradioentry") {
		$c = $row;
		$c["type"] = "bareradioentry";
		$cell = renderCell($c, $values, $readOnly);
		$cell["width"] = "left-merge";
		$output["cells"][] = $cell;
		
		$c["type"] = "label";
		$cell = renderCell($c, $values, $readOnly);
		$cell["width"] = "stretch";
		$output["cells"][] = $cell;
	} else {
		$cell = renderCell($row, $values, $readOnly);
		$cell["width"] = "stretch";
		$output["cells"] = array($cell);
	}
	if(isset($row["rowclass"])) {
		$output["rowclass"] = $row["rowclass"];
	}
	return $output;
}

function renderRowspan($rowspan, $values, $readOnly)
{
	if($rowspan["type"] == "rowspan") {
		$rows = array();
		foreach($rowspan["rows"] as $row) {
			$row["rowclass"] = getField("rowclass", $row, $rowspan);
			$rows[] = renderRow($row, $values, $readOnly);
		}
		return $rows;
	} else if($rowspan["type"] == "subformchooser") {
		$rows = array();
		foreach($rowspan["subforms"] as $subform) {
			$rows[] = renderRow(array("type"=>"splitradioentry", "name"=>$rowspan["name"], "value"=>$subform["value"], "label"=>$subform["label"], "id"=>$subform["id"]), $values, $readOnly);
			foreach($subform["subform"] as $subfield) {
				if(!isset($subfield["title"])) {
					$f = $subfield;
					$f["fieldclass"] = getField("fieldclass", $subfield, $subform, $rowspan);
					$f["cellclass"] = getField("cellclass", $subfield, $subform, $rowspan);
					$f["rowclass"] = getField("rowclass", $subfield, $subform, $rowspan);
					if($f["rowclass"] === null) {
						$f["rowclass"] = "if-selected-{$subform["id"]}";
					} else {
						$f["rowclass"] .= " if-selected-{$subform["id"]}";
					}
					
					$row = renderRow($f, $values, $readOnly);
					$row["cells"] = array_merge(array("width"=>"left-merge"), $row["cells"]);
					$rows[] = $row;
				}
			}
		}
		return $rows;
	} else if($rowspan["type"] == "radio") {
		$rows = array();
		foreach($rowspan["options"] as $option) {
			$row = array("type"=>"radioentry", "name"=>$rowspan["name"], "value"=>$option["value"], "label"=>$option["label"]);
			$row["fieldclass"] = getField("fieldclass", $option, $rowspan);
			$row["cellclass"] = getField("cellclass", $option, $rowspan);
			$row["rowclass"] = getField("rowclass", $option, $rowspan);
			
			$rows[] = renderRow($row, $values, $readOnly);
		}
		return $rows;
	} else {
		return array(renderRow($rowspan, $values, $readOnly));
	}
}

function renderTable($fields, $values, $readOnly, $submitCaption = null, $submitName = null)
{
	foreach($fields as $key=>$value) {
		if($value["type"] == "subformchooser") {
			foreach($value["subforms"] as $i=>$subform) {
				if(!isset($subform["id"])) {
					$fields[$key]["subforms"][$i]["id"] = getHtmlID();
				}
			}
		}
	}
	
	$rowspans = array();
	foreach($fields as $field) {
		$f = $field;
		$rowspan = array();
		if(!isset($field["title"]) || $field["title"] === null) {
			$rowspan["title"] = null;
		} else {
			$rowspan["title"] = $field["title"];
		}
		
		if(isset($field["titleclass"])) {
			$rowspan["titleclass"] = $field["titleclass"];
		}
		
		if(isset($field["confirmtitle"])) {
			$f["confirm"] = 1;
		}
		
		$rowspan["rows"] = renderRowspan($f, $values, $readOnly);
		$rowspans[] = $rowspan;
		
		if(isset($field["confirmtitle"]) && !$readOnly) {
			$f = $field;
			$f["confirm"] = 2;
			$rowspan = array();
			$rowspan["title"] = $field["confirmtitle"];
			$rowspan["rows"] = renderRowspan($f, $values, $readOnly);
			$rowspans[] = $rowspan;
		}
		
		if($field["type"] == "subformchooser") {
			foreach($field["subforms"] as $subform) {
				foreach($subform["subform"] as $subfield) {
					if(isset($subfield["title"])) {
						$rowspan = array();
						$rowspan["title"] = $subfield["title"];
						if(isset($field["titleclass"])) {
							$rowspan["titleclass"] = $field["titleclass"];
						}
						if(isset($subfield["rowclass"]) && $subfield["rowclass"] !== null) {
							$subfield["rowclass"] .= " if-selected-{$subform["id"]}";
						} else {
							$subfield["rowclass"] = "if-selected-{$subform["id"]}";
						}
						
						$rowspan["rows"] = renderRowspan($subfield, $values, $readOnly);
						$rowspans[] = $rowspan;
					}
				}
			}
		}
	}
	
	$leftMergeUsed = false;
	$maxLeftFields = 0;
	$maxRightFields = 0;
	foreach($rowspans as $rowspan) {
		foreach($rowspan["rows"] as $row) {
			$leftFields = 0;
			$rightFields = 0;
			if(isset($rowspan["title"]) && $rowspan["title"] !== null) {
				$leftFields++;
			}
			
			$stretchSeen = false;
			foreach($row["cells"] as $cell) {
				if(isset($cell["width"]) && $cell["width"] == "left-merge") {
					$leftMergeUsed = true;
				} else if(isset($cell["width"]) && $cell["width"] == "stretch") {
					$stretchSeen = true;
				} else if($stretchSeen) {
					$rightFields++;
				} else {
					$leftFields++;
				}
			}
			if($leftFields > $maxLeftFields) {
				$maxLeftFields = $leftFields;
			}
			if($rightFields > $maxRightFields) {
				$maxRightFields = $rightFields;
			}
		}
	}
	if($leftMergeUsed) {
		$maxLeftFields += 1;
	}
	
	$output = "<table>\n";
	if($maxLeftFields == 1) {
		$output .= "<col />";
	} else if($maxLeftFields > 0) {
		$output .= "<col span=\"$maxLeftFields\" />";
	}
	$output .= "<col style=\"width: 100%;\" />";
	if($maxRightFields == 1) {
		$output .= "<col />";
	} else if($maxRightFields > 0) {
		$output .= "<col span=\"$maxRightFields\" />";
	}
	
	foreach($rowspans as $rowspan) {
		$hasTitle = (isset($rowspan["title"]) && $rowspan["title"] !== null);
		$first = true;
		
		foreach($rowspan["rows"] as $row) {
			$output .= "<tr";
			if(isset($row["rowclass"]) && $row["rowclass"] !== null) {
				$output .= " class=\"{$row["rowclass"]}\"";
			}
			$output .= ">";
			
			if($first) {
				if($hasTitle) {
					$output .= "<th";
					if(count($rowspan["rows"]) != 1) {
						$rows = count($rowspan["rows"]);
						$output .= " rowspan=\"$rows\"";
					}
					if(isset($rowspan["titleclass"]) && $rowspan["titleclass"] !== null) {
						$output .= " class=\"{$rowspan["titleclass"]}\"";
					}
					$output .= ">";
					if($rowspan["title"] != "") {
						$output .= $rowspan["title"] . ":";
					}
					$output .= "</th>";
				}
				$first = false;
			}
			$output .= "\n";
			
			$stretchWidth = $maxLeftFields + $maxRightFields + 1;
			if($hasTitle) {
				$stretchWidth -= 1;
			}
			if($leftMergeUsed && !(isset($row["cells"][0]["width"]) && $row["cells"][0]["width"] == "left-merge")) {
				$stretchWidth -= 1;
			}
			$stretchWidth -= count($row["cells"]);
			$stretchWidth += 1;
			
			$firstCell = true;
			foreach($row["cells"] as $cell) {
				$output .= "<td";
				if(isset($cell["width"]) && $cell["width"] == "left-merge") {
					$width = 1;
				} else {
					if(isset($cell["width"]) && $cell["width"] == "stretch") {
						$width = $stretchWidth;
						if(isset($cell["cellclass"]) && $cell["cellclass"] !== null) {
							$cell["cellclass"] .= " stretch";
						} else {
							$cell["cellclass"] = "stretch";
						}
					} else {
						$width = 1;
					}
					if($firstCell && $leftMergeUsed) {
						$width++;
					}
				}
				if($width != 1) {
					$output .= " colspan=\"$width\"";
				}
				if(isset($cell["cellclass"]) && $cell["cellclass"] !== null) {
					$output .= " class=\"{$cell["cellclass"]}\"";
				}
				$output .= ">";
				$firstCell = false;
				
				$output .= $cell["content"];
				
				$output .= "</td>\n";
			}
			
			$output .= "</tr>\n";
		}
	}
	
	if($submitCaption !== null) {
		$stretchWidth = $maxLeftFields + $maxRightFields + 1;
		$nameHtml = ($submitName === null ? "" : "name=\"$submitName\" ");
		$output .= "<tr class=\"submit\"><td colspan=\"$stretchWidth\"><input type=\"submit\" value=\"$submitCaption\" $nameHtml/></td></tr>\n";
	}
	
	$output .= "</table>\n";
	
	return $output;
}

function operationForm($postUrl, $error, $title, $submitCaption, $fields, $values, $messages = null)
{
	if($values === null) {
		$values = array();
	}
	
	if($messages === null) {
		$messages = array();
	}
	
	$readOnly = ($error === null);
	$stub = ($error == "STUB");
	
	$mainTable = array();
	$extraTables = null;
	$hiddenFields = ($error === null ? "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n" : "");
	foreach($fields as $value) {
		if($stub) {
			if(isset($value["nostub"]) && $value["nostub"] !== null) {
				if($value["nostub"]) {
					continue;
				}
			} else if($value["type"] == "typechooser") {
				continue;
			}
		}
		
		if($value["type"] == "hidden") {
			$valueHtml = htmlentities($values[$value["name"]]);
			$hiddenFields .= "<input type=\"hidden\" name=\"{$value["name"]}\" value=\"$valueHtml\" />\n";
			continue;
		} else if($value["type"] == "typechooser") {
			$extraTables = $value["options"];
		} else {
			$mainTable[] = $value;
		}
	}
	
	if($extraTables !== null) {
		$selectedTable = null;
		foreach($extraTables as $table) {
			if(isset($values[$table["name"]])) {
				$selectedTable = $table["name"];
				break;
			}
		}
	}
	
	$output = "<div class=\"operation\">\n";
	$output .= "<h2>$title</h2>\n";
	
	if($error === null) {
		$output .= "<p class=\"confirm\">Confirm your input</p>\n";
		if(isset($messages["confirmdelete"])) {
			$output .= "<p class=\"confirmdelete\">{$messages["confirmdelete"]}</p>\n";
		}
		if(isset($messages["confirmbilling"])) {
			$output .= "<p class=\"confirmbilling\">{$messages["confirmbilling"]}</p>\n";
		}
	} else if($error != "" && $error != "STUB") {
		$output .= "<p class=\"error\">" . $error . "</p>\n";
	}
	if(isset($messages["custom"])) {
		$output .= $messages["custom"];
	}
	
	$output .= "<form action=\"$postUrl\" method=\"post\">\n";
	$output .= $hiddenFields;
	
	$output .= renderTable($mainTable, $values, $readOnly, $extraTables === null ? $submitCaption : null);
	
	if($extraTables !== null) {
		foreach($extraTables as $table) {
			if($table["name"] !== $selectedTable) {
				continue;
			}
			
			$output .= "<div class=\"operation selected\">\n";
			$output .= "<h3>Currently selected: {$table["title"]}</h3>\n";
			if(isset($table["summary"]) && $table["summary"] !== null) {
				$output .= "<p>{$table["summary"]}</p>\n";
			}
			$output .= renderTable($table["subform"], $values, $readOnly, $table["submitcaption"], $table["name"]);
			$output .= "</div>\n";
		}
		
		foreach($extraTables as $table) {
			if($table["name"] === $selectedTable || $readOnly) {
				continue;
			}
			
			$output .= "<div class=\"operation\">\n";
			$output .= "<h3>{$table["title"]}</h3>\n";
			if(isset($table["summary"]) && $table["summary"] !== null) {
				$output .= "<p>{$table["summary"]}</p>\n";
			}
			$output .= renderTable($table["subform"], $values, $readOnly, $table["submitcaption"], $table["name"]);
			$output .= "</div>\n";
		}
	}
	
	$output .= "</form>\n";
	$output .= "</div>\n";
	
	return $output;
}

?>