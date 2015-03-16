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
	return crumbs(_("Customers"), "");
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
	return listTable(array(_("Nickname"), _("Name"), _("Email"), _("Balance")), $rows, _("Customers"), true, "sortable list");
}

function customerBalance($customerID)
{
	return summaryTable(_("Balance"), array(
		_("Balance")=>array("url"=>"{$GLOBALS["rootHtml"]}billing/customer.php?id=$customerID", "html"=>formatPrice(billingBalance($customerID)))
	));
}

function addCustomerForm($error = "", $values = null)
{
	if($values === null) {
		$values = array(
			"countryCode"=>"NL",
			"invoiceFrequencyBase"=>"MONTH",
			"btwStatus"=>"excludingBTW",
		);
	}
	
	return operationForm("addcustomer.php", $error, _("Add customer"), _("Add"), array(
		array("title"=>_("Nickname"), "type"=>"text", "name"=>"name"),
		array("title"=>_("Initials"), "type"=>"text", "name"=>"initials"),
		array("title"=>_("Last name"), "type"=>"text", "name"=>"lastName"),
		array("title"=>_("Company name"), "type"=>"text", "name"=>"companyName"),
		array("title"=>_("Address"), "type"=>"text", "name"=>"address"),
		array("title"=>_("Postal code"), "type"=>"text", "name"=>"postalCode"),
		array("title"=>_("City"), "type"=>"text", "name"=>"city"),
		array("title"=>_("Country"), "type"=>"dropdown", "name"=>"countryCode", "options"=>dropdown(countryCodes())),
		array("title"=>_("Email"), "type"=>"text", "name"=>"email"),
		array("title"=>_("Phone number"), "type"=>"text", "name"=>"phoneNumber"),
		array("title"=>_("Invoice interval"), "type"=>"colspan", "columns"=>array(
			array("type"=>"html", "html"=>_("per")),
			array("type"=>"text", "name"=>"invoiceFrequencyMultiplier", "fill"=>true),
			array("type"=>"dropdown", "name"=>"invoiceFrequencyBase", "options"=>dropdown(array("DAY"=>_("days"), "MONTH"=>_("months"), "YEAR"=>_("years"))))
		)),
		array("title"=>_("Invoice BTW status"), "type"=>"dropdown", "name"=>"btwStatus", "options"=>array(
			array("label"=>_("Send invoices excluding BTW (for companies)"), "value"=>"excludingBTW"),
			array("label"=>_("Send invoices including BTW (for individuals)"), "value"=>"includingBTW"),
		)),
	), $values);
}

function editCustomerForm($customerID, $error = "", $values = null)
{
	$customer = stdGet("adminCustomer", array("customerID"=>$customerID), array("name", "companyName", "initials", "lastName", "address", "postalCode", "city", "countryCode", "email", "phoneNumber", "invoiceFrequencyBase", "invoiceFrequencyMultiplier", "btwStatus"));
	
	if($values === null) {
		$values = $customer;
	}
	return operationForm("editcustomer.php?id=$customerID", $error, _("Edit customer"), _("Edit"), array(
		array("title"=>_("Nickname"), "type"=>"html", "html"=>$customer["name"]),
		array("title"=>_("Initials"), "type"=>"text", "name"=>"initials"),
		array("title"=>_("Last name"), "type"=>"text", "name"=>"lastName"),
		array("title"=>_("Company name"), "type"=>"text", "name"=>"companyName"),
		array("title"=>_("Address"), "type"=>"text", "name"=>"address"),
		array("title"=>_("Postal code"), "type"=>"text", "name"=>"postalCode"),
		array("title"=>_("City"), "type"=>"text", "name"=>"city"),
		array("title"=>_("Country"), "type"=>"dropdown", "name"=>"countryCode", "options"=>dropdown(countryCodes())),
		array("title"=>_("Email"), "type"=>"text", "name"=>"email"),
		array("title"=>_("Phone number"), "type"=>"text", "name"=>"phoneNumber"),
		array("title"=>_("Invoice interval"), "type"=>"colspan", "columns"=>array(
			array("type"=>"html", "html"=>_("per")),
			array("type"=>"text", "name"=>"invoiceFrequencyMultiplier", "fill"=>true),
			array("type"=>"dropdown", "name"=>"invoiceFrequencyBase", "options"=>dropdown(array("DAY"=>"days", "MONTH"=>"months", "YEAR"=>"years")))
		)),
		array("title"=>_("Invoice BTW status"), "type"=>"dropdown", "name"=>"btwStatus", "options"=>array(
			array("label"=>_("Send invoices excluding BTW (for companies)"), "value"=>"excludingBTW"),
			array("label"=>_("Send invoices including BTW (for individuals)"), "value"=>"includingBTW"),
		)),
	), $values);
}

function customerAccountDescription($name, $initials, $lastName)
{
	return sprintf(_("Customer account for customer %s"), "$name ($initials $lastName)");
}

?>