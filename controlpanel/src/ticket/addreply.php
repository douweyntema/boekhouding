<?php

require_once("common.php");

function main()
{
	$threadID = get("id");
	doTicketThread($threadID);
	
	$check = function($condition, $error) use($threadID) {
		if(!$condition) die(page(makeHeader("Support - Ticket #$threadID", ticketBreadcrumbs($threadID)) . newReplyForm($threadID, $error, $_POST)));
	};
	
	$text = post("text");
	$closed = stdGet("ticketThread", array("threadID"=>$threadID), "status") == "CLOSED";
	$doReopen = post("reopen") !== null;
	$doClose = post("close") !== null;
	
	if($text === null || $text == "") {
		redirect("ticket/thread.php?id=$threadID");
	}
	
	$check(post("confirm") !== null, null);
	
	ticketNewReply($threadID, userID(), $text, ($closed ? $doReopen : !$doClose) ? "OPEN" : "CLOSED");
	
	redirect("ticket/thread.php?id=$threadID");
}

main();

?>