<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomain($domainID);
	$domainName = domainName($domainID);
	
	$content = "<h1>Domain " . $domainName . "</h1>\n";
	
	$content .= domainBreadcrumbs($domainID, array(array("name"=>"Edit domain", "url"=>"{$GLOBALS["root"]}domains/edithost.php?id=$domainID")));
	
	
	
	die(page($content));
	
	/*
	- address doosje
	- mail doosje
	- custom doosje
	
	
	- other options doosje:
	  - add mail (treva of extern) -> ENUM dnsDomain (none, treva, extern); extern -> nieuwe tabel
	  - custom dns record -> tabel dnsRecord
	- address: ENUM dnsDomain (none, parent, treva, ip, cname, symlink, extern)
	  - treva web servers
	  - IP(s) -> dnsRecord
	  - cname (dname) -> value
	  - symbolische link (zoals http alias) -> id
	  - external dns hosting -> nieuwe tabel
	*/
	
	
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
		if(!$GLOBALS["database"]->stdExists("adminUser", array("userID"=>$hostedUserID, "customerID"=>customerID()))) {
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