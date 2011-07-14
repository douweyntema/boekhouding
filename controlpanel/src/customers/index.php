<?php

$GLOBALS["loginAllowed"] = true;

require_once("common.php");

function main()
{
	doCustomers();
	
	$content = "<h1>Customers</h1>\n";
	
	$content .= breadcrumbs(array(
		array("name"=>"Customers", "url"=>"{$GLOBALS["root"]}customers/")
		));
	
	$content .= customerList();
	
	$content .= addCustomerForm();
	
	echo page($content);
}

main();

?>