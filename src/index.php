<?php

$GLOBALS["loginAllowed"] = true;

require_once("common.php");

$content = "<h1>" . _("Welcome") . "</h1>";
$content .= breadcrumbs(array(array("name"=>_("Home"), "url"=>"{$GLOBALS["root"]}")));

$newsItems = stdList("adminNews", array(), array("title", "text", "date"), array("date"=>"desc"));
$count = 0;
foreach($newsItems as $item) {
	$count++;
	if($count > 5) {
		break;
	}
	$titleHtml = htmlentities($item["title"]);
	$textHtml = nl2br(htmlentities($item["text"]));
	$dateHtml = date("Y-m-d H:i", $item["date"]);
	
	$content .= <<<HTML
<div class="news">
<h2>$titleHtml</h2>
<p class="date">$dateHtml</p>
<p>$textHtml</p>
</div>

HTML;
}

if(isRoot()) {
	$content .= operationForm("addnews.php", "", _("New news item"), _("Add news item"), array(
		array("title"=>_("Title"), "type"=>"text", "name"=>"title"),
		array("title"=>null, "type"=>"textarea", "name"=>"text"),
	), null);
}

echo page($content);

?>