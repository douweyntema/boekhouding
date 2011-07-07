<?php

require_once(dirname(__FILE__) . "/../common.php");

function menuInfrastructure()
{
	if($GLOBALS["menuComponent"] == "infrastructure") {
		return <<<HTML
<li>
<a href="{$GLOBALS["rootHtml"]}infrastructure/">Infrastructure</a>
</li>

HTML;
	} else {
		return "<li><a href=\"{$GLOBALS["rootHtml"]}infrastructure/\">Infrastructure</a></li>\n";
	}
}

if(isRoot()) {
	addMenu("menuInfrastructure");
}

?>