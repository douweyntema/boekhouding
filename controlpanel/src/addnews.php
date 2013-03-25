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
		redirect("");
	}
	
	stdNew("adminNews", array("title"=>$title, "text"=>$text, "date"=>$date));
	
	redirect("");
}

main();

?>