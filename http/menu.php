<?php

require_once(dirname(__FILE__) . "/../common.php");

function menuHttp()
{
	if($GLOBALS["menuComponent"] == "http") {
		return <<<HTML
<li>
<a href="{$GLOBALS["rootHtml"]}http/">Webhosting</a>
<ul>
<li><a href="{$GLOBALS["rootHtml"]}http/functie1.php">Functie 1</a></li>
<li><a href="{$GLOBALS["rootHtml"]}http/functie2.php">Functie 2</a></li>
</ul>
</li>

HTML;
	} else {
		return "<li><a href=\"{$GLOBALS["rootHtml"]}http/\">Webhosting</a></li>\n";
	}
}

if(canAccessComponent("http", true)) {
	addMenu("menuHttp");
} else if(canAccessComponent("http")) {
	addAdminMenu("menuHttp");
}

?>