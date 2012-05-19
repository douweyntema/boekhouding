<?php

$GLOBALS["loginAllowed"] = true;

require_once("common.php");

$content = "<h1>Welcome</h1>";
$content .= breadcrumbs(array(array("name"=>"Home", "url"=>"{$GLOBALS["root"]}")));

$newsItems = $GLOBALS["database"]->stdList("adminNews", array(), array("title", "text", "date"), array("date"=>"desc"));
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

$content .= customersOverview();
$content .= infrastructureOverview();

if(isRoot()) {
	$content .= <<<HTML
<div class="operation">
<h2>New news item</h2>
<form action="addnews.php" method="post">
<table>
<tr><th>Title:</th><td><input type="text" name="title" /></td></tr>
<tr><td colspan="2"><textarea name="text"></textarea></td></tr>
<tr class="submit"><td colspan="2"><input type="submit" value="Add news item"></td></tr>
</table>
</form>
</div>

HTML;
}

echo page($content);

?>