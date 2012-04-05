<?php

require_once("common.php");

function main()
{
	$threadID = get("id");
	doTicketThread($threadID);
	
	$check = function($condition, $error) use($threadID) {
		if(!$condition) die(page(threadHeader($threadID) . newReplyForm($threadID, $error, $_POST)));
	};
	
	$text = post("text");
	$closed = $GLOBALS["database"]->stdGet("ticketThread", array("threadID"=>$threadID), "status") == "CLOSED";
	$doReopen = post("reopen") !== null;
	$doClose = post("close") !== null;
	
	if($text === null || $text == "") {
		header("HTTP/1.1 303 See Other");
		header("Location: {$GLOBALS["root"]}ticket/thread.php?id=$threadID");
		die();
	}
	
	$check(post("confirm") !== null, null);
	
	ticketNewReply($threadID, userID(), $text, ($closed ? $doReopen : !$doClose) ? "OPEN" : "CLOSED");
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}ticket/thread.php?id=$threadID");
}

main();

?>