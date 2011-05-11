<?php

require_once("common.php");

function main()
{
	doHttp();
	
	$content  = "<h1>Web hosting</h1>\n";
	$content .= breadcrumbs(array(array("name"=>"Web hosting", "url"=>"{$GLOBALS["root"]}http/"), array("name"=>"Add domain", "url"=>"{$GLOBALS["root"]}http/adddomain.php")));
	
	$rootDomainID = post("rootDomainID");
	$subdomainName = post("name");
	
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
	
	if($subdomainName == "" || $subdomainName === null || $rootDomainID === null) {
		$content .= addDomainForm("", $rootDomainID, $subdomainName);
		die(page($content));
	}
	
	$subdomainParts = explode(".", $subdomainName);
	$valid = count($subdomainParts) > 0;
	foreach($subdomainParts as $part) {
		if(!validSubdomain($part)) {
			$valid = false;
		}
	}
	if(!$valid) {
		$content .= addDomainForm("Invalid domain name.", $rootDomainID, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
		die(page($content));
	}
	
	if($type === null) {
		$content .= addDomainForm("", $rootDomainID, $subdomainName, null, null, "$subdomainName." . domainName($rootDomainID));
		die(page($content));
	}
	
	$parentDomainID = $rootDomainID;
	$remainingDomainParts = array_reverse($subdomainParts);
	while(count($remainingDomainParts) > 0) {
		$part = $remainingDomainParts[0];
		$domain = $GLOBALS["database"]->stdGetTry("httpDomain", array("parentDomainID"=>$parentDomainID, "name"=>$part), "domainID");
		if($domain === null) {
			break;
		} else {
			$parentDomainID = $domain;
			array_shift($remainingDomainParts);
		}
	}
	if(count($remainingDomainParts) == 0 && !isStubDomain($parentDomainID)) {
		$content .= addDomainForm("A domain with the given name already exists.", $rootDomainID, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
		die(page($content));
	}
	
	if($type == "HOSTED") {
		$httpComponentID = $GLOBALS["database"]->stdGet("adminComponent", array("name"=>"http"), "componentID");
		if($GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$hostedUserID, "customerID"=>customerID()), "userID") === null ||
			($GLOBALS["database"]->stdGetTry("adminUserRight", array("userID"=>$hostedUserID, "componentID"=>$httpComponentID), "userID") === null &&
			$GLOBALS["database"]->stdGetTry("adminUserRight", array("userID"=>$hostedUserID, "componentID"=>null), "userID") === null)
		) {
			$content .= addDomainForm("", $rootDomainID, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		if(!validDocumentRoot($hostedDocumentRoot)) {
			$content .= addDomainForm("Invalid document root", $rootDomainID, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		if(post("confirm") === null) {
			$content .= addDomainForm(null, $rootDomainID, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$docroot = trim($hostedDocumentRoot, "/");
		
		$GLOBALS["database"]->startTransaction();
		foreach($remainingDomainParts as $part) {
			$parentDomainID = $GLOBALS["database"]->stdNew("httpDomain", array("customerID"=>customerID(), "parentDomainID"=>$parentDomainID, "name"=>$part));
		}
		$newDomainID = $parentDomainID;
		$GLOBALS["database"]->stdNew("httpPath", array("parentPathID"=>null, "domainID"=>$newDomainID, "name"=>null, "type"=>"HOSTED", "hostedUserID"=>$hostedUserID, "hostedPath"=>$docroot));
		$GLOBALS["database"]->commitTransaction();
	} else if($type == "REDIRECT") {
		if(post("confirm") === null) {
			$content .= addDomainForm(null, $rootDomainID, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		foreach($remainingDomainParts as $part) {
			$parentDomainID = $GLOBALS["database"]->stdNew("httpDomain", array("customerID"=>customerID(), "parentDomainID"=>$parentDomainID, "name"=>$part));
		}
		$newDomainID = $parentDomainID;
		$GLOBALS["database"]->stdNew("httpPath", array("parentPathID"=>null, "domainID"=>$newDomainID, "name"=>null, "type"=>"REDIRECT", "redirectTarget"=>$redirectTarget));
		$GLOBALS["database"]->commitTransaction();
	} else if($type == "MIRROR") {
		$path = $GLOBALS["database"]->stdGetTry("httpPath", array("pathID"=>$mirrorTarget), array("domainID", "type"));
		if($path === null || $path["type"] == "MIRROR" || $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$path["domainID"]), "customerID") != customerID()) {
			$content .= addDomainForm("", $rootDomainID, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		if(post("confirm") === null) {
			$content .= addDomainForm(null, $rootDomainID, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		foreach($remainingDomainParts as $part) {
			$parentDomainID = $GLOBALS["database"]->stdNew("httpDomain", array("customerID"=>customerID(), "parentDomainID"=>$parentDomainID, "name"=>$part));
		}
		$newDomainID = $parentDomainID;
		$GLOBALS["database"]->stdNew("httpPath", array("parentPathID"=>null, "domainID"=>$newDomainID, "name"=>null, "type"=>"MIRROR", "mirrorTargetPathID"=>$mirrorTarget));
		$GLOBALS["database"]->commitTransaction();
	} else {
		die("Internal error");
	}
	
	updateHttp(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}http/domain.php?id=$newDomainID");
}

main();

?>