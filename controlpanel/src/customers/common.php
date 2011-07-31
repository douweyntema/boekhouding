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
	foreach($GLOBALS["database"]->stdList("adminCustomer", array(), array("customerID", "fileSystemID", "mailSystemID", "name", "initials", "lastName", "email"), array("name"=>"ASC")) as $customer) {
		$nicknameHtml = htmlentities($customer["name"]);
		$nameHtml = htmlentities($customer["initials"] . " " . $customer["lastName"]);
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

function addCustomerForm($error = "", $nickname = "", $initials = "", $lastName = "", $companyName = "", $address = "", $postalCode = "", $city = "", $countryCode = "nl", $email = "", $phoneNumber = "", $group = "", $diskQuota = "", $mailQuota = "", $fileSystemID = "", $mailSystemID = "", $nameSystemID = "")
{
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

function countryArray()
{
	$country = array();
	$country["AF"] = "Afghanistan";  
	$country["AX"] = "Åland Islands";  
	$country["AL"] = "Albania";  
	$country["DZ"] = "Algeria";  
	$country["AS"] = "American Samoa";  
	$country["AD"] = "Andorra";  
	$country["AO"] = "Angola";
	$country["AI"] = "Anguilla";
	$country["AQ"] = "Antarctica";
	$country["AG"] = "Antigua and Barbuda";
	$country["AR"] = "Argentina";
	$country["AU"] = "Australia";
	$country["AT"] = "Austria";
	$country["AZ"] = "Azerbaijan";
	$country["BS"] = "Bahamas";
	$country["BH"] = "Bahrain";
	$country["BD"] = "Bangladesh";
	$country["BB"] = "Barbados";
	$country["BY"] = "Belarus";
	$country["BE"] = "Belgium";
	$country["BZ"] = "Belize";
	$country["BJ"] = "Benin";
	$country["BM"] = "Bermuda";
	$country["BT"] = "Bhutan";
	$country["BO"] = "Bolivia";
	$country["BA"] = "Bosnia and Herzegovina";
	$country["BW"] = "Botswana";
	$country["BV"] = "Bouvet Island";
	$country["BR"] = "Brazil";
	$country["IO"] = "British Indian Ocean Territory";
	$country["BN"] = "Brunei Darussalam";
	$country["BG"] = "Bulgaria";
	$country["BF"] = "Burkina Faso";
	$country["BI"] = "Burundi";
	$country["KH"] = "Cambodia";
	$country["CM"] = "Cameroon";
	$country["CA"] = "Canada";
	$country["CV"] = "Cape Verde";
	$country["KY"] = "Cayman Islands";
	$country["CF"] = "Central African Republic";
	$country["TD"] = "Chad";
	$country["CL"] = "Chile";
	$country["CN"] = "China";
	$country["CX"] = "Christmas Island";
	$country["CC"] = "Cocos (Keeling) Islands";
	$country["CO"] = "Colombia";
	$country["KM"] = "Comoros";
	$country["CG"] = "Congo";
	$country["CD"] = "Congo, the Democratic Republic of the";
	$country["CK"] = "Cook Islands";
	$country["CR"] = "Costa Rica";
	$country["CI"] = "Côte D'Ivoire";
	$country["HR"] = "Croatia";
	$country["CU"] = "Cuba";
	$country["CY"] = "Cyprus";
	$country["CZ"] = "Czech Republic";
	$country["DK"] = "Denmark";
	$country["DJ"] = "Djibouti";
	$country["DM"] = "Dominica";
	$country["DO"] = "Dominican Republic";
	$country["EC"] = "Ecuador";
	$country["EG"] = "Egypt";
	$country["SV"] = "El Salvador";
	$country["GQ"] = "Equatorial Guinea";
	$country["ER"] = "Eritrea";
	$country["EE"] = "Estonia";
	$country["ET"] = "Ethiopia";
	$country["FK"] = "Falkland Islands (Malvinas)";
	$country["FO"] = "Faroe Islands";
	$country["FJ"] = "Fiji";
	$country["FI"] = "Finland";
	$country["FR"] = "France";
	$country["GF"] = "French Guiana";
	$country["PF"] = "French Polynesia";
	$country["TF"] = "French Southern Territories";
	$country["GA"] = "Gabon";
	$country["GM"] = "Gambia";
	$country["GE"] = "Georgia";
	$country["DE"] = "Germany";
	$country["GH"] = "Ghana";
	$country["GI"] = "Gibraltar";
	$country["GR"] = "Greece";
	$country["GL"] = "Greenland";
	$country["GD"] = "Grenada";
	$country["GP"] = "Guadeloupe";
	$country["GU"] = "Guam";
	$country["GT"] = "Guatemala";
	$country["GG"] = "Guernsey";
	$country["GN"] = "Guinea";
	$country["GW"] = "Guinea-Bissau";
	$country["GY"] = "Guyana";
	$country["HT"] = "Haiti";
	$country["HM"] = "Heard Island and Mcdonald Islands";
	$country["VA"] = "Holy See (Vatican City State)";
	$country["HN"] = "Honduras";
	$country["HK"] = "Hong Kong";
	$country["HU"] = "Hungary";
	$country["IS"] = "Iceland";
	$country["IN"] = "India";
	$country["ID"] = "Indonesia";
	$country["IR"] = "Iran, Islamic Republic of";
	$country["IQ"] = "Iraq";
	$country["IE"] = "Ireland";
	$country["IM"] = "Isle of Man";
	$country["IL"] = "Israel";
	$country["IT"] = "Italy";
	$country["JM"] = "Jamaica";
	$country["JP"] = "Japan";
	$country["JE"] = "Jersey";
	$country["JO"] = "Jordan";
	$country["KZ"] = "Kazakhstan";
	$country["KE"] = "KENYA";
	$country["KI"] = "Kiribati";
	$country["KP"] = "Korea, Democratic People's Republic of";
	$country["KR"] = "Korea, Republic of";
	$country["KW"] = "Kuwait";
	$country["KG"] = "Kyrgyzstan";
	$country["LA"] = "Lao People's Democratic Republic";
	$country["LV"] = "Latvia";
	$country["LB"] = "Lebanon";
	$country["LS"] = "Lesotho";
	$country["LR"] = "Liberia";
	$country["LY"] = "Libyan Arab Jamahiriya";
	$country["LI"] = "Liechtenstein";
	$country["LT"] = "Lithuania";
	$country["LU"] = "Luxembourg";
	$country["MO"] = "Macao";
	$country["MK"] = "Macedonia, the Former Yugoslav Republic of";
	$country["MG"] = "Madagascar";
	$country["MW"] = "Malawi";
	$country["MY"] = "Malaysia";
	$country["MV"] = "Maldives";
	$country["ML"] = "Mali";
	$country["MT"] = "Malta";
	$country["MH"] = "Marshall Islands";
	$country["MQ"] = "Martinique";
	$country["MR"] = "Mauritania";
	$country["MU"] = "Mauritius";
	$country["YT"] = "Mayotte";
	$country["MX"] = "Mexico";
	$country["FM"] = "Micronesia, Federated States of";
	$country["MD"] = "Moldova, Republic of";
	$country["MC"] = "Monaco";
	$country["MN"] = "Mongolia";
	$country["ME"] = "Montenegro";
	$country["MS"] = "Montserrat";
	$country["MA"] = "Morocco";
	$country["MZ"] = "Mozambique";
	$country["MM"] = "Myanmar";
	$country["NA"] = "Namibia";
	$country["NR"] = "Nauru";
	$country["NP"] = "Nepal";
	$country["NL"] = "Netherlands";
	$country["AN"] = "Netherlands Antilles";
	$country["NC"] = "New Caledonia";
	$country["NZ"] = "New Zealand";
	$country["NI"] = "Nicaragua";
	$country["NE"] = "Niger";
	$country["NG"] = "Nigeria";
	$country["NU"] = "Niue";
	$country["NF"] = "Norfolk Island";
	$country["MP"] = "Northern Mariana Islands";
	$country["NO"] = "Norway";
	$country["OM"] = "Oman";
	$country["PK"] = "Pakistan";
	$country["PW"] = "Palau";
	$country["PS"] = "Palestinian Territory, Occupied";
	$country["PA"] = "Panama";
	$country["PG"] = "Papua New Guinea";
	$country["PY"] = "Paraguay";
	$country["PE"] = "Peru";
	$country["PH"] = "Philippines";
	$country["PN"] = "Pitcairn";
	$country["PL"] = "Poland";
	$country["PT"] = "Portugal";
	$country["PR"] = "Puerto Rico";
	$country["QA"] = "Qatar";
	$country["RE"] = "Réunion";
	$country["RO"] = "Romania";
	$country["RU"] = "Russian Federation";
	$country["RW"] = "Rwanda";
	$country["SH"] = "Saint Helena";
	$country["KN"] = "Saint Kitts and Nevis";
	$country["LC"] = "Saint Lucia";
	$country["PM"] = "Saint Pierre and Miquelon";
	$country["VC"] = "Saint Vincent and the Grenadines";
	$country["WS"] = "Samoa";
	$country["SM"] = "San Marino";
	$country["ST"] = "Sao Tome and Principe";
	$country["SA"] = "Saudi Arabia";
	$country["SN"] = "Senegal";
	$country["RS"] = "Serbia";
	$country["SC"] = "Seychelles";
	$country["SL"] = "Sierra Leone";
	$country["SG"] = "Singapore";
	$country["SK"] = "Slovakia";
	$country["SI"] = "Slovenia";
	$country["SB"] = "Solomon Islands";
	$country["SO"] = "Somalia";
	$country["ZA"] = "South Africa";
	$country["GS"] = "South Georgia and the South Sandwich Islands";
	$country["ES"] = "Spain";
	$country["LK"] = "Sri Lanka";
	$country["SD"] = "Sudan";
	$country["SR"] = "Suriname";
	$country["SJ"] = "Svalbard and Jan Mayen";
	$country["SZ"] = "Swaziland";
	$country["SE"] = "Sweden";
	$country["CH"] = "Switzerland";
	$country["SY"] = "Syrian Arab Republic";
	$country["TW"] = "Taiwan, Province of China";
	$country["TJ"] = "Tajikistan";
	$country["TZ"] = "Tanzania, United Republic of";
	$country["TH"] = "Thailand";
	$country["TL"] = "Timor-Leste";
	$country["TG"] = "Togo";
	$country["TK"] = "Tokelau";
	$country["TO"] = "Tonga";
	$country["TT"] = "Trinidad and Tobago";
	$country["TN"] = "Tunisia";
	$country["TR"] = "Turkey";
	$country["TM"] = "Turkmenistan";
	$country["TC"] = "Turks and Caicos Islands";
	$country["TV"] = "Tuvalu";
	$country["UG"] = "Uganda";
	$country["UA"] = "Ukraine";
	$country["AE"] = "United Arab Emirates";
	$country["GB"] = "United Kingdom";
	$country["US"] = "United States";
	$country["UM"] = "United States Minor Outlying Islands";
	$country["UY"] = "Uruguay";
	$country["UZ"] = "Uzbekistan";
	$country["VU"] = "Vanuatu";
	$country["VA"] = "Vatican City State";
	$country["VE"] = "Venezuela";
	$country["VN"] = "Viet Nam";
	$country["VG"] = "Virgin Islands, British";
	$country["VI"] = "Virgin Islands, U.S.";
	$country["WF"] = "Wallis and Futuna";
	$country["EH"] = "Western Sahara";
	$country["YE"] = "Yemen";
	$country["CD"] = "Zaire";
	$country["ZM"] = "Zambia";
	$country["ZW"] = "Zimbabwe";
	
	return $country;
}

function countryDropdown($name, $selectedCode = null)
{
	$country = countryArray();
	$output = "<select name=\"$name\">\n";
	foreach($country as $code=>$name) {
		$selected = $code == strtoupper($selectedCode) ? "selected=\"selected\"" : "";
		$output .= "<option value=\"$code\" $selected>$name</option>\n";
	}
	$output .= "</select>\n";
	
	return $output;
}

function countryName($code)
{
	$code = strtoupper($code);
	$country = countryArray();
	if(isset($country[$code])) {
		return $country[$code];
	} else {
		return $code;
	}
}
?>