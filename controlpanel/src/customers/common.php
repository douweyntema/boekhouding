<?php

require_once(dirname(__FILE__) . "/../common.php");

function doCustomers()
{
	useComponent("customers");
	useCustomer(0);
	$GLOBALS["menuComponent"] = "customers";
}

function customerNotFound($customerID)
{
	header("HTTP/1.1 404 Not Found");
	
	die("Customer #$customerID not found");
}

function customerList()
{
	$output  = "<div class=\"sortable list\">\n";
	$output .= "<table>\n";
	$output .= "<thead>\n";
	$output .= "<tr><th>Nickname</th><th>Name</th><th>Email</th><th>Filesystem</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->stdList("adminCustomer", array(), array("customerID", "filesystemID", "name", "realname", "email"), array("name"=>"ASC")) as $customer) {
		$nicknameHtml = htmlentities($customer["name"]);
		$nameHtml = htmlentities($customer["realname"]);
		$emailHtml = htmlentities($customer["email"]);
		$filesystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureFilesystem", array("filesystemID"=>$customer["filesystemID"]), "name"));
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}customers/customer.php?id={$customer["customerID"]}\">$nicknameHtml</a></td><td>$nameHtml</td><td><a href=\"mailto:{$customer["email"]}\">$emailHtml</a></td><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$customer["filesystemID"]}\">$filesystemNameHtml</a></td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function addCustomerForm($error = "", $nickname = "", $name = "", $email = "", $group = "", $filesystemID = "")
{
	$nicknameValue = inputValue($nickname);
	$nameValue = inputValue($name);
	$emailValue = inputValue($email);
	$groupValue = inputValue($group);
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = "readonly=\"readonly\"";
	} else if($error === "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
	}
	
	if($readonly == "") {
		$filesystemOptions = "<select name=\"customerFilesystem\">";
		$filesystems = $GLOBALS["database"]->stdList("infrastructureFilesystem", array(), array("filesystemID", "name"));
		foreach($filesystems as $filesystemOption) {
			$selected = $filesystemID == $filesystemOption["filesystemID"] ? "selected=\"selected\"" : "";
			$filesystemOptions .= "<option value=\"{$filesystemOption["filesystemID"]}\" $selected>{$filesystemOption["name"]}</option>";
		}
		$filesystemOptions .= "</select>";
	} else {
		$filesystemName = $GLOBALS["database"]->stdGet("infrastructureFilesystem", array("filesystemID"=>$filesystemID), "name");
		$filesystemOptions = "<input name=\"customerFilesystem\" type=\"hidden\" value=\"$filesystemID\" /><input type=\"text\" name=\"customerFilesystemName\" value=\"$filesystemName\" readonly=\"readonly\">";
	}
	
	return <<<HTML
<div class="operation">
<h2>Add customer</h2>
$messageHtml
<form action="addcustomer.php" method="post">
$confirmHtml
<table>
<tr>
<th>Nickname:</th>
<td><input type="text" name="customerNickname" $nicknameValue $readonly /></td>
</tr>
<tr>
<th>Name:</th>
<td><input type="text" name="customerName" $nameValue $readonly /></td>
</tr>
<tr>
<th>Email:</th>
<td><input type="text" name="customerEmail" $emailValue $readonly /></td>
</tr>
<tr>
<th>Group:</th>
<td><input type="text" name="customerGroup" $groupValue $readonly /></td>
</tr>
<tr>
<th>Filesystem:</th>
<td>$filesystemOptions</td>
</tr>
<tr>
<td colspan="2" class="submitCell"><input type="submit" value="Add" /></td>
</tr>
</table>
</form>
</div>

HTML;
}

function editCustomerForm($customerID, $error, $name, $email)
{
	$customer = $GLOBALS["database"]->stdGetTry("adminCustomer", array("customerID"=>$customerID), array("name", "groupname", "filesystemID"), false);
	$nicknameHtml = htmlentities($customer["name"]);
	$nameValue = inputValue($name);
	$emailValue = inputValue($email);
	$groupHtml = htmlentities($customer["groupname"]);
	$filesystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureFilesystem", array("filesystemID"=>$customer["filesystemID"]), "name"));
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />";
		$readonly = "readonly=\"readonly\"";
	} else if($error === "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>";
		$confirmHtml = "";
		$readonly = "";
	}
	
	return <<<HTML
<div class="operation">
<h2>Edit customer details</h2>
$messageHtml
<form action="editcustomer.php?id=$customerID" method="post">
$confirmHtml
<table>
<tr>
<th>Nickname:</th>
<td>$nicknameHtml</td>
</tr>
<tr>
<th>Name:</th>
<td><input type="text" name="customerName" $nameValue $readonly /></td>
</tr>
<tr>
<th>Email:</th>
<td><input type="text" name="customerEmail" $emailValue $readonly /></td>
</tr>
<tr>
<th>Group:</th>
<td>$groupHtml</td>
</tr>
<tr>
<th>Filesystem:</th>
<td><a href="{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$customer["filesystemID"]}">$filesystemNameHtml</select></td>
</tr>
<tr>
<td colspan="2" class="submit"><input type="submit" value="Save" /></td>
</tr>
</table>
</form>
</div>

HTML;
}

function editCustomerRightsForm($customerID, $error = "", $rights = null)
{
	if($rights === null) {
		$components = components();
		$rights = array();
		foreach($components as $component) {
			$rights[$component["componentID"]] = false;
		}
		foreach($GLOBALS["database"]->stdList("adminCustomerRight", array("customerID"=>$customerID), "componentID") as $componentID) {
			$rights[$componentID] = true;
		}
	}
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = true;
	} else if($error === "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = false;
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>";
		$confirmHtml = "";
		$readonly = false;
	}
	
	$html  = "<div class=\"operation\">\n";
	$html .= "<h2>Edit customer rights</h2>\n";
	$html .= $messageHtml;
	$html .= "<form action=\"editcustomerrights.php?id=$customerID\" method=\"post\">\n";
	$html .= "<input type=\"hidden\" name=\"posted\" value=\"true\">";
	$html .= $confirmHtml;
	$html .= "<table class=\"customer rights\">\n";
	$html .= "<tr>\n";
	$html .= "<th colspan=\"2\">Right</th>\n";
	$html .= "<th>Description</th>\n";
	$html .= "</tr>\n";
	
	foreach(components() as $component) {
		if($component["rootOnly"] != 0) {
			continue;
		}
		
		$titleHtml = htmlentities($component["title"]);
		$descriptionHtml = htmlentities($component["description"]);
		$checkedHtml = ($rights[$component["componentID"]] ? "checked=\"checked\"" : "");
		
		$html .= "<tr class=\"right\">\n";
		if($readonly) {
			if($rights[$component["componentID"]]) {
				$html .= "<td><input type=\"checkbox\" value=\"1\" disabled=\"disabled\" checked=\"checked\" /><input type=\"hidden\" name=\"right{$component["componentID"]}\" value=\"1\" /></td>\n";
			} else {
				$html .= "<td><input type=\"checkbox\" disabled=\"disabled\" /></td>\n";
			}
			$html .= "<td>$titleHtml</td>\n";
		} else {
			$html .= "<td><input type=\"checkbox\" name=\"right{$component["componentID"]}\" id=\"right{$component["componentID"]}\" value=\"1\" $checkedHtml /></td>\n";
			$html .= "<td><label for=\"right{$component["componentID"]}\">$titleHtml</label></td>\n";
		}
		$html .= "<td>$descriptionHtml</td>\n";
		$html .= "</tr>\n";
	}
	
	$html .= "<tr>\n";
	$html .= "<td colspan=\"3\" class=\"submit\"><input type=\"submit\" value=\"Save\" /></td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";
	$html .= "</form>\n";
	$html .= "</div>\n";
	return $html;
}

?>