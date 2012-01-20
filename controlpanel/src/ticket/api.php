<?php

$ticketTitle = "Tickets";
$ticketDescription = "Tickets";
$ticketTarget = "both";

function ticketNewThread($customerID, $userID, $title, $text)
{
	$threadID = $GLOBALS["database"]->stdNew("ticketThread", array("customerID"=>$customerID, "userID"=>$userID, "title"=>$title, "text"=>$text, "status"=>"OPEN", "date"=>time()));
	
	global $controlpanelUrl;
	
	$body = <<<MAIL
A new support ticket has been created:

$text

See {$controlpanelUrl}ticket/thread.php?id=$threadID for the complete thread.

You will be notified when an update is posted.

MAIL;
	
	mailCustomer($customerID, "New support ticket [#$threadID]: $title", $body, true);
	
	return $threadID;
}

function ticketNewReply($threadID, $userID, $text, $status)
{
	$GLOBALS["database"]->startTransaction();
	$replyID = $GLOBALS["database"]->stdNew("ticketReply", array("threadID"=>$threadID, "userID"=>$userID, "text"=>$text, "date"=>time()));
	$GLOBALS["database"]->stdSet("ticketThread", array("threadID"=>$threadID), array("status"=>$status));
	$customerID = $GLOBALS["database"]->stdGet("ticketThread", array("threadID"=>$threadID), "customerID");
	$title = $GLOBALS["database"]->stdGet("ticketThread", array("threadID"=>$threadID), "title");
	$GLOBALS["database"]->commitTransaction();
	
	global $controlpanelUrl;
	
	$body = <<<MAIL
A new reply has been posted on your support ticket:

$text

See {$controlpanelUrl}ticket/thread.php?id=$threadID for the complete thread.

You will be notified when an update is posted.

MAIL;
	
	mailCustomer($customerID, "New reply on support ticket [#$threadID]: $title", $body, true);
	
	return $replyID;
}

?>