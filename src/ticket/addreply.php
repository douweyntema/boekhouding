<?php

require_once("common.php");

function main()
{
	$threadID = get("id");
	doTicketThread($threadID);
	
	$content  = "<h1>Ticket #$threadID</h1>\n";
	$content .= breadcrumbs(array(
		array("url"=>"{$GLOBALS["root"]}ticket/", "name"=>"Tickets"),
		array("url"=>"{$GLOBALS["root"]}ticket/thread.php?id=$threadID", "name"=>"Ticket #$threadID"),
		array("url"=>"{$GLOBALS["root"]}ticket/addreply.php?id=$threadID", "name"=>"New reply")
	));
	
	$userID = userID();
	$text = post("text");
	$date = time();
	$status = post("status");
	if($status === null) {
		$status = $GLOBALS["database"]->stdGet("ticketThread", array("threadID"=>$threadID), "status");
	}
	
	if($text == null) {
		header("HTTP/1.1 303 See Other");
		header("Location: {$GLOBALS["root"]}ticket/thread.php?id=$threadID");
		die();
	}
	if($text == "") {
		$content .= newReplyForm($threadID, "Please provide a description.", $text, $status);
		echo page($content);
		die();
	}
	if(!($status == "OPEN" || $status == "CLOSED")) {
		$content .= newReplyForm($threadID, "Status can only be open or closed.", $text, $status);
		echo page($content);
		die();
	}
	if(post("confirm") === null) {
		$content .= newReplyForm($threadID, null, $text, $status);
		echo page($content);
		die();
	}
	
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdNew("ticketReply", array("threadID"=>$threadID, "userID"=>$userID, "text"=>$text, "date"=>$date));
	$GLOBALS["database"]->stdSet("ticketThread", array("threadID"=>$threadID), array("status"=>$status));
	$GLOBALS["database"]->commitTransaction();
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}ticket/thread.php?id=$threadID");
}

main();

?>