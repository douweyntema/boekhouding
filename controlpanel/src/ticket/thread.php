<?php

require_once("common.php");

function main()
{
	$threadID = get("id");
	doTicketThread($threadID);
	
	$content = makeHeader("Support - Ticket #$threadID", ticketBreadcrumbs($threadID));
	$content .= showThread($threadID);
	$content .= newReplyForm($threadID);
	echo page($content);
}

main();

?>