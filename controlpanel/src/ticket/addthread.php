<?php

require_once("common.php");

function main()
{
	doTicket();
	
	$content  = "<h1>Support</h1>\n";
	$content .= breadcrumbs(array(
		array("url"=>"{$GLOBALS["root"]}ticket/", "name"=>"Support"),
		array("url"=>"{$GLOBALS["root"]}ticket/addthread.php", "name"=>"New ticket")
	));
	
	$customerID = customerID();
	$userID = userID();
	$title = post("title");
	$text = post("text");
	$date = time();
	
	if($title == null && $text == null) {
		$content .= newThreadForm();
		echo page($content);
		die();
	}
	if(isRoot()) {
		$content .= newThreadForm("A root user cannot create tickets.", $title, $text);
		echo page($content);
		die();
	}
	if($title == "" || $text == "") {
		$content .= newThreadForm("Please provide a title and a description.", $title, $text);
		echo page($content);
		die();
	}
	if(post("confirm") === null) {
		$content .= newThreadForm(null, $title, $text);
		echo page($content);
		die();
	}

	$newThreadID = $GLOBALS["database"]->stdNew("ticketThread", array("customerID"=>$customerID, "userID"=>$userID, "title"=>$title, "text"=>$text, "status"=>"OPEN", "date"=>$date));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}ticket/thread.php?id=$newThreadID");
}

main();

?>