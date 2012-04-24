<?php

require_once(dirname(__FILE__) . "/../common.php");
require_once("api.php");

function doTicket()
{
	useComponent("ticket");
	$GLOBALS["menuComponent"] = "ticket";
}

function doTicketThread($threadID)
{
	doTicket();
	if(!isRoot()) {
		useCustomer($GLOBALS["database"]->stdGetTry("ticketThread", array("threadID"=>$threadID), "customerID", false));
	}
}

function crumb($name, $filename)
{
	return array("name"=>$name, "url"=>"{$GLOBALS["root"]}ticket/$filename");
}

function crumbs($name, $filename)
{
	return array(crumb($name, $filename));
}

function ticketsBreadcrumbs()
{
	return crumbs("Support", "");
}

function ticketBreadcrumbs($threadID)
{
	return array_merge(ticketsBreadcrumbs(), crumbs("Ticket #$threadID", "thread.php?id=$threadID"));
}

function threadList($status = "OPEN")
{
	$output = "";
	
	if(isRoot()) {
		$threads = $GLOBALS["database"]->stdList("ticketThread", array("status"=>$status), array("threadID", "customerID", "userID", "title", "date"));
		$customerHeader = "<th>Customer</th>";
	} else {
		$threads = $GLOBALS["database"]->stdList("ticketThread", array("customerID"=>customerID(), "status"=>$status), array("threadID", "userID", "title", "date"));
		$customerHeader = "";
	}
	if(count($threads) == 0) {
		return "<h4>There are no " . strtolower($status) . " tickets.</h4>";
	}
	$statusHtml = "All " . strtolower($status) . " tickets:";
	$output .= <<<HTML
<h4>$statusHtml</h4>
<div class="list sortable">
<table>
<thead>
<tr><th>Ticket nr.</th>$customerHeader<th>User</th><th>Title</th><th>Date</th></tr>
</thead>
<tbody>

HTML;
	foreach($threads as $thread) {
		$customerHtml = "";
		if(isRoot()) {
			if($thread["customerID"] === null) {
				$customerHtml = "<td>-</td>";
			} else {
				$customerHtml = "<td>" . htmlentities($GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$thread["customerID"]), "name")) . "</td>";
			}
		}
		$userName = htmlentities($GLOBALS["database"]->stdGet("adminUser", array("userID"=>$thread["userID"]), "username"));
		$title = htmlentities($thread["title"]);
		$date = date("d-m-Y H:i", $thread["date"]);
		$threadID = htmlentities($thread["threadID"]);
		
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}ticket/thread.php?id={$threadID}\">#$threadID</a></td>$customerHtml<td>$userName</td><td><a href=\"{$GLOBALS["rootHtml"]}ticket/thread.php?id={$threadID}\">$title</a></td><td>$date</td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function showThread($threadID)
{
	$output = "";
	
	$thread = $GLOBALS["database"]->stdGet("ticketThread", array("threadID"=>$threadID), array("threadID", "customerID", "userID", "status", "title", "text", "date"));
	
	$replies = $GLOBALS["database"]->stdList("ticketReply", array("threadID"=>$threadID), array("replyID", "userID", "text", "date"), array("date"=>"asc"));
	
	$customerHtml = "";
	if(isRoot()) {
		if($thread["customerID"] === null) {
			$customerHtml = "<tr><th>Customer:</th><td> - </td></tr>\n";
		} else {
			$customerHtml = "<tr><th>Customer:</th><td>" . htmlentities($GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$thread["customerID"]), "name")) . "</td></tr>\n";
		}
	}
	$userName = htmlentities($GLOBALS["database"]->stdGet("adminUser", array("userID"=>$thread["userID"]), "username"));
	$status = htmlentities(strtolower($thread["status"]));
	$title = htmlentities($thread["title"]);
	$date = date("d-m-Y H:i", $thread["date"]);
	$text = nl2br(htmlentities($thread["text"]));
	
	$output .= <<<HTML
<div class="operation">
<h2>$title</h2>
<table>
$customerHtml
<tr><th>Reported by:</th><td>$userName</td></tr>
<tr><th>Status:</th><td>$status</td></tr>
<tr><th>Date:</th><td>$date</td></tr>
</table>
<p>$text</p>

HTML;
	
	foreach($replies as $reply) {
		$userName = htmlentities($GLOBALS["database"]->stdGet("adminUser", array("userID"=>$reply["userID"]), "username"));
		$date = date("d-m-Y H:i", $reply["date"]);
		$text = nl2br(htmlentities($reply["text"]));
		
		$output .= <<<HTML
<div class="operation">
<h3>Reply by $userName on $date</h3>
<p>$text</p>
</div>

HTML;
	}
	
	$output .= "</div>";
	
	return $output;
}

function newThreadForm($error = "", $values = null)
{
	return operationForm("addthread.php", $error, "New ticket", "Create ticket",
		array(
			array("title"=>"Title", "type"=>"text", "name"=>"title"),
			array("title"=>null, "type"=>"textarea", "name"=>"text")
		),
		$values);
}

function newReplyForm($threadID, $error = "", $values = null)
{
	$closed = $GLOBALS["database"]->stdGet("ticketThread", array("threadID"=>$threadID), "status") == "CLOSED";
	return operationForm("addreply.php?id=$threadID", $error, "New reply", "Reply",
		array(
			array("title"=>null, "type"=>"textarea", "name"=>"text"),
			$closed ?
				array("title"=>null, "type"=>"checkbox", "name"=>"reopen", "label"=>"Reopen ticket")
			:
				array("title"=>null, "type"=>"checkbox", "name"=>"close", "label"=>"Close ticket")
		),
		$values);
}

?>