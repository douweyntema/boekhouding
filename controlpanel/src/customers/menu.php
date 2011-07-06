<?php

require_once(dirname(__FILE__) . "/../common.php");

function menuCustomers()
{
	if($GLOBALS["menuComponent"] == "customers") {
		return <<<HTML
<li>
<a href="{$GLOBALS["rootHtml"]}customers/">Customers</a>
<ul>
<li><a href="{$GLOBALS["rootHtml"]}customers/addcustomer.php">Add customer</a></li>
</ul>
</li>

HTML;
	} else {
		return "<li><a href=\"{$GLOBALS["rootHtml"]}customers/\">Customers</a></li>\n";
	}
}

if(isRoot()) {
	addMenu("menuCustomers");
}

?>