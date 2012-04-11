<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doHttpDomain($domainID);
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(makeHeader("Add subdomain", domainBreadcrumbs($domainID), crumbs("Add subdomain", "addsubdomain.php?id=$domainID")) . addSubdomainForm($domainID, $error, $_POST)));
	};
	
	$check(($name = post("name")) !== null, "");
	$check(validSubdomain($name), "Invalid domain name.");
	$check(!$GLOBALS["database"]->stdExists("httpDomain", array("parentDomainID"=>$domainID, "name"=>$name)), "A domain with the given name already exists.");
	
	if(post("documentRoot") == null) {
		$_POST["documentRoot"] = $name . "." . domainName($domainID);
	}
	
	$check(($type = searchKey($_POST, "hosted", "redirect", "mirror")) !== null, "");
	
	if($type == "hosted") {
		$userID = post("documentOwner");
		$directory = trim(post("documentRoot"), "/");
		
		$check($GLOBALS["database"]->stdExists("adminUser", array("userID"=>$userID, "customerID"=>customerID())), "");
		$check(validDocumentRoot($directory), "Invalid document root");
		
		$function = array("type"=>"HOSTED", "hostedUserID"=>$userID, "hostedPath"=>$directory);
	} else if($type == "redirect") {
		$function = array("type"=>"REDIRECT", "redirectTarget"=>post("redirectTarget"));
	} else if($type == "mirror") {
		$mirrorTarget = post("mirrorTarget");
		
		$check(($path = $GLOBALS["database"]->stdGetTry("httpPath", array("pathID"=>$mirrorTarget), array("domainID", "type"))) !== null, "");
		$check($path["type"] != "MIRROR", "");
		$check($GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$path["domainID"]), "customerID") == customerID(), "");
		
		$function = array("type"=>"MIRROR", "mirrorTargetPathID"=>$mirrorTarget);
	} else {
		die("Internal error");
	}
	
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->startTransaction();
	$newDomainID = $GLOBALS["database"]->stdNew("httpDomain", array("customerID"=>customerID(), "parentDomainID"=>$domainID, "name"=>$name));
	$GLOBALS["database"]->stdNew("httpPath", array_merge(array("parentPathID"=>null, "domainID"=>$newDomainID, "name"=>null), $function));
	$GLOBALS["database"]->commitTransaction();
	
	updateHttp(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}http/domain.php?id=$newDomainID");
}

main();

?>