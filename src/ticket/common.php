<?php

require_once(dirname(__FILE__) . "/../common.php");

function doTicket()
{
// 	useComponent("ticket");
	$GLOBALS["menuComponent"] = "ticket";
}

function doTicketThread($threadID)
{
	doTicket();
	useCustomer($GLOBALS["database"]->stdGetTry("ticketThread", array("threadID"=>$threadID), "customerID", false));
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
			$customerHtml = "<td>" . htmlentities($GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$thread["customerID"]), "name")) . "</td>";
		}
		$userName = htmlentities($GLOBALS["database"]->stdGet("adminUser", array("userID"=>$thread["userID"]), "username"));
		$title = htmlentities($thread["title"]);
		$date = date("d-m-Y H:i", $thread["date"]);
		$threadID = htmlentities($thread["threadID"]);
		
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}ticket/thread.php?id={$threadID}\">#$threadID</a></td>$customerHtml<td>$userName</td><td><a href=\"{$GLOBALS["rootHtml"]}ticket/thread.php?id={$threadID}\">$title</a></td><td>$date</td></tr>";
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
		$customerHtml = "<tr><th>Customer:</th><td>" . htmlentities($GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$thread["customerID"]), "name")) . "</td></tr>\n";
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

function newReplyForm($threadID, $error = "", $text = null, $status = null)
{
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = "readonly=\"readonly\"";
		$disabled = "disabled=\"disabled\"";
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
		$disabled = "";
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
		$disabled = "";
	}
	
	$oldstatus = $GLOBALS["database"]->stdGet("ticketThread", array("threadID"=>$threadID), "status");
	if($status === null) {
		$status = $oldstatus;
	}
	
	$statusChangeText = ($oldstatus == "OPEN") ? "Close ticket" : "Reopen ticket";
	$statusChangeValue = ($oldstatus == "OPEN") ? "CLOSED" : "OPEN";
	
	if($status != $oldstatus) {
		$statusChecked = "checked=\"checked\"";
	} else {
		$statusChecked = "";
	}
	
	$textHtml = ($text === null) ? "" : htmlentities($text);
	
	$output = <<<HTML
<div class="operation">
<h2>New reply</h2>
$messageHtml
<form action="addreply.php?id={$threadID}" method="post">
$confirmHtml
<table>
<tr><td><textarea name="text" $readonly>$textHtml</textarea></td></tr>
<tr><td><label><input type="checkbox" name="status" value="$statusChangeValue" $statusChecked $disabled> $statusChangeText</label></td></tr>
<tr class="submit"><td><input type="submit" name="submit" value="Reply" /></td></tr>
</table>
</form>
</div>

HTML;
	return $output;
}

function newThreadForm($error = "", $title = null, $text = null)
{
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = "readonly=\"readonly\"";
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
	}
	$titleHtml = inputvalue($title);
	$textHtml = ($text === null) ? "" : htmlentities($text);
	
	$output = <<<HTML
<div class="operation">
<h2>New ticket</h2>
$messageHtml
<form action="addthread.php" method="post">
$confirmHtml
<table>
<tr><th>Title:</th><td class="stretch"><input type="text" name="title" $titleHtml $readonly></td></tr>
<tr><td colspan="2"><textarea name="text" $readonly>$textHtml</textarea></td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="submit" value="Create ticket" /></td></tr>
</table>
</form>
</div>

HTML;
	return $output;
}

?>