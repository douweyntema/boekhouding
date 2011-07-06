<?php

require_once(dirname(__FILE__) . "/../common.php");

function menuDomains()
{
	if($GLOBALS["menuComponent"] == "domains") {
		return <<<HTML
<li>
<a href="{$GLOBALS["rootHtml"]}domains/">Domains</a>
</li>

HTML;
	} else {
		return "<li><a href=\"{$GLOBALS["rootHtml"]}domains/\">Domains</a></li>\n";
	}
}
if(canAccessComponent("domains", true)) {
	addMenu("menuDomains");
} else if(canAccessComponent("domains")) {
	addAdminMenu("menuDomains");
}

?>