<?php

require_once("common.php");

function main()
{
	doTicket();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader("Support", ticketsBreadcrumbs(), crumbs("New ticket", "addthread.php")) . newThreadForm($error, $_POST)));
	};
	
	$title = post("title");
	$text = post("text");
	
	$check(!isRoot(), "A root user cannot create tickets");
	$check($title !== null || $text !== null, "");
	$check($title != "" && $text != "", "Please provide a title and a description.");
	$check(post("confirm") !== null, null);

	$newThreadID = ticketNewThread(customerID(), userID(), $title, $text);
	
	redirect("ticket/thread.php?id=$newThreadID");
}

main();

?>