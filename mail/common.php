<?php

require_once(dirname(__FILE__) . "/../common.php");

function menuMail()
{
	if($GLOBALS["menuComponent"] == "mail") {
		return <<<HTML
<li>
<a href="{$GLOBALS["rootHtml"]}mail/">Email</a>
<ul>
<li><a href="{$GLOBALS["rootHtml"]}mail/functie1.php">Functie 1</a></li>
<li><a href="{$GLOBALS["rootHtml"]}mail/functie2.php">Functie 2</a></li>
</ul>
</li>

HTML;
	} else {
		return "<li><a href=\"{$GLOBALS["rootHtml"]}mail/\">Email</a></li>\n";
	}
}

function doMail($x)
{
	
}

?>