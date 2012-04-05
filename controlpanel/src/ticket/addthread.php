<?php

require_once("common.php");

function main()
{
	doTicket();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(addHeader("Support", "addthread.php") . newThreadForm($error, $_POST)));
	};
	
	$title = post("title");
	$text = post("text");
	
	$check(!isRoot(), "A root user cannot create tickets");
	$check($title !== null || $text !== null, "");
	$check($title != "" && $text != "", "Please provide a title and a description.");
	$check(post("confirm") !== null, null);

	$newThreadID = ticketNewThread(customerID(), userID(), $title, $text);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}ticket/thread.php?id=$newThreadID");
}

main();

?>