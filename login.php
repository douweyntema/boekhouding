<?php

require_once(dirname(__FILE__) . "/common.php");

session_start();

unset($GLOBALS["loginCustomerID"]);
unset($GLOBALS["loginUserID"]);
unset($GLOBALS["loginUsername"]);
unset($GLOBALS["loginImpersonatedCustomer"]);

if((isset($_SESSION["username"]) && isset($_SESSION["password"])) ||
   (isset($GLOBALS["loginAllowed"]) && isset($_POST["username"]) && isset($_POST["password"]))) {
	if(isset($GLOBALS["loginAllowed"]) && isset($_POST["username"]) && isset($_POST["password"])) {
		$username = $_POST["username"];
		$password = $_POST["password"];
	} else {
		$username = $_SESSION["username"];
		$password = $_SESSION["password"];
	}
	
	$user = $GLOBALS["database"]->stdGetTry("adminUser", array("username"=>$username, "password"=>md5($password)), array("userID", "customerID", "username"), false);
	if($user === false) {
		loginFailed();
	}
	
	if($user["customerID"] != 0) {
		$customerID = $user["customerID"];
		$impersonate = false;
	} else if(isset($GLOBALS["loginAllowed"]) && isset($_GET["customerID"]) && $_GET["customerID"] == 0) {
		$customerID = 0;
		$impersonate = false;
	} else if(isset($GLOBALS["loginAllowed"]) && isset($_GET["customerID"])) {
		$customerID = $_GET["customerID"];
		$impersonate = true;
	} else if(isset($_SESSION["impersonatedCustomerID"])) {
		$customerID = $_SESSION["impersonatedCustomerID"];
		$impersonate = true;
	} else {
		$customerID = 0;
		$impersonate = false;
	}
	
	if($impersonate) {
		$customer = $GLOBALS["database"]->stdGetTry("adminCustomer", array("customerID"=>$customerID), "name", false);
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
	if($failed) {
		$errorHtml = <<<HTML
<tr>
<td colspan="2" class="error">Invalid username or password.</td>
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
<th>Username:</th>
<td><input type="text" name="username" /></td>
</tr>
<tr>
<th>Password:</th>
<td><input type="password" name="password" /></td>
</tr>
<tr>
<td colspan="2"><input type="submit" value="Login" /></td>
</tr>
</table>
</form>
</div>

HTML;
}

function loginFailed()
{
	session_destroy();
	echo htmlHeader(welcomeHeader() . loginForm(true));
	die();
}

function isRoot()
{
	return isLoggedIn() && customerID() == 0;
}

function canAccessComponent($component, $advisory = false)
{
	$data = $GLOBALS["database"]->stdGetTry("adminComponent", array("name"=>$component), array("componentID", "rootOnly"), false);
	if($data === false) {
		return false;
	}
	if($advisory && isRoot()) {
		return false;
	} else if(isRoot()) {
		return true;
	}
	if(!$advisory && isImpersonating()) {
		return true;
	}
	if($data["rootOnly"] != 0) {
		return false;
	}
	$componentID = $data["componentID"];
	if($GLOBALS["database"]->stdGetTry("adminCustomerRight", array("customerID"=>customerID(), "componentID"=>$componentID), "componentID", false) === false) {
		return false;
	}
	if(isImpersonating()) {
		return true;
	}
	if($GLOBALS["database"]->stdGetTry("adminUserRight", array("userID"=>userID(), "componentID"=>0), "userID", false) !== false) {
		return true;
	}
	if($GLOBALS["database"]->stdGetTry("adminUserRight", array("userID"=>userID(), "componentID"=>$componentID), "userID", false) !== false) {
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