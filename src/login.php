<?php

require_once(dirname(__FILE__) . "/common.php");

session_start();

unset($GLOBALS["loginCustomerID"]);
unset($GLOBALS["loginUserID"]);
unset($GLOBALS["loginUsername"]);
unset($GLOBALS["loginImpersonatedCustomer"]);

if(isset($GLOBALS["controlpanelDisabled"]) && $GLOBALS["controlpanelDisabled"]) {
	session_destroy();
	$content = <<<HTML
<div class="controlpanel-disabled"><div class="operation">
<h2>{$_("Control panel disabled")}</h2>
{$GLOBALS["controlpanelDisabledNotice"]}
</div></div>
HTML;
	echo htmlHeader(welcomeHeader() . $content);
	die();
}

if((isset($_SESSION["username"]) && isset($_SESSION["password"])) ||
   (isset($GLOBALS["loginAllowed"]) && post("username") !== null && post("password") !== null)) {
	if(isset($GLOBALS["loginAllowed"]) && post("username") !== null && post("password") !== null) {
		$username = post("username");
		$password = post("password");
	} else {
		$username = $_SESSION["username"];
		$password = $_SESSION["password"];
	}
	
	$user = stdGetTry("adminUser", array("username"=>$username), array("userID", "username", "password"), false);
	if($user === false) {
		loginFailed();
	}
	if(!verifyPassword($password, $user["password"])) {
		loginFailed();
	}
	
	$customerID = 0;
	$impersonate = false;
	
	if($impersonate) {
		$customer = stdGetTry("adminCustomer", array("customerID"=>$customerID), "name", false);
		if($customer === false) {
			$customerID = 0;
			$impersonate = false;
		}
	}
	
	$_SESSION["username"] = $username;
	$_SESSION["password"] = $password;
	if($impersonate) {
		$_SESSION["impersonatedCustomerID"] = $customerID;
	} else {
		unset($_SESSION["impersonatedCustomerID"]);
	}
	
	$GLOBALS["loginCustomerID"] = $customerID;
	$GLOBALS["loginUserID"] = $user["userID"];
	$GLOBALS["loginUsername"] = $user["username"];
	if($impersonate) {
		$GLOBALS["loginImpersonatedCustomer"] = $customer;
	}
} else {
	echo htmlHeader(welcomeHeader() . "<div class=\"main-login\">" . loginForm() . "</div>");
	die();
}

function loginForm($failed = false)
{
	global $_;
	if($failed) {
		$errorHtml = <<<HTML
<tr>
<td colspan="2"><p class="error">{$_("Invalid username or password.")}</p></td>
</tr>

HTML;
	} else {
		$errorHtml = "";
	}
	return <<<HTML
<div class="operation">
<h2>Login</h2>
$errorHtml
<form action="{$GLOBALS["rootHtml"]}" method="post">
<table class="login">
<tr>
<th>{$_("Username")}:</th>
<td><input type="text" name="username" /></td>
</tr>
<tr>
<th>{$_("Password")}:</th>
<td><input type="password" name="password" /></td>
</tr>
<tr>
<td colspan="2"><input type="submit" value="{$_("Login")}" /></td>
</tr>
</table>
</form>
</div>

HTML;
}

function loginFailed()
{
	session_destroy();
	echo htmlHeader(welcomeHeader() . "<div class=\"main-login\">" . loginForm(true) . "</div>");
	die();
}

function isRoot()
{
	return isLoggedIn() && customerID() == 0;
}

function canAccessCustomerComponent($component, $customerID = null)
{
	if($customerID === null) {
		$customerID = customerID();
	}
	$components = components();
	if(!isset($components[$component])) {
		return false;
	}
	
	if($customerID == 0) {
		return true;
	}
	
	if($components[$component]["target"] == "admin") {
		return false;
	}
	
	$customerRightID = stdGetTry("adminCustomerRight", array("customerID"=>$customerID, "right"=>$component), "customerRightID", false);
	if($customerRightID === false) {
		return false;
	}
	
	if(isImpersonating()) {
		return true;
	}
	
	if(stdExists("adminUserRight", array("userID"=>userID(), "customerRightID"=>null))) {
		return true;
	}
	
	if(stdExists("adminUserRight", array("userID"=>userID(), "customerRightID"=>$customerRightID))) {
		return true;
	}
	
	return false;
}

function canAccessComponent($component)
{
	$components = components();
	if(!isset($components[$component])) {
		return false;
	}
	
	if(isImpersonating()) {
		return true;
	}
	if(isRoot()) {
		return !($components[$component]["target"] == "customer");
	}
	
	return canAccessCustomerComponent($component);
}

function canUserAccessComponent($userID, $component)
{
	$components = components();
	if(!isset($components[$component])) {
		return false;
	}
	
	if($components[$component]["target"] == "admin") {
		return false;
	}
	
	$customerID = stdGet("adminUser", array("userID"=>$userID), "customerID");
	$customerRightID = stdGetTry("adminCustomerRight", array("customerID"=>$customerID, "right"=>$component), "customerRightID", false);
	if($customerRightID === false) {
		return false;
	}
	
	if(stdExists("adminUserRight", array("userID"=>$userID, "customerRightID"=>null))) {
		return true;
	}
	
	if(stdExists("adminUserRight", array("userID"=>$userID, "customerRightID"=>$customerRightID))) {
		return true;
	}
	
	return false;
}

function useComponent($component)
{
	if(!canAccessComponent($component)) {
		echo page("<div class=\"loginError\">Access denied to component '$component'.</div>");
		die();
	}
}

function useCustomer($customerID)
{
	if($customerID === false || $customerID === null || (!isRoot() && $customerID !== customerID())) {
		header("HTTP/1.1 404 Not Found");
		die("The requested page could not be found.");
	}
}

function isLoggedIn()
{
	return isset($GLOBALS["loginCustomerID"]);
}

function customerID()
{
	return $GLOBALS["loginCustomerID"];
}

function userID()
{
	return $GLOBALS["loginUserID"];
}

function username()
{
	return $GLOBALS["loginUsername"];
}

function isImpersonating()
{
	return isset($GLOBALS["loginImpersonatedCustomer"]);
}

function impersonatedCustomer()
{
	return $GLOBALS["loginImpersonatedCustomer"];
}

?>