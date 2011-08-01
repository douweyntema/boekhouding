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
	
	if(!validSubdomain($subdomainName)) {
		$content .= addDomainForm("Invalid domain name.", $rootDomainID, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
		die(page($content));
	}
	
	if($type === null) {
		$content .= addDomainForm("", $rootDomainID, $subdomainName, null, null, "$subdomainName." . domainName($rootDomainID));
		die(page($content));
	}
	
	$domain = $GLOBALS["database"]->stdGetTry("httpDomain", array("parentDomainID"=>$rootDomainID, "name"=>$subdomainName), "domainID");
	if($domain !== null) {
		$content .= addDomainForm("A domain with the given name already exists.", $rootDomainID, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
		die(page($content));
	}
	
	if($type == "HOSTED") {
		if(!$GLOBALS["database"]->stdExists("adminUser", array("userID"=>$hostedUserID, "customerID"=>customerID()))) {
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
		$newDomainID = $GLOBALS["database"]->stdNew("httpDomain", array("customerID"=>customerID(), "parentDomainID"=>$rootDomainID, "name"=>$subdomainName));
		$GLOBALS["database"]->stdNew("httpPath", array("parentPathID"=>null, "domainID"=>$newDomainID, "name"=>null, "type"=>"HOSTED", "hostedUserID"=>$hostedUserID, "hostedPath"=>$docroot));
		$GLOBALS["database"]->commitTransaction();
	} else if($type == "REDIRECT") {
		if(post("confirm") === null) {
			$content .= addDomainForm(null, $rootDomainID, $subdomainName, $type, $hostedUserID, $hostedDocumentRoot, $redirectTarget, $mirrorTarget);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		$newDomainID = $GLOBALS["database"]->stdNew("httpDomain", array("customerID"=>customerID(), "parentDomainID"=>$rootDomainID, "name"=>$subdomainName));
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
		$newDomainID = $GLOBALS["database"]->stdNew("httpDomain", array("customerID"=>customerID(), "parentDomainID"=>$rootDomainID, "name"=>$subdomainName));
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