<?php

$GLOBALS["loginAllowed"] = true;

require_once("common.php");

$content = "<h1>Welcome</h1>";
$content .= breadcrumbs(array(array("name"=>"Home", "url"=>"{$GLOBALS["root"]}")));
$content .= "<p>News here...</p>";

echo page($content);

?>