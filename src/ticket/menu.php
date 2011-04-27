<?php

require_once(dirname(__FILE__) . "/../common.php");

function menuTicket()
{
	if($GLOBALS["menuComponent"] == "ticket" && !isRoot()) {
		return <<<HTML
<li>
<a href="{$GLOBALS["rootHtml"]}ticket/">Tickets</a>
<ul>
<li><a href="{$GLOBALS["rootHtml"]}ticket/addthread.php">New ticket</a></li>
</ul>
</li>

HTML;
	} else {
		return "<li><a href=\"{$GLOBALS["rootHtml"]}ticket/\">Tickets</a></li>\n";
	}
}

addMenu("menuTicket");

?>