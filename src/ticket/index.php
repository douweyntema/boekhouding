<?php

require_once("common.php");

function main()
{
	doTicket();
	
	$content = "<h1>Tickets</h1>\n";
	
	$content .= breadcrumbs(array(array("url"=>"{$GLOBALS["root"]}ticket/", "name"=>"Tickets")));
	
	if(isset($_GET["status"])) {
		$status = $_GET["status"];
	} else {
		$status = "OPEN";
	}
	
	$content .= threadList($status);
	if($status == "OPEN") {
		$content .= "<p><a href=\"{$GLOBALS["root"]}ticket/index.php?status=CLOSED\">Show closed tickets</a></p>";
	} else {
		$content .= "<p><a href=\"{$GLOBALS["root"]}ticket/index.php?status=OPEN\">Show open tickets</a></p>";
	}
	
	if(!isRoot()) {
		$content .= newThreadForm();
	}
	
	echo page($content);
}

main();

?>