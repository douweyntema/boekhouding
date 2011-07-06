<?php

require_once("common.php");

function main()
{
	$pathID = get("id");
	doHttpPath($pathID);
	$pathName = pathName($pathID);
	$domainID = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), "domainID");
	
	$content  = "<h1>Web hosting - " . htmlentities($pathName) . "</h1>\n";
	$content .= pathBreadcrumbs($pathID, array(array("name"=>"Add subdirectory", "url"=>"{$GLOBALS["root"]}http/addpath.php?id=$pathID")));
	
	$directoryName = post("name");
	
	$type = typeFromTitle(post("type"));
	$hostedUserID = null;
	$hostedDocumentRoot = null;
	$redirectTarget = null;
	$mirrorTarget = null;
	if($type == "HOSTED") {
		$hostedUserID = post("documentOwner");
		$hostedDocumentRoot = post("documentRoot");
	} else if($type == "REDIRECT") {
		$redirectTarget = post("redirectTarget");
	} else if($type == "MIRROR") {
		$mirrorTarget = post("mirrorTarget");
	}
	
	if($directoryName == "" || $directoryName === null) {
		$content .= addPathForm($pathID, "", $directoryName);
		die(page($content));
	}
	
	$directoryParts = explode("/", $directoryName);
	$valid = count($directoryParts) > 0;
	foreach($directoryParts as $part) {
		if(!validDirectory($part)) {
			$valid = false;
		}
	}
	if(!$valid) {
		$content .= addPathForm($pathID, "Invalid directory name.", $directoryName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
		die(page($content));
	}
	
	if($type === null) {
		$content .= addPathForm($pathID, "", $directoryName, null, null, str_replace("/", "-", "$pathName/$directoryName"));
		die(page($content));
	}
	
	$parentPathID = $pathID;
	$remainingPathParts = $directoryParts;
	while(count($remainingPathParts) > 0) {
		$part = $remainingPathParts[0];
		$path = $GLOBALS["database"]->stdGetTry("httpPath", array("parentPathID"=>$parentPathID, "name"=>$part), "pathID");
		if($path === null) {
			break;
		} else {
			$parentPathID = $path;
			array_shift($remainingPathParts);
		}
	}
	if(count($remainingPathParts) == 0 && $GLOBALS["database"]->stdGetTry("httpPath", array("pathID"=>$parentPathID), "type") != "NONE") {
		$content .= addPathForm($pathID, "A directory with the given name already exists.", $directoryName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
		die(page($content));
	}
	
	if($type == "HOSTED") {
		$httpComponentID = $GLOBALS["database"]->stdGet("adminComponent", array("name"=>"http"), "componentID");
		if($GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$hostedUserID, "customerID"=>customerID()), "userID") === null ||
			($GLOBALS["database"]->stdGetTry("adminUserRight", array("userID"=>$hostedUserID, "componentID"=>$httpComponentID), "userID") === null &&
			$GLOBALS["database"]->stdGetTry("adminUserRight", array("userID"=>$hostedUserID, "componentID"=>null), "userID") === null)
		) {
			$content .= addPathForm($pathID, "", $directoryName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		if(!validDocumentRoot($hostedDocumentRoot)) {
			$content .= addPathForm($pathID, "Invalid document root", $directoryName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		if(post("confirm") === null) {
			$content .= addPathForm($pathID, null, $directoryName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$docroot = trim($hostedDocumentRoot, "/");
		
		$GLOBALS["database"]->startTransaction();
		foreach($remainingPathParts as $part) {
			$parentPathID = $GLOBALS["database"]->stdNew("httpPath", array("domainID"=>$domainID, "parentPathID"=>$parentPathID, "name"=>$part, "type"=>"NONE"));
		}
		$newPathID = $parentPathID;
		$GLOBALS["database"]->stdSet("httpPath", array("pathID"=>$newPathID), array("type"=>"HOSTED", "hostedUserID"=>$hostedUserID, "hostedPath"=>$docroot));
		$GLOBALS["database"]->commitTransaction();
	} else if($type == "REDIRECT") {
		if(post("confirm") === null) {
			$content .= addPathForm($pathID, null, $directoryName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		foreach($remainingPathParts as $part) {
			$parentPathID = $GLOBALS["database"]->stdNew("httpPath", array("domainID"=>$domainID, "parentPathID"=>$parentPathID, "name"=>$part, "type"=>"NONE"));
		}
		$newPathID = $parentPathID;
		$GLOBALS["database"]->stdSet("httpPath", array("pathID"=>$newPathID), array("type"=>"REDIRECT", "redirectTarget"=>$redirectTarget));
		$GLOBALS["database"]->commitTransaction();
	} else if($type == "MIRROR") {
		$path = $GLOBALS["database"]->stdGetTry("httpPath", array("pathID"=>$mirrorTarget), array("domainID", "type"));
		if($path === null || $path["type"] == "MIRROR" || $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$path["domainID"]), "customerID") != customerID()) {
			$content .= addPathForm($pathID, "", $directoryName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		if(post("confirm") === null) {
			$content .= addPathForm($pathID, null, $directoryName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		foreach($remainingPathParts as $part) {
			$parentPathID = $GLOBALS["database"]->stdNew("httpPath", array("domainID"=>$domainID, "parentPathID"=>$parentPathID, "name"=>$part, "type"=>"NONE"));
		}
		$newPathID = $parentPathID;
		$GLOBALS["database"]->stdSet("httpPath", array("pathID"=>$newPathID), array("type"=>"MIRROR", "mirrorTargetPathID"=>$mirrorTarget));
		$GLOBALS["database"]->commitTransaction();
	} else {
		die("Internal error");
	}
	
	updateHttp(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}http/path.php?id=$newPathID");
}

main();

?>