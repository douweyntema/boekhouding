<?php

require_once("common.php");

function main()
{
	doTicket();
	
	if(isset($_GET["status"])) {
		$status = $_GET["status"];
	} else {
		$status = "OPEN";
	}
	
	$content = makeHeader("Support", ticketsBreadcrumbs());
	
	if($status == "OPEN") {
		$content .= "<h4>All open tickets:</h4>\n";
		$content .= isRoot() ? adminThreadList("OPEN") : threadList("OPEN");
		$content .= "<p><a href=\"{$GLOBALS["root"]}ticket/index.php?status=CLOSED\">Show closed tickets</a></p>";
	} else {
		$content .= "<h4>All closed tickets:</h4>\n";
		$content .= isRoot() ? adminThreadList("CLOSED") : threadList("CLOSED");
		$content .= "<p><a href=\"{$GLOBALS["root"]}ticket/index.php?status=OPEN\">Show open tickets</a></p>";
	}
	
	if(!isRoot()) {
		$content .= newThreadForm();
	}
	
	echo page($content);
}

main();

?>