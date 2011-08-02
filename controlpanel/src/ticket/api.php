<?php

$ticketTitle = "Tickets";
$ticketDescription = "Tickets";
$ticketTarget = "both";

function ticketNewThread($customerID, $userID, $title, $text)
{
	return $GLOBALS["database"]->stdNew("ticketThread", array("customerID"=>$customerID, "userID"=>$userID, "title"=>$title, "text"=>$text, "status"=>"OPEN", "date"=>time()));
}

function ticketNewReply($threadID, $userID, $text, $status)
{
	$GLOBALS["database"]->startTransaction();
	$replyID = $GLOBALS["database"]->stdNew("ticketReply", array("threadID"=>$threadID, "userID"=>$userID, "text"=>$text, "date"=>time()));
	$GLOBALS["database"]->stdSet("ticketThread", array("threadID"=>$threadID), array("status"=>$status));
	$GLOBALS["database"]->commitTransaction();
	return $replyID;
}

?>