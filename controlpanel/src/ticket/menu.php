<?php

require_once(dirname(__FILE__) . "/../common.php");

function menuTicket()
{
	return "<li><a href=\"{$GLOBALS["rootHtml"]}ticket/\">Support</a></li>\n";
}

addMenu("menuTicket");

?>