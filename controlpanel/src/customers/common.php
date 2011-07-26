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
	$output .= "<tr><th>Nickname</th><th>Name</th><th>Email</th><th>Filesystem</th><th>Mailsystem</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->stdList("adminCustomer", array(), array("customerID", "fileSystemID", "mailSystemID", "name", "realname", "email"), array("name"=>"ASC")) as $customer) {
		$nicknameHtml = htmlentities($customer["name"]);
		$nameHtml = htmlentities($customer["realname"]);
		$emailHtml = htmlentities($customer["email"]);
		$fileSystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$customer["fileSystemID"]), "name"));
		$mailSystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$customer["mailSystemID"]), "name"));
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}customers/customer.php?id={$customer["customerID"]}\">$nicknameHtml</a></td><td>$nameHtml</td><td><a href=\"mailto:{$customer["email"]}\">$emailHtml</a></td><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$customer["fileSystemID"]}\">$fileSystemNameHtml</a></td><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id={$customer["mailSystemID"]}\">$mailSystemNameHtml</a></td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function addCustomerForm($error = "", $nickname = "", $name = "", $email = "", $group = "", $mailQuota = "", $fileSystemID = "", $mailSystemID = "")
{
	$nicknameValue = inputValue($nickname);
	$nameValue = inputValue($name);
	$emailValue = inputValue($email);
	$groupValue = inputValue($group);
	$mailQuotaValue = inputValue($mailQuota);
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
		$fileSystemOptions = "<select name=\"customerFileSystem\">";
		$fileSystems = $GLOBALS["database"]->stdList("infrastructureFileSystem", array(), array("fileSystemID", "name"));
		foreach($fileSystems as $fileSystemOption) {
			$selected = $fileSystemID == $fileSystemOption["fileSystemID"] ? "selected=\"selected\"" : "";
			$fileSystemOptions .= "<option value=\"{$fileSystemOption["fileSystemID"]}\" $selected>{$fileSystemOption["name"]}</option>";
		}
		$fileSystemOptions .= "</select>";
		
		$mailSystemOptions = "<select name=\"customerMailSystem\">";
		$mailSystems = $GLOBALS["database"]->stdList("infrastructureMailSystem", array(), array("mailSystemID", "name"));
		foreach($mailSystems as $mailSystemOption) {
			$selected = $fileSystemID == $mailSystemOption["mailSystemID"] ? "selected=\"selected\"" : "";
			$mailSystemOptions .= "<option value=\"{$mailSystemOption["mailSystemID"]}\" $selected>{$mailSystemOption["name"]}</option>";
		}
		$mailSystemOptions .= "</select>";
	} else {
		$fileSystemName = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "name");
		$fileSystemOptions = "<input name=\"customerFileSystem\" type=\"hidden\" value=\"$fileSystemID\" /><input type=\"text\" name=\"customerFileSystemName\" value=\"$fileSystemName\" readonly=\"readonly\">";
		
		$mailSystemName = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "name");
		$mailSystemOptions = "<input name=\"customerMailSystem\" type=\"hidden\" value=\"$mailSystemID\" /><input type=\"text\" name=\"customerMailSystemName\" value=\"$mailSystemName\" readonly=\"readonly\">";
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
<td colspan="2"><input type="text" name="customerNickname" $nicknameValue $readonly /></td>
</tr>
<tr>
<th>Name:</th>
<td colspan="2"><input type="text" name="customerName" $nameValue $readonly /></td>
</tr>
<tr>
<th>Email:</th>
<td colspan="2"><input type="text" name="customerEmail" $emailValue $readonly /></td>
</tr>
<tr>
<th>Group:</th>
<td colspan="2"><input type="text" name="customerGroup" $groupValue $readonly /></td>
</tr>
<tr>
<th>Mail quota:</th>
<td><input type="text" name="mailQuota" $mailQuotaValue $readonly /></td><td>MB</td>
</tr>
<tr>
<th>Filesystem:</th>
<td colspan="2">$fileSystemOptions</td>
</tr>
<tr>
<th>Mailsystem:</th>
<td colspan="2">$mailSystemOptions</td>
</tr>
<tr>
<td colspan="3" class="submitCell"><input type="submit" value="Add" /></td>
</tr>
</table>
</form>
</div>

HTML;
}

function editCustomerForm($customerID, $error, $name, $email, $mailQuota)
{
	$customer = $GLOBALS["database"]->stdGetTry("adminCustomer", array("customerID"=>$customerID), array("name", "groupname", "fileSystemID", "mailSystemID"), false);
	$nicknameHtml = htmlentities($customer["name"]);
	$nameValue = inputValue($name);
	$emailValue = inputValue($email);
	$mailQuotaValue = inputValue($mailQuota);
	$groupHtml = htmlentities($customer["groupname"]);
	$fileSystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$customer["fileSystemID"]), "name"));
	$mailSystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$customer["mailSystemID"]), "name"));
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
<td colspan="2">$nicknameHtml</td>
</tr>
<tr>
<th>Name:</th>
<td colspan="2"><input type="text" name="customerName" $nameValue $readonly /></td>
</tr>
<tr>
<th>Email:</th>
<td colspan="2"><input type="text" name="customerEmail" $emailValue $readonly /></td>
</tr>
<tr>
<th>Group:</th>
<td colspan="2">$groupHtml</td>
</tr>
<tr>
<th>Mail quota:</th>
<td><input type="text" name="mailQuota" $mailQuotaValue $readonly /></td><td>MB</td>
</tr>
<tr>
<th>Filesystem:</th>
<td colspan="2"><a href="{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$customer["fileSystemID"]}">$fileSystemNameHtml</select></td>
</tr>
<tr>
<th>Mailsystem:</th>
<td colspan="2"><a href="{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id={$customer["mailSystemID"]}">$mailSystemNameHtml</select></td>
</tr>
<tr>
<td colspan="3" class="submit"><input type="submit" value="Save" /></td>
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