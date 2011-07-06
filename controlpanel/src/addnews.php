<?php

require_once("common.php");

function main()
{
	if(!isRoot()) {
		error404();
	}
	$title = post("title");
	$text = post("text");
	$date = time();
	if($title == null || $text == null || $title == "" || $text == "") {
		header("HTTP/1.1 303 See Other");
		header("Location: {$GLOBALS["root"]}");
	}
	
	$GLOBALS["database"]->stdNew("adminNews", array("title"=>$title, "text"=>$text, "date"=>$date));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}");
}

main();

?>