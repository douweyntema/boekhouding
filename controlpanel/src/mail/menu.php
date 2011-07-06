<?php

require_once(dirname(__FILE__) . "/../common.php");

function menuMail()
{
	if($GLOBALS["menuComponent"] == "mail") {
		return <<<HTML
<li>
<a href="{$GLOBALS["rootHtml"]}mail/">Email</a>
</li>

HTML;
	} else {
		return "<li><a href=\"{$GLOBALS["rootHtml"]}mail/\">Email</a></li>\n";
	}
}

if(canAccessComponent("mail", true)) {
	addMenu("menuMail");
} else if(canAccessComponent("mail")) {
	addAdminMenu("menuMail");
}

?>