<?php

require_once(dirname(__FILE__) . "/common.php");

$GLOBALS["menuComponent"] = null;
$GLOBALS["menuComponents"] = array();
$GLOBALS["menuComponentsAdmin"] = array();

foreach(components() as $component) {
	require_once(dirname(__FILE__) . "/{$component["name"]}/menu.php");
}

function menu()
{
	$output = "";
	
	$output .= "<ul>\n";
	$output .= "<li><a href=\"{$GLOBALS["rootHtml"]}\">Welcome</a></li>\n";
	foreach($GLOBALS["menuComponents"] as $component) {
		$output .= call_user_func($component);
	}
	$output .= "</ul>\n";
	
	if(isImpersonating()) {
		$found = false;
		$menu = "<ul class=\"menu-admin\">\n";
		foreach($GLOBALS["menuComponentsAdmin"] as $component) {
			$item = call_user_func($component);
			if($item != "") {
				$menu .= $item;
				$found = true;
			}
		}
		$menu  .= "</ul>\n";
		if($found) {
			$output .= $menu;
		}
	}
	
	return $output;
}

function addMenu($function)
{
	$GLOBALS["menuComponents"][] = $function;
}

function addAdminMenu($function)
{
	$GLOBALS["menuComponentsAdmin"][] = $function;
}

?>