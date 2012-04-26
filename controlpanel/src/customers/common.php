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
	return array_merge(customersBreadcrumbs(), crumbs($GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "name"), "customer.php?id=$customerID"));
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

function customerLogin($customerID)
{
	$customerName = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "name");
	$customerNameHtml = htmlentities($customerName);
	$usernameHtml = htmlentities(username());
	
	return operationForm(null, "", "Login for this customer", null, array(
		array("type"=>"html", "html"=>"Login as $usernameHtml@$customerNameHtml", "url"=>"{$GLOBALS["rootHtml"]}index.php?customerID=$customerID")
	), null);
}

function addCustomerForm($error = "", $values = null)
{
	if($values === null) {
		$values = array(
			"country"=>"NL",
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
		array("title"=>"Group", "type"=>"text", "name"=>"groupname"),
		array("title"=>"Disk quota", "type"=>"colspan", "columns"=>array(
			array("type"=>"text", "name"=>"diskQuota", "fill"=>true),
			array("type"=>"html", "html"=>"MiB")
		)),
		array("title"=>"Mail quota", "type"=>"colspan", "columns"=>array(
			array("type"=>"text", "name"=>"mailQuota", "fill"=>true),
			array("type"=>"html", "html"=>"MiB")
		)),
		array("title"=>"Filesystem", "type"=>"dropdown", "name"=>"fileSystemID", "options"=>dropdown($GLOBALS["database"]->stdMap("infrastructureFileSystem", array(), "fileSystemID", "name"))),
		array("title"=>"Mailsystem", "type"=>"dropdown", "name"=>"mailSystemID", "options"=>dropdown($GLOBALS["database"]->stdMap("infrastructureMailSystem", array(), "mailSystemID", "name"))),
		array("title"=>"Namesystem", "type"=>"dropdown", "name"=>"nameSystemID", "options"=>dropdown($GLOBALS["database"]->stdMap("infrastructureNameSystem", array(), "nameSystemID", "name"))),
		array("title"=>"Invoice interval", "type"=>"colspan", "columns"=>array(
			array("type"=>"html", "html"=>"per"),
			array("type"=>"text", "name"=>"invoiceFrequencyMultiplier", "fill"=>true),
			array("type"=>"dropdown", "name"=>"invoiceFrequencyBase", "options"=>dropdown(array("DAY"=>"days", "MONTH"=>"months", "YEAR"=>"years")))
		))
	), $values);
}

function editCustomerForm($customerID, $error = "", $values = null)
{
	$customer = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), array("fileSystemID", "mailSystemID", "nameSystemID", "name", "companyName", "initials", "lastName", "address", "postalCode", "city", "countryCode", "email", "phoneNumber", "groupname", "diskQuota", "mailQuota", "invoiceFrequencyBase", "invoiceFrequencyMultiplier"));
	
	$fileSystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$customer["fileSystemID"]), "name"));
	$mailSystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$customer["mailSystemID"]), "name"));
	$nameSystemNameHtml = htmlentities($GLOBALS["database"]->stdGet("infrastructureNameSystem", array("nameSystemID"=>$customer["nameSystemID"]), "name"));
	
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
		array("title"=>"Group", "type"=>"html", "html"=>$customer["groupname"]),
		array("title"=>"Disk quota", "type"=>"colspan", "columns"=>array(
			array("type"=>"text", "name"=>"diskQuota", "fill"=>true),
			array("type"=>"html", "html"=>"MiB")
		)),
		array("title"=>"Mail quota", "type"=>"colspan", "columns"=>array(
			array("type"=>"text", "name"=>"mailQuota", "fill"=>true),
			array("type"=>"html", "html"=>"MiB")
		)),
		array("title"=>"Filesystem", "type"=>"html", "html"=>$fileSystemNameHtml, "url"=>"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$customer["fileSystemID"]}"),
		array("title"=>"Mailsystem", "type"=>"html", "html"=>$mailSystemNameHtml, "url"=>"{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id={$customer["mailSystemID"]}"),
		array("title"=>"Namsystem", "type"=>"html", "html"=>$nameSystemNameHtml, "url"=>"{$GLOBALS["rootHtml"]}infrastructure/namesystem.php?id={$customer["nameSystemID"]}"),
		array("title"=>"Invoice interval", "type"=>"colspan", "columns"=>array(
			array("type"=>"html", "html"=>"per"),
			array("type"=>"text", "name"=>"invoiceFrequencyMultiplier", "fill"=>true),
			array("type"=>"dropdown", "name"=>"invoiceFrequencyBase", "options"=>dropdown(array("DAY"=>"days", "MONTH"=>"months", "YEAR"=>"years")))
		))
	), $values);
}

function editCustomerRightsForm($customerID, $error = "", $values = null)
{
	if($values === null) {
		$values = array();
		foreach($GLOBALS["database"]->stdList("adminCustomerRight", array("customerID"=>$customerID), "right") as $right) {
			$values["right-" . $right] = true;
		}
	}
	
	$fields = array();
	$fields[] = array("type"=>"colspan", "columns"=>array(
		array("type"=>"html", "html"=>"", "celltype"=>"th"),
		array("type"=>"html", "html"=>"Right", "celltype"=>"th"),
		array("type"=>"html", "html"=>"Description", "celltype"=>"th", "fill"=>true)
	));
	$fields[] = array("type"=>"hidden", "name"=>"posted", "value"=>"1");
	foreach(rights() as $right) {
		$descriptionHtml = htmlentities($right["description"]);
		$fields[] = array("type"=>"colspan", "columns"=>array(
			array("type"=>"checkbox", "name"=>"right-{$right["name"]}", "id"=>"checkbox-right-{$right["name"]}"),
			array("type"=>"label", "label"=>$right["title"], "id"=>"checkbox-right-{$right["name"]}", "cellclass"=>"nowrap"),
			array("type"=>"html", "html"=>$descriptionHtml, "fill"=>true)
		));
	}
	return operationForm("editcustomerrights.php?id=$customerID", $error, "Edit customer rights", "Edit", $fields, $values);
}

function dropdown($list)
{
	$result = array();
	foreach($list as $value=>$label) {
		$result[] = array("value"=>$value, "label"=>$label);
	}
	return $result;
}

?>