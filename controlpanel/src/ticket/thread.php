<?php

require_once("common.php");

function main()
{
	$threadID = get("id");
	doTicketThread($threadID);
	
	$content = "<h1>Support - Ticket #$threadID</h1>\n";
	
	$content .= breadcrumbs(array(
		array("url"=>"{$GLOBALS["root"]}ticket/", "name"=>"Support"),
		array("url"=>"{$GLOBALS["root"]}ticket/thread.php?id=$threadID", "name"=>"Ticket #$threadID")
	));
	
	$content .= showThread($threadID);
	$content .= newReplyForm($threadID);
	
	echo page($content);
}

main();

?>