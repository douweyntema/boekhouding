<?php

$ticketTitle = "Support";
$ticketDescription = "Support tickets";
$ticketTarget = "both";

function ticketNewThread($customerID, $userID, $title, $text)
{
	$threadID = stdNew("ticketThread", array("customerID"=>$customerID, "userID"=>$userID, "title"=>$title, "text"=>$text, "status"=>"OPEN", "date"=>time()));
	
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
	startTransaction();
	$replyID = stdNew("ticketReply", array("threadID"=>$threadID, "userID"=>$userID, "text"=>$text, "date"=>time()));
	stdSet("ticketThread", array("threadID"=>$threadID), array("status"=>$status));
	$customerID = stdGet("ticketThread", array("threadID"=>$threadID), "customerID");
	$title = stdGet("ticketThread", array("threadID"=>$threadID), "title");
	commitTransaction();
	
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