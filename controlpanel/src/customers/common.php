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
	$output .= "<tr><th>Nickname</th><th>Name</th><th>Email</th><th>Filesystem</th><th>Mailsystem</th><th>Balance</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->stdList("adminCustomer", array(), array("customerID", "fileSystemID", "mailSystemID", "name", "initials", "lastName", "email"), array("name"=>"ASC")) as $customer) {
		$nicknameHtml = htmlentities($customer["name"]);
		$nameHtml = htmlentities($customer["initials"] . " " . $customer["lastName"]);
		$emailHtml = htmlentities($customer["email"]);
		$fileSystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$customer["fileSystemID"]), "name"));
		$mailSystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$customer["mailSystemID"]), "name"));
		$balanceHtml = formatPrice(billingBalance($customer["customerID"]));
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}customers/customer.php?id={$customer["customerID"]}\">$nicknameHtml</a><a href=\"{$GLOBALS["rootHtml"]}index.php?customerID={$customer["customerID"]}\" class=\"rightalign\"><img src=\"{$GLOBALS["rootHtml"]}img/external.png\" alt=\"Impersonate\" /></a></td><td>$nameHtml</td><td><a href=\"mailto:{$customer["email"]}\">$emailHtml</a></td><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$customer["fileSystemID"]}\">$fileSystemNameHtml</a></td><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id={$customer["mailSystemID"]}\">$mailSystemNameHtml</a></td><td><a href=\"{$GLOBALS["rootHtml"]}billing/customer.php?id={$customer["customerID"]}\">$balanceHtml</a></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function customerBalance($customerID)
{
	$balance = formatPrice(billingBalance($customerID));
	return <<<HTML
<div class="operation">
<h2>Balance</h2>
<table>
<tr><th>Balance</th><td><a href="{$GLOBALS["rootHtml"]}billing/customer.php?id=$customerID">$balance</a></td></tr>
</table>
</div>

HTML;
}

function addCustomerForm($error = "", $nickname = "", $initials = "", $lastName = "", $companyName = "", $address = "", $postalCode = "", $city = "", $countryCode = "nl", $email = "", $phoneNumber = "", $group = "", $diskQuota = "", $mailQuota = "", $fileSystemID = "", $mailSystemID = "", $nameSystemID = "")
{
	// TODO: billing interval
	$nicknameValue = inputValue($nickname);
	$initialsValue = inputValue($initials);
	$lastNameValue = inputValue($lastName);
	$companyNameValue = inputValue($companyName);
	$addressValue = inputValue($address);
	$postalCodeValue = inputValue($postalCode);
	$cityValue = inputValue($city);
	$emailValue = inputValue($email);
	$phoneNumberValue = inputValue($phoneNumber);
	$groupValue = inputValue($group);
	$diskQuotaValue = inputValue($diskQuota);
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
			$selected = $mailSystemID == $mailSystemOption["mailSystemID"] ? "selected=\"selected\"" : "";
			$mailSystemOptions .= "<option value=\"{$mailSystemOption["mailSystemID"]}\" $selected>{$mailSystemOption["name"]}</option>";
		}
		$mailSystemOptions .= "</select>";
		
		$nameSystemOptions = "<select name=\"customerNameSystem\">";
		$nameSystems = $GLOBALS["database"]->stdList("infrastructureNameSystem", array(), array("nameSystemID", "name"));
		foreach($nameSystems as $nameSystemOption) {
			$selected = $nameSystemID == $nameSystemOption["nameSystemID"] ? "selected=\"selected\"" : "";
			$nameSystemOptions .= "<option value=\"{$nameSystemOption["nameSystemID"]}\" $selected>{$nameSystemOption["name"]}</option>";
		}
		$nameSystemOptions .= "</select>";
		
		$countryOptions = countryDropdown("customerCountryCode", $countryCode);
	} else {
		$fileSystemName = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "name");
		$fileSystemOptions = "<input name=\"customerFileSystem\" type=\"hidden\" value=\"$fileSystemID\" /><input type=\"text\" name=\"customerFileSystemName\" value=\"$fileSystemName\" readonly=\"readonly\">";
		
		$mailSystemName = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "name");
		$mailSystemOptions = "<input name=\"customerMailSystem\" type=\"hidden\" value=\"$mailSystemID\" /><input type=\"text\" name=\"customerMailSystemName\" value=\"$mailSystemName\" readonly=\"readonly\">";
		
		$nameSystemName = $GLOBALS["database"]->stdGet("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), "name");
		$nameSystemOptions = "<input name=\"customerNameSystem\" type=\"hidden\" value=\"$nameSystemID\" /><input type=\"text\" name=\"customerNameSystemName\" value=\"$nameSystemName\" readonly=\"readonly\">";
		
		$countryName = countryName($countryCode);
		$countryOptions = "<input name=\"customerCountryCode\" type=\"hidden\" value=\"$countryCode\" /><input type=\"text\" name=\"customerCountryName\" value=\"$countryName\" readonly=\"readonly\">";
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
<th>Initials:</th>
<td colspan="2"><input type="text" name="customerInitials" $initialsValue $readonly /></td>
</tr>
<tr>
<th>Last name:</th>
<td colspan="2"><input type="text" name="customerLastName" $lastNameValue $readonly /></td>
</tr>
<tr>
<th>Company name:</th>
<td colspan="2"><input type="text" name="customerCompanyName" $companyNameValue $readonly /></td>
</tr>
<tr>
<th>Address:</th>
<td colspan="2"><input type="text" name="customerAddress" $addressValue $readonly /></td>
</tr>
<tr>
<th>Postal code:</th>
<td colspan="2"><input type="text" name="customerPostalCode" $postalCodeValue $readonly /></td>
</tr>
<tr>
<th>City:</th>
<td colspan="2"><input type="text" name="customerCity" $cityValue $readonly /></td>
</tr>
<tr>
<th>Country:</th>
<td colspan="2">$countryOptions</td>
</tr>
<tr>
<th>Email:</th>
<td colspan="2"><input type="text" name="customerEmail" $emailValue $readonly /></td>
</tr>
<tr>
<th>Phone number:</th>
<td colspan="2"><input type="text" name="customerPhoneNumber" $phoneNumberValue $readonly /></td>
</tr>
<tr>
<th>Group:</th>
<td colspan="2"><input type="text" name="customerGroup" $groupValue $readonly /></td>
</tr>
<tr>
<th>Disk quota:</th>
<td><input type="text" name="diskQuota" $diskQuotaValue $readonly /></td><td>MiB</td>
</tr>
<tr>
<th>Mail quota:</th>
<td><input type="text" name="mailQuota" $mailQuotaValue $readonly /></td><td>MiB</td>
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
<th>Namesystem:</th>
<td colspan="2">$nameSystemOptions</td>
</tr>
<tr>
<td colspan="3" class="submitCell"><input type="submit" value="Add" /></td>
</tr>
</table>
</form>
</div>

HTML;
}

function editCustomerForm($customerID, $error, $initials, $lastName, $companyName, $address, $postalCode, $city, $countryCode, $email, $phoneNumber, $diskQuota, $mailQuota)
{
	// TODO: billing interval
	$customer = $GLOBALS["database"]->stdGetTry("adminCustomer", array("customerID"=>$customerID), array("name", "groupname", "fileSystemID", "mailSystemID", "nameSystemID"), false);
	
	$nicknameHtml = htmlentities($customer["name"]);
	$groupHtml = htmlentities($customer["groupname"]);
	$fileSystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$customer["fileSystemID"]), "name"));
	$mailSystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$customer["mailSystemID"]), "name"));
	$nameSystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureNameSystem", array("nameSystemID"=>$customer["nameSystemID"]), "name"));
	
	$initialsValue = inputValue($initials);
	$lastNameValue = inputValue($lastName);
	$companyNameValue = inputValue($companyName);
	$addressValue = inputValue($address);
	$postalCodeValue = inputValue($postalCode);
	$cityValue = inputValue($city);
	$emailValue = inputValue($email);
	$phoneNumberValue = inputValue($phoneNumber);
	$diskQuotaValue = inputValue($diskQuota);
	$mailQuotaValue = inputValue($mailQuota);
	
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$messageHtml .= "<p class=\"warning\">This will unrecoverably remove rights from this customer's users!</p>\n";
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
		$countryOptions = countryDropdown("customerCountryCode", $countryCode);
	} else {
		$countryName = countryName($countryCode);
		$countryOptions = "<input name=\"customerCountryCode\" type=\"hidden\" value=\"$countryCode\" /><input type=\"text\" name=\"customerCountryName\" value=\"$countryName\" readonly=\"readonly\">";
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
<th>Initials:</th>
<td colspan="2"><input type="text" name="customerInitials" $initialsValue $readonly /></td>
</tr>
<tr>
<th>Last name:</th>
<td colspan="2"><input type="text" name="customerLastName" $lastNameValue $readonly /></td>
</tr>
<tr>
<th>Company name:</th>
<td colspan="2"><input type="text" name="customerCompanyName" $companyNameValue $readonly /></td>
</tr>
<tr>
<th>Address:</th>
<td colspan="2"><input type="text" name="customerAddress" $addressValue $readonly /></td>
</tr>
<tr>
<th>Postal code:</th>
<td colspan="2"><input type="text" name="customerPostalCode" $postalCodeValue $readonly /></td>
</tr>
<tr>
<th>City:</th>
<td colspan="2"><input type="text" name="customerCity" $cityValue $readonly /></td>
</tr>
<tr>
<th>Country:</th>
<td colspan="2">$countryOptions</td>
</tr>
<tr>
<th>Email:</th>
<td colspan="2"><input type="text" name="customerEmail" $emailValue $readonly /></td>
</tr>
<tr>
<th>Phone number:</th>
<td colspan="2"><input type="text" name="customerPhoneNumber" $phoneNumberValue $readonly /></td>
</tr>
<tr>
<th>Group:</th>
<td colspan="2">$groupHtml</td>
</tr>
<tr>
<th>Disk quota:</th>
<td><input type="text" name="diskQuota" $diskQuotaValue $readonly /></td><td>MiB</td>
</tr>
<tr>
<th>Mail quota:</th>
<td><input type="text" name="mailQuota" $mailQuotaValue $readonly /></td><td>MiB</td>
</tr>
<tr>
<th>Filesystem:</th>
<td colspan="2"><a href="{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$customer["fileSystemID"]}">$fileSystemNameHtml</td>
</tr>
<tr>
<th>Mailsystem:</th>
<td colspan="2"><a href="{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id={$customer["mailSystemID"]}">$mailSystemNameHtml</td>
</tr>
<tr>
<th>Namesystem:</th>
<td colspan="2"><a href="{$GLOBALS["rootHtml"]}infrastructure/namesystem.php?id={$customer["nameSystemID"]}">$nameSystemNameHtml</td>
</tr>
<tr>
<td colspan="3" class="submitCell"><input type="submit" value="Save" /></td>
</tr>
</table>
</form>
</div>

HTML;
}

function editCustomerRightsForm($customerID, $error = "", $rights = null)
{
	if($rights === null) {
		$rights = array();
		foreach(rights() as $right) {
			$rights[$right["name"]] = false;
		}
		foreach($GLOBALS["database"]->stdList("adminCustomerRight", array("customerID"=>$customerID), "right") as $right) {
			$rights[$right] = true;
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
	
	foreach(rights() as $right) {
		$titleHtml = htmlentities($right["title"]);
		$descriptionHtml = htmlentities($right["description"]);
		$checkedHtml = ($rights[$right["name"]] ? "checked=\"checked\"" : "");
		
		$html .= "<tr class=\"right\">\n";
		if($readonly) {
			if($rights[$right["name"]]) {
				$html .= "<td><input type=\"checkbox\" value=\"1\" disabled=\"disabled\" checked=\"checked\" /><input type=\"hidden\" name=\"right-{$right["name"]}\" value=\"1\" /></td>\n";
			} else {
				$html .= "<td><input type=\"checkbox\" disabled=\"disabled\" /></td>\n";
			}
			$html .= "<td>$titleHtml</td>\n";
		} else {
			$html .= "<td><input type=\"checkbox\" name=\"right-{$right["name"]}\" id=\"right-{$right["name"]}\" value=\"1\" $checkedHtml /></td>\n";
			$html .= "<td><label for=\"right-{$right["name"]}\">$titleHtml</label></td>\n";
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

function countryDropdown($name, $selectedCode = null)
{
	$country = countryCodes();
	$output = "<select name=\"$name\">\n";
	foreach($country as $code=>$name) {
		$selected = $code == strtoupper($selectedCode) ? "selected=\"selected\"" : "";
		$output .= "<option value=\"$code\" $selected>$name</option>\n";
	}
	$output .= "</select>\n";
	
	return $output;
}

?>