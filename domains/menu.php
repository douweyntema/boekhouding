<?php

require_once(dirname(__FILE__) . "/../common.php");

function menuDomains()
{
	if($GLOBALS["menuComponent"] == "domains") {
		return <<<HTML
<li>
<a href="{$GLOBALS["rootHtml"]}domains/">Domains</a>
<ul>
<li><a href="{$GLOBALS["rootHtml"]}domains/functie1.php">Functie 1</a></li>
<li><a href="{$GLOBALS["rootHtml"]}domains/functie2.php">Functie 2</a></li>
</ul>
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