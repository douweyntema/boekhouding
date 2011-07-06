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
		die(page($content));
	}
	
	if($type == "HOSTED") {
		$httpComponentID = $GLOBALS["database"]->stdGet("adminComponent", array("name"=>"http"), "componentID");
		if($GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$hostedUserID, "customerID"=>customerID()), "userID") === null ||
			($GLOBALS["database"]->stdGetTry("adminUserRight", array("userID"=>$hostedUserID, "componentID"=>$httpComponentID), "userID") === null &&
			$GLOBALS["database"]->stdGetTry("adminUserRight", array("userID"=>$hostedUserID, "componentID"=>null), "userID") === null)
		) {
			$content .= editPathForm($domainID, $pathID, "", $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		if(!validDocumentRoot($hostedDocumentRoot)) {
			$content .= editPathForm($domainID, $pathID, "Invalid document root", $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		if(post("confirm") === null) {
			$content .= editPathForm($domainID, $pathID, null, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$docroot = trim($hostedDocumentRoot, "/");
		
		$GLOBALS["database"]->stdSet("httpPath", array("pathID"=>$pathID), array("type"=>"HOSTED", "hostedUserID"=>$hostedUserID, "hostedPath"=>$docroot, "redirectTarget"=>null, "mirrorTargetPathID"=>null));
	} else if($type == "REDIRECT") {
		if(post("confirm") === null) {
			$content .= editPathForm($domainID, $pathID, null, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$GLOBALS["database"]->stdSet("httpPath", array("pathID"=>$pathID), array("type"=>"REDIRECT", "redirectTarget"=>$redirectTarget, "hostedUserID"=>null, "hostedPath"=>null, "mirrorTargetPathID"=>null));
	} else if($type == "MIRROR") {
		$path = $GLOBALS["database"]->stdGetTry("httpPath", array("pathID"=>$mirrorTarget), array("domainID", "type"));
		if($path === null || $path["type"] == "MIRROR" || $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$path["domainID"]), "customerID") != customerID()) {
			$content .= editPathForm($domainID, $pathID, "", $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		if(post("confirm") === null) {
			$content .= editPathForm($domainID, $pathID, null, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$GLOBALS["database"]->stdSet("httpPath", array("pathID"=>$pathID), array("type"=>"MIRROR", "mirrorTargetPathID"=>$mirrorTarget, "hostedUserID"=>null, "hostedPath"=>null, "redirectTarget"=>null));
	} else {
		die("Internal error");
	}
	
	updateHttp(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}http/domain.php?id=$domainID");
}

main();

?>