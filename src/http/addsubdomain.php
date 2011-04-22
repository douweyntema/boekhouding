<?php

require_once("common.php");

function main()
{
	$domainID = $_GET["id"];
	doHttpDomain($domainID);
	$domainName = domainName($domainID);
	
	$content  = "<h1>Web hosting - " . htmlentities($domainName) . "</h1>\n";
	$content .= breadcrumbs(array(
		array("name"=>"Web hosting", "url"=>"{$GLOBALS["root"]}http/"),
		array("name"=>$domainName, "url"=>"{$GLOBALS["root"]}http/domain.php?id=$domainID"),
		array("name"=>"Add subdomain", "url"=>"{$GLOBALS["root"]}http/addsubdomain.php?id=$domainID")
		));
	
	$subdomainName = $_POST["name"];
	
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
	
	$subdomainParts = explode(".", $subdomainName);
	$valid = count($subdomainParts) > 0;
	foreach($subdomainParts as $part) {
		if(!validSubdomain($part)) {
			$valid = false;
		}
	}
	if(!$valid) {
		$content .= addSubdomainForm($domainID, "Invalid domain name", $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
		die(page($content));
	}
	
	if($type === null) {
		$content .= addSubdomainForm($domainID, "", $subdomainName, null, null, $subdomainName . "." . $domainName);
		die(page($content));
	}
	
	$parentDomainID = $domainID;
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
		$content .= addSubdomainForm($domainID, "Domain name is in use", $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
		die(page($content));
	}
	
	if($type == "HOSTED") {
		$httpComponentID = $GLOBALS["database"]->stdGet("adminComponent", array("name"=>"http"), "componentID");
		if($GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$hostedUserID, "customerID"=>customerID()), "userID") === null ||
			($GLOBALS["database"]->stdGetTry("adminUserRight", array("userID"=>$hostedUserID, "componentID"=>$httpComponentID), "userID") === null &&
			$GLOBALS["database"]->stdGetTry("adminUserRight", array("userID"=>$hostedUserID, "componentID"=>null), "userID") === null)
		) {
			$content .= addSubdomainForm($domainID, "", $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		if(!validDocumentRoot($hostedDocumentRoot)) {
			$content .= addSubdomainForm($domainID, "Invalid document root", $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		if(!isset($_POST["confirm"])) {
			$content .= addSubdomainForm($domainID, null, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$docroot = trim($hostedDocumentRoot, "/");
		
		$GLOBALS["database"]->startTransaction();
		foreach($remainingDomainParts as $part) {
			$parentDomainID = $GLOBALS["database"]->stdNew("httpDomain", array("customerID"=>customerID(), "parentDomainID"=>$parentDomainID, "name"=>$part));
		}
		$newDomainID = $parentDomainID;
		$GLOBALS["database"]->stdNew("httpPath", array("parentPathID"=>null, "domainID"=>$newDomainID, "name"=>null, "type"=>"HOSTED", "hostedUserID"=>$hostedUserID, "hostedPath"=>$docroot));
	} else if($type == "REDIRECT") {
		if(!isset($_POST["confirm"])) {
			$content .= addSubdomainForm($domainID, null, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		foreach($remainingDomainParts as $part) {
			$parentDomainID = $GLOBALS["database"]->stdNew("httpDomain", array("customerID"=>customerID(), "parentDomainID"=>$parentDomainID, "name"=>$part));
		}
		$newDomainID = $parentDomainID;
		$GLOBALS["database"]->stdNew("httpPath", array("parentPathID"=>null, "domainID"=>$newDomainID, "name"=>null, "type"=>"REDIRECT", "redirectTarget"=>$redirectTarget));
	} else if($type == "MIRROR") {
		$path = $GLOBALS["database"]->stdGetTry("httpPath", array("pathID"=>$mirrorTarget), array("domainID", "type"));
		if($path === null || $path["type"] == "MIRROR" || $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$path["domainID"]), "customerID") != customerID()) {
			$content .= addSubdomainForm($domainID, "", $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		if(!isset($_POST["confirm"])) {
			$content .= addSubdomainForm($domainID, null, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		foreach($remainingDomainParts as $part) {
			$parentDomainID = $GLOBALS["database"]->stdNew("httpDomain", array("customerID"=>customerID(), "parentDomainID"=>$parentDomainID, "name"=>$part));
		}
		$newDomainID = $parentDomainID;
		$GLOBALS["database"]->stdNew("httpPath", array("parentPathID"=>null, "domainID"=>$newDomainID, "name"=>null, "type"=>"MIRROR", "mirrorTargetPathID"=>$redirectTarget));
	} else {
		die("Internal error");
	}
	
	$GLOBALS["database"]->commitTransaction();
	
	updateHttp(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}http/domain.php?id=$newDomainID");
}

main();

?>