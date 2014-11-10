<?php

require_once("common.php");

function main()
{
	$pathID = get("id");
	doHttpPath($pathID);
	
	$check = function($condition, $error) use($pathID) {
		if(!$condition) die(page(makeHeader("Add domain", pathBreadcrumbs($pathID), crumbs("Add subdirectory", "addpath.php?id=$pathID")) . addPathForm($pathID, $error, $_POST)));
	};
	
	$domainID = stdGet("httpPath", array("pathID"=>$pathID), "domainID");
	
	$check(($directoryName = post("name")) !== null, "");
	$directoryParts = explode("/", $directoryName);
	$check(count($directoryParts) > 0, "");
	foreach($directoryParts as $part) {
		$check(validDirectory($part), "Invalid directory name.");
	}
	
	$parentPathID = $pathID;
	while(count($directoryParts) > 0) {
		$part = $directoryParts[0];
		$path = stdGetTry("httpPath", array("parentPathID"=>$parentPathID, "name"=>$part), "pathID");
		if($path === null) {
			break;
		} else {
			$parentPathID = $path;
			array_shift($directoryParts);
		}
	}
	$check(count($directoryParts) > 0 || stdGet("httpPath", array("pathID"=>$parentPathID), "type") == "NONE", "A directory with the chosen name already exists.");
	
	if(post("documentRoot") == null) {
		$_POST["documentRoot"] = str_replace("/", "-", httpPathName($pathID) . "/$directoryName");
	}
	
	$check(($type = searchKey($_POST, "hosted", "redirect", "mirror")) !== null, "");
	
	if($type == "hosted") {
		$userID = post("documentOwner");
		$directory = trim(post("documentRoot"), "/");
		
		$check(stdExists("adminUser", array("userID"=>$userID, "customerID"=>customerID())), "");
		$check(validDocumentRoot($directory), "Invalid document root.");
		
		$function = array("type"=>"HOSTED", "hostedUserID"=>$userID, "hostedPath"=>$directory);
	} else if($type == "redirect") {
		$function = array("type"=>"REDIRECT", "redirectTarget"=>post("redirectTarget"));
	} else if($type == "mirror") {
		$mirrorTarget = post("mirrorTarget");
		
		$check(($path = stdGetTry("httpPath", array("pathID"=>$mirrorTarget), array("domainID", "type"))) !== null, "");
		$check($path["type"] != "MIRROR", "");
		$check(stdGet("httpDomain", array("domainID"=>$path["domainID"]), "customerID") == customerID(), "");
		
		$function = array("type"=>"MIRROR", "mirrorTargetPathID"=>$mirrorTarget);
	} else {
		die("Internal error");
	}
	
	$check(post("confirm") !== null, null);
	
	startTransaction();
	foreach($directoryParts as $part) {
		$parentPathID = stdNew("httpPath", array("domainID"=>$domainID, "parentPathID"=>$parentPathID, "name"=>$part, "type"=>"NONE"));
	}
	stdSet("httpPath", array("pathID"=>$parentPathID), $function);
	commitTransaction();
	
	updateHttp(customerID());
	
	redirect("http/path.php?id=$parentPathID");
}

main();

?>