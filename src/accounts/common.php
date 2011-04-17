<?php

require_once(dirname(__FILE__) . "/../common.php");

define("RESERVED_USERNAMES_FILE", dirname(__FILE__) . "/../../reserved-usernames");

function doAccounts($userID)
{
	useComponent("accounts");
	useCustomer($userID === null ? customerID() : $GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$userID), "customerID", false));
	$GLOBALS["menuComponent"] = "accounts";
}

function doAccountsAdmin($userID)
{
	useComponent("accounts");
	useCustomer(0);
	$GLOBALS["menuComponent"] = "accounts";
}

function accountNotFound($accountID)
{
	header("HTTP/1.1 404 Not Found");
	
	die("Account #$accountID not found");
}

function validAccountName($username)
{
	if(strlen($username) < 3 || strlen($username) > 30) {
		return false;
	}
	if(preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $username) != 1) {
		return false;
	}
	return true;
}

function reservedAccountName($username)
{
	foreach(explode("\n", file_get_contents(RESERVED_USERNAMES_FILE)) as $reserved) {
		$reserved = trim($reserved);
		if($reserved == "" || $reserved[0] == "#") {
			continue;
		}
		if($username == $reserved) {
			return true;
		}
	}
	return false;
}

function customerComponents()
{
	$components = components();
	$customerComponents = array();
	foreach($components as $component) {
		if($GLOBALS["database"]->stdGetTry("adminCustomerRight", array("customerID"=>customerID(), "componentID"=>$component["componentID"]), "componentID", false) !== false) {
			$customerComponents[] = $component;
		}
	}
	return $customerComponents;
}

function accountList()
{
	$output  = "<div class=\"list\">\n";
	$output .= "<table>\n";
	$output .= "<thead>\n";
	$output .= "<tr><th>Account name</th><th>Type</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->stdList("adminUser", array("customerID"=>customerID()), array("userID", "username"), array("username"=>"ASC")) as $account) {
		if($GLOBALS["database"]->stdGetTry("adminUserRight", array("userID"=>$account["userID"], "componentID"=>null), "userID", false) === false) {
			$type = "Limited rights";
		} else {
			$type = "Full access";
		}
		$usernameHtml = htmlentities($account["username"]);
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}accounts/account.php?id={$account["userID"]}\">$usernameHtml</a></td><td>$type</td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function addAccountForm($error, $name, $rights, $password)
{
	$nameValue = inputValue($name);
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
	
	if($readonly == "") {
		$passwordHtml = <<<HTML
<tr>
<th>Password:</th>
<td colspan="4"><input type="password" name="accountPassword1" /></td>
</tr>
<tr>
<th>Confirm password:</th>
<td colspan="4"><input type="password" name="accountPassword2" /></td>
</tr>

HTML;
	} else {
		$encryptedPassword = encryptPassword($password);
		$masked = str_repeat("*", strlen($password));
		$passwordHtml = <<<HTML
<tr>
<th>Password:</th>
<td colspan="4"><input type="password" value="$masked" readonly="readonly" /><input type="hidden" name="accountEncryptedPassword" value="$encryptedPassword" /></td>
</tr>

HTML;
	}
	
	$components = customerComponents();
	$rowspan = count($components) + 2;
	$rightsHtml = "";
	
	if($readonly) {
		if($rights === true) {
			$rightsHtml .= "<tr>\n";
			$rightsHtml .= "<th rowspan=\"$rowspan\">Rights:</th>\n";
			$rightsHtml .= "<td><input type=\"radio\" disabled=\"disabled\" checked=\"checked\" /><input type=\"hidden\" name=\"rights\" value=\"full\" /></td>\n";
			$rightsHtml .= "<td colspan=\"3\">Full access</td>\n";
			$rightsHtml .= "</tr>\n";
			$rightsHtml .= "<tr>\n";
			$rightsHtml .= "<td><input type=\"radio\" disabled=\"disabled\" /></td>\n";
			$rightsHtml .= "<td colspan=\"3\">Limited rights:</td>\n";
			$rightsHtml .= "</tr>\n";
		} else {
			$rightsHtml .= "<tr>\n";
			$rightsHtml .= "<th rowspan=\"$rowspan\">Rights:</th>\n";
			$rightsHtml .= "<td><input type=\"radio\" disabled=\"disabled\" /></td>\n";
			$rightsHtml .= "<td colspan=\"3\">Full access</td>\n";
			$rightsHtml .= "</tr>\n";
			$rightsHtml .= "<tr>\n";
			$rightsHtml .= "<td><input type=\"radio\" disabled=\"disabled\" checked=\"checked\" /><input type=\"hidden\" name=\"rights\" value=\"limited\" /></td>\n";
			$rightsHtml .= "<td colspan=\"3\">Limited rights:</td>\n";
			$rightsHtml .= "</tr>\n";
		}
	} else {
		if($rights === true) {
			$fullChecked = "checked=\"checked\"";
			$limitedChecked = "";
		} else {
			$fullChecked = "";
			$limitedChecked = "checked=\"checked\"";
		}
		
		$rightsHtml .= "<tr>\n";
		$rightsHtml .= "<th rowspan=\"$rowspan\">Rights:</th>\n";
		$rightsHtml .= "<td><input type=\"radio\" name=\"rights\" value=\"full\" id=\"fullrights\" $fullChecked /></td>\n";
		$rightsHtml .= "<td colspan=\"3\"><label for=\"fullrights\">Full access</label></td>\n";
		$rightsHtml .= "</tr>\n";
		$rightsHtml .= "<tr>\n";
		$rightsHtml .= "<td><input type=\"radio\" name=\"rights\" value=\"limited\" id=\"limitedrights\" $limitedChecked /></td>\n";
		$rightsHtml .= "<td colspan=\"3\"><label for=\"limitedrights\">Limited rights:</label></td>\n";
		$rightsHtml .= "</tr>\n";
	}
	
	foreach($components as $component) {
		$titleHtml = htmlentities($component["title"]);
		$descriptionHtml = htmlentities($component["description"]);
		$checkedHtml = (is_array($rights) && $rights[$component["componentID"]]) ? "checked=\"checked\"" : "";
		
		$rightsHtml .= "<tr>\n";
		$rightsHtml .= "<td></td>\n";
		if($readonly) {
			if($checkedHtml != "") {
				$rightsHtml .= "<td><input type=\"checkbox\" disabled=\"disabled\" $checkedHtml /><input type=\"hidden\" name=\"right{$component["componentID"]}\" value=\"1\" /></td>\n";
			} else {
				$rightsHtml .= "<td><input type=\"checkbox\" disabled=\"disabled\" /></td>\n";
			}
			$rightsHtml .= "<td>$titleHtml</td>\n";
		} else {
			$rightsHtml .= "<td><input type=\"checkbox\" name=\"right{$component["componentID"]}\" id=\"right{$component["componentID"]}\" class=\"right\" value=\"1\" $checkedHtml /></td>\n";
			$rightsHtml .= "<td><label for=\"right{$component["componentID"]}\">$titleHtml</label></td>\n";
		}
		$rightsHtml .= "<td>$descriptionHtml</td>\n";
		$rightsHtml .= "</tr>\n";
	}
	
	$js = <<<SCRIPT
<script type="text/javascript">
function updateRights() {
	if($("input[name='rights']:checked", "#addAccount").val() == 'full') {
		$("input.right").attr("disabled", "disabled");
	} else {
		$("input.right").attr("disabled", null);
	}
}

$(document).ready(function() {
	$("#fullrights").bind("change", updateRights);
	$("#limitedrights").bind("change", updateRights);
	updateRights();
});

</script>

SCRIPT;
	
	return <<<HTML
<div class="operation">
<h2>Add account</h2>
$messageHtml
<form action="addaccount.php" method="post" id="addAccount">
$confirmHtml
<table>
<tr><th>Username:</th><td colspan="4"><input type="text" name="accountUsername" $nameValue $readonly /></td></tr>
$passwordHtml
$rightsHtml
<tr><td colspan="5"><input type="submit" value="Add" /></td></tr>
</table>
</form>
</div>
$js

HTML;
}

function changeAccountPasswordForm($userID, $error, $password)
{
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
	
	if($readonly == "") {
		$passwordHtml = <<<HTML
<tr>
<th>Password:</th>
<td><input type="password" name="accountPassword1" /></td>
</tr>
<tr>
<th>Confirm password:</th>
<td><input type="password" name="accountPassword2" /></td>
</tr>

HTML;
	} else {
		$encryptedPassword = encryptPassword($password);
		$masked = str_repeat("*", strlen($password));
		$passwordHtml = <<<HTML
<tr>
<th>Password:</th>
<td><input type="password" value="$masked" readonly="readonly" /><input type="hidden" name="accountEncryptedPassword" value="$encryptedPassword" /></td>
</tr>

HTML;
	}
	
	return <<<HTML
<div class="operation">
<h2>Change password</h2>
$messageHtml
<form action="editpassword.php?id=$userID" method="post">
$confirmHtml
<table>
$passwordHtml
<tr>
<td colspan="2" class="submit"><input type="submit" value="Change Password" /></td>
</tr>
</table>
</form>
</div>

HTML;
}

function changeAccountRightsForm($userID, $error, $rights)
{
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
	
	$components = customerComponents();
	$rowspan = count($components) + 2;
	$rightsHtml = "";
	
	if($readonly) {
		if($rights === true) {
			$rightsHtml .= "<tr>\n";
			$rightsHtml .= "<th rowspan=\"$rowspan\">Rights:</th>\n";
			$rightsHtml .= "<td><input type=\"radio\" disabled=\"disabled\" checked=\"checked\" /><input type=\"hidden\" name=\"rights\" value=\"full\" /></td>\n";
			$rightsHtml .= "<td colspan=\"3\">Full access</td>\n";
			$rightsHtml .= "</tr>\n";
			$rightsHtml .= "<tr>\n";
			$rightsHtml .= "<td><input type=\"radio\" disabled=\"disabled\" /></td>\n";
			$rightsHtml .= "<td colspan=\"3\">Limited rights:</td>\n";
			$rightsHtml .= "</tr>\n";
		} else {
			$rightsHtml .= "<tr>\n";
			$rightsHtml .= "<th rowspan=\"$rowspan\">Rights:</th>\n";
			$rightsHtml .= "<td><input type=\"radio\" disabled=\"disabled\" /></td>\n";
			$rightsHtml .= "<td colspan=\"3\">Full access</td>\n";
			$rightsHtml .= "</tr>\n";
			$rightsHtml .= "<tr>\n";
			$rightsHtml .= "<td><input type=\"radio\" disabled=\"disabled\" checked=\"checked\" /><input type=\"hidden\" name=\"rights\" value=\"limited\" /></td>\n";
			$rightsHtml .= "<td colspan=\"3\">Limited rights:</td>\n";
			$rightsHtml .= "</tr>\n";
		}
	} else {
		if($rights === true) {
			$fullChecked = "checked=\"checked\"";
			$limitedChecked = "";
		} else {
			$fullChecked = "";
			$limitedChecked = "checked=\"checked\"";
		}
		
		$rightsHtml .= "<tr>\n";
		$rightsHtml .= "<th rowspan=\"$rowspan\">Rights:</th>\n";
		$rightsHtml .= "<td><input type=\"radio\" name=\"rights\" value=\"full\" id=\"fullrights\" $fullChecked /></td>\n";
		$rightsHtml .= "<td colspan=\"3\"><label for=\"fullrights\">Full access</label></td>\n";
		$rightsHtml .= "</tr>\n";
		$rightsHtml .= "<tr>\n";
		$rightsHtml .= "<td><input type=\"radio\" name=\"rights\" value=\"limited\" id=\"limitedrights\" $limitedChecked /></td>\n";
		$rightsHtml .= "<td colspan=\"3\"><label for=\"limitedrights\">Limited rights:</label></td>\n";
		$rightsHtml .= "</tr>\n";
	}
	
	foreach($components as $component) {
		$titleHtml = htmlentities($component["title"]);
		$descriptionHtml = htmlentities($component["description"]);
		$checkedHtml = (is_array($rights) && $rights[$component["componentID"]]) ? "checked=\"checked\"" : "";
		
		$rightsHtml .= "<tr>\n";
		$rightsHtml .= "<td></td>\n";
		if($readonly) {
			if($checkedHtml != "") {
				$rightsHtml .= "<td><input type=\"checkbox\" disabled=\"disabled\" $checkedHtml /><input type=\"hidden\" name=\"right{$component["componentID"]}\" value=\"1\" /></td>\n";
			} else {
				$rightsHtml .= "<td><input type=\"checkbox\" disabled=\"disabled\" /></td>\n";
			}
			$rightsHtml .= "<td>$titleHtml</td>\n";
		} else {
			$rightsHtml .= "<td><input type=\"checkbox\" name=\"right{$component["componentID"]}\" id=\"right{$component["componentID"]}\" value=\"1\" class=\"right\" $checkedHtml /></td>\n";
			$rightsHtml .= "<td><label for=\"right{$component["componentID"]}\">$titleHtml</label></td>\n";
		}
		$rightsHtml .= "<td>$descriptionHtml</td>\n";
		$rightsHtml .= "</tr>\n";
	}
	
	$js = <<<SCRIPT
<script type="text/javascript">
function updateRights() {
	if($("input[name='rights']:checked", "#changeRights").val() == 'full') {
		$("input.right").attr("disabled", "disabled");
	} else {
		$("input.right").attr("disabled", null);
	}
}

$(document).ready(function() {
	$("#fullrights").bind("change", updateRights);
	$("#limitedrights").bind("change", updateRights);
	updateRights();
});

</script>

SCRIPT;
	
	return <<<HTML
<div class="operation">
<h2>Change account access rights</h2>
$messageHtml
<form action="editrights.php?id=$userID" method="post" id="changeRights">
$confirmHtml
<table>
$rightsHtml
<tr><td colspan="5"><input type="submit" value="Save" /></td></tr>
</table>
</form>
</div>
$js

HTML;

}

function removeAccountForm($userID, $error)
{
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n<p class=\"confirmdelete\">Are you sure you want to remove this account?</p>\n";
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
<h2>Remove account</h2>
$messageHtml
<form action="removeaccount.php?id=$userID" method="post">
$confirmHtml
<input type="submit" value="Remove Account" />
</form>
</div>

HTML;
}

function adminAccountList()
{
	$output  = "<div class=\"list\">\n";
	$output .= "<table>\n";
	$output .= "<thead>\n";
	$output .= "<tr><th>Account name</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->stdList("adminUser", array("customerID"=>null), array("userID", "username"), array("username"=>"ASC")) as $account) {
		$usernameHtml = htmlentities($account["username"]);
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}accounts/adminaccount.php?id={$account["userID"]}\">$usernameHtml</a></td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function addAdminAccountForm($error, $name, $password)
{
	$nameValue = inputValue($name);
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
	
	if($readonly == "") {
		$passwordHtml = <<<HTML
<tr>
<th>Password:</th>
<td><input type="password" name="accountPassword1" /></td>
</tr>
<tr>
<th>Confirm password:</th>
<td><input type="password" name="accountPassword2" /></td>
</tr>

HTML;
	} else {
		$encryptedPassword = encryptPassword($password);
		$masked = str_repeat("*", strlen($password));
		$passwordHtml = <<<HTML
<tr>
<th>Password:</th>
<td><input type="password" value="$masked" readonly="readonly" /><input type="hidden" name="accountEncryptedPassword" value="$encryptedPassword" /></td>
</tr>

HTML;
	}
	
	return <<<HTML
<div class="operation">
<h2>Add admin account</h2>
$messageHtml
<form action="addadminaccount.php" method="post">
$confirmHtml
<table>
<tr>
<th>Username:</th>
<td><input type="text" name="accountUsername" $nameValue $readonly /></td>
</tr>
$passwordHtml
<tr>
<td colspan="2" class="submit"><input type="submit" value="Add" /></td>
</tr>
</table>
</form>
</div>

HTML;
}

function changeAdminAccountPasswordForm($userID, $error, $password)
{
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
	
	if($readonly == "") {
		$passwordHtml = <<<HTML
<tr>
<th>Password:</th>
<td><input type="password" name="accountPassword1" /></td>
</tr>
<tr>
<th>Confirm password:</th>
<td><input type="password" name="accountPassword2" /></td>
</tr>

HTML;
	} else {
		$encryptedPassword = encryptPassword($password);
		$masked = str_repeat("*", strlen($password));
		$passwordHtml = <<<HTML
<tr>
<th>Password:</th>
<td><input type="password" value="$masked" readonly="readonly" /><input type="hidden" name="accountEncryptedPassword" value="$encryptedPassword" /></td>
</tr>

HTML;
	}
	
	return <<<HTML
<div class="operation">
<h2>Change password</h2>
$messageHtml
<form action="editadminpassword.php?id=$userID" method="post">
$confirmHtml
<table>
$passwordHtml
<tr>
<td colspan="2" class="submit"><input type="submit" value="Change Password" /></td>
</tr>
</table>
</form>
</div>

HTML;
}

function removeAdminAccountForm($userID, $error)
{
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n<p class=\"confirmdelete\">Are you sure you want to remove this account?</p>\n";
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
<h2>Remove account</h2>
$messageHtml
<form action="removeadminaccount.php?id=$userID" method="post">
$confirmHtml
<input type="submit" value="Remove Account" />
</form>
</div>

HTML;
}

?>