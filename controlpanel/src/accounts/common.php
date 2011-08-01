<?php

require_once(dirname(__FILE__) . "/../common.php");

define("RESERVED_USERNAMES_FILE", dirname(__FILE__) . "/../../reserved-usernames");

function doAccounts()
{
	useComponent("accounts");
	$GLOBALS["menuComponent"] = "accounts";
}

function doAccountsUser($userID)
{
	doAccounts();
	useCustomer($userID === null ? customerID() : $GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$userID), "customerID", false));
}

function doAccountsAdmin()
{
	doAccounts();
	useCustomer(0);
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

function accountList()
{
	$output  = "<div class=\"list sortable\">\n";
	$output .= "<table>\n";
	$output .= "<thead>\n";
	$output .= "<tr><th>Account name</th><th>Type</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->stdList("adminUser", array("customerID"=>customerID()), array("userID", "username"), array("username"=>"ASC")) as $account) {
		if($GLOBALS["database"]->stdExists("adminUserRight", array("userID"=>$account["userID"], "customerRightID"=>null))) {
			$type = "Full access";
		} else {
			$type = "Limited rights";
		}
		$usernameHtml = htmlentities($account["username"]);
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}accounts/account.php?id={$account["userID"]}\">$usernameHtml</a></td><td>$type</td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function addAccountForm($error = "", $name = null, $rights = null, $password = null)
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
	
	$rowspan = count(rights()) + 2;
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
	
	foreach(rights() as $right) {
		if(!$GLOBALS["database"]->stdExists("adminCustomerRight", array("customerID"=>customerID(), "right"=>$right["name"]))) {
			continue;
		}
		$titleHtml = htmlentities($right["title"]);
		$descriptionHtml = htmlentities($right["description"]);
		$checkedHtml = (is_array($rights) && $rights[$right["name"]]) ? "checked=\"checked\"" : "";
		
		$rightsHtml .= "<tr>\n";
		$rightsHtml .= "<td></td>\n";
		if($readonly) {
			if($checkedHtml != "") {
				$rightsHtml .= "<td><input type=\"checkbox\" disabled=\"disabled\" $checkedHtml /><input type=\"hidden\" name=\"right-{$right["name"]}\" value=\"1\" /></td>\n";
			} else {
				$rightsHtml .= "<td><input type=\"checkbox\" disabled=\"disabled\" /></td>\n";
			}
			$rightsHtml .= "<td>$titleHtml</td>\n";
		} else {
			$rightsHtml .= "<td><input type=\"checkbox\" name=\"right-{$right["name"]}\" id=\"right-{$right["name"]}\" class=\"right\" value=\"1\" $checkedHtml /></td>\n";
			$rightsHtml .= "<td><label for=\"right-{$right["name"]}\">$titleHtml</label></td>\n";
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
<tr class="submit"><td colspan="5"><input type="submit" value="Add" /></td></tr>
</table>
</form>
</div>
$js

HTML;
}

function changeAccountRightsForm($userID, $error = "", $rights = null)
{
	$customerID = $GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "customerID");
	if($rights === null) {
		if($GLOBALS["database"]->stdExists("adminUserRight", array("userID"=>$userID, "customerRightID"=>null))) {
			$rights = true;
		} else {
			$rights = array();
			foreach($GLOBALS["database"]->stdList("adminCustomerRight", array("customerID"=>$customerID), array("customerRightID", "right")) as $right) {
				$rights[$right["right"]] = $GLOBALS["database"]->stdExists("adminUserRight", array("userID"=>$userID, "customerRightID"=>$right["customerRightID"]));
			}
		}
	}
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
	
	$rowspan = count(rights()) + 2;
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
	
	foreach(rights() as $right) {
		if(!$GLOBALS["database"]->stdExists("adminCustomerRight", array("customerID"=>$customerID, "right"=>$right["name"]))) {
			continue;
		}
		$titleHtml = htmlentities($right["title"]);
		$descriptionHtml = htmlentities($right["description"]);
		$checkedHtml = (is_array($rights) && $rights[$right["name"]]) ? "checked=\"checked\"" : "";
		
		$rightsHtml .= "<tr>\n";
		$rightsHtml .= "<td></td>\n";
		if($readonly) {
			if($checkedHtml != "") {
				$rightsHtml .= "<td><input type=\"checkbox\" disabled=\"disabled\" $checkedHtml /><input type=\"hidden\" name=\"right-{$right["name"]}\" value=\"1\" /></td>\n";
			} else {
				$rightsHtml .= "<td><input type=\"checkbox\" disabled=\"disabled\" /></td>\n";
			}
			$rightsHtml .= "<td>$titleHtml</td>\n";
		} else {
			$rightsHtml .= "<td><input type=\"checkbox\" name=\"right-{$right["name"]}\" id=\"right-{$right["name"]}\" value=\"1\" class=\"right\" $checkedHtml /></td>\n";
			$rightsHtml .= "<td><label for=\"right-{$right["name"]}\">$titleHtml</label></td>\n";
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
<tr class="submit"><td colspan="5"><input type="submit" value="Save" /></td></tr>
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
<table><tr class="submit"><td>
<input type="submit" value="Remove Account" />
</td></tr></table>
</form>
</div>

HTML;
}

function adminAccountList()
{
	$output  = "<div class=\"list sortable\">\n";
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

function addAdminAccountForm($error = "", $name = null, $password = null)
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
<tr class="submit">
<td colspan="2"><input type="submit" value="Add" /></td>
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
<table><tr class="submit"><td>
<input type="submit" value="Remove Account" />
</td></tr></table>
</form>
</div>

HTML;
}

?>