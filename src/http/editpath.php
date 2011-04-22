<?php

require_once("common.php");

function main()
{
	$pathID = get("id");
	doHttpPath($pathID);
	$domainID = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), "domainID");
	
	$content = "<h1>Web hosting - " . domainName($domainID) . "</h1>\n";
	
	$content .= pathBreadcrumbs($pathID, array(array("name"=>"Edit site", "url"=>"{$GLOBALS["root"]}http/editpath.php?id=$pathID")));
	
	$type = typeFromTitle(isset($_POST["type"]) ? $_POST["type"] : null);
	$hostedUserID = null;
	$hostedDocumentRoot = null;
	$redirectTarget = null;
	$mirrorTarget = null;
	if($type == "HOSTED") {
		$hostedUserID = $_POST["documentOwner"];
		$hostedDocumentRoot = $_POST["documentRoot"];
	} else if($type == "REDIRECT") {
		$redirectTarget = $_POST["redirectTarget"];
	} else if($type == "MIRROR") {
		$mirrorTarget = $_POST["mirrorTarget"];
	}
	
	if($type === null) {
		$content .= editPathForm($domainID, $pathID, "");
	} else {
		$content .= editPathForm($domainID, $pathID, null, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
	}
	
	echo page($content);
}

main();

?>