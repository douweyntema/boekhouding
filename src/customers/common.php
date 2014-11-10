<?php

require_once(dirname(__FILE__) . "/../common.php");

function doCustomers()
{
	useComponent("customers");
	useCustomer(0);
	$GLOBALS["menuComponent"] = "customers";
}

function doCustomer($customerID)
{
	doCustomers();
	useCustomer($customerID);
}

function crumb($name, $filename)
{
	return array("name"=>$name, "url"=>"{$GLOBALS["root"]}customers/$filename");
}

function crumbs($name, $filename)
{
	return array(crumb($name, $filename));
}

function customersBreadcrumbs()
{
	return crumbs("Customers", "");
}

function customerBreadcrumbs($customerID)
{
	return array_merge(customersBreadcrumbs(), crumbs(stdGet("adminCustomer", array("customerID"=>$customerID), "name"), "customer.php?id=$customerID"));
}

function customerList()
{
	$rows = array();
	foreach(stdList("adminCustomer", array(), array("customerID", "name", "initials", "lastName", "email", "invoiceStatus"), array("name"=>"ASC")) as $customer) {
		$nicknameHtml = htmlentities($customer["name"]);
		$balance = billingBalance($customer["customerID"]);
		$rows[] = array(
			array("html"=>"<a href=\"{$GLOBALS["rootHtml"]}customers/customer.php?id={$customer["customerID"]}\">$nicknameHtml</a>"),
			$customer["initials"] . " " . $customer["lastName"],
			array("url"=>"mailto:{$customer["email"]}", "text"=>$customer["email"], "class"=>"nowrap"),
			array("url"=>"{$GLOBALS["rootHtml"]}billing/customer.php?id={$customer["customerID"]}", "html"=>formatPrice($balance), "class"=>$balance < 0 ? "balance-negative" : ($customer["invoiceStatus"] == "DISABLED" ? "balance-disabled" : null))
		);
	}
	return listTable(array("Nickname", "Name", "Email", "Balance"), $rows, "Customers", true, "sortable list");
}

function customerBalance($customerID)
{
	return summaryTable("Balance", array(
		"Balance"=>array("url"=>"{$GLOBALS["rootHtml"]}billing/customer.php?id=$customerID", "html"=>formatPrice(billingBalance($customerID)))
	));
}

function addCustomerForm($error = "", $values = null)
{
	if($values === null) {
		$values = array(
			"countryCode"=>"NL",
			"invoiceFrequencyBase"=>"MONTH",
		);
	}
	
	return operationForm("addcustomer.php", $error, "Add customer", "Add", array(
		array("title"=>"Nickname", "type"=>"text", "name"=>"name"),
		array("title"=>"Initials", "type"=>"text", "name"=>"initials"),
		array("title"=>"Last name", "type"=>"text", "name"=>"lastName"),
		array("title"=>"Company name", "type"=>"text", "name"=>"companyName"),
		array("title"=>"Address", "type"=>"text", "name"=>"address"),
		array("title"=>"Postal code", "type"=>"text", "name"=>"postalCode"),
		array("title"=>"City", "type"=>"text", "name"=>"city"),
		array("title"=>"Country", "type"=>"dropdown", "name"=>"countryCode", "options"=>dropdown(countryCodes())),
		array("title"=>"Email", "type"=>"text", "name"=>"email"),
		array("title"=>"Phone number", "type"=>"text", "name"=>"phoneNumber"),
		array("title"=>"Invoice interval", "type"=>"colspan", "columns"=>array(
			array("type"=>"html", "html"=>"per"),
			array("type"=>"text", "name"=>"invoiceFrequencyMultiplier", "fill"=>true),
			array("type"=>"dropdown", "name"=>"invoiceFrequencyBase", "options"=>dropdown(array("DAY"=>"days", "MONTH"=>"months", "YEAR"=>"years")))
		)),
	), $values);
}

function editCustomerForm($customerID, $error = "", $values = null)
{
	$customer = stdGet("adminCustomer", array("customerID"=>$customerID), array("name", "companyName", "initials", "lastName", "address", "postalCode", "city", "countryCode", "email", "phoneNumber", "invoiceFrequencyBase", "invoiceFrequencyMultiplier"));
	
	if($values === null) {
		$values = $customer;
	}
	return operationForm("editcustomer.php?id=$customerID", $error, "Edit customer", "Edit", array(
		array("title"=>"Nickname", "type"=>"html", "html"=>$customer["name"]),
		array("title"=>"Initials", "type"=>"text", "name"=>"initials"),
		array("title"=>"Last name", "type"=>"text", "name"=>"lastName"),
		array("title"=>"Company name", "type"=>"text", "name"=>"companyName"),
		array("title"=>"Address", "type"=>"text", "name"=>"address"),
		array("title"=>"Postal code", "type"=>"text", "name"=>"postalCode"),
		array("title"=>"City", "type"=>"text", "name"=>"city"),
		array("title"=>"Country", "type"=>"dropdown", "name"=>"countryCode", "options"=>dropdown(countryCodes())),
		array("title"=>"Email", "type"=>"text", "name"=>"email"),
		array("title"=>"Phone number", "type"=>"text", "name"=>"phoneNumber"),
		array("title"=>"Invoice interval", "type"=>"colspan", "columns"=>array(
			array("type"=>"html", "html"=>"per"),
			array("type"=>"text", "name"=>"invoiceFrequencyMultiplier", "fill"=>true),
			array("type"=>"dropdown", "name"=>"invoiceFrequencyBase", "options"=>dropdown(array("DAY"=>"days", "MONTH"=>"months", "YEAR"=>"years")))
		)),
	), $values);
}

function customerAccountDescription($name, $initials, $lastName)
{
	return "Customer account for customer $name ($initials $lastName)";
}

?>