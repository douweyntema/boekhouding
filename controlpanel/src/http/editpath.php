<?php

require_once("common.php");

function main()
{
	$pathID = get("id");
	doHttpPath($pathID);
	$domainID = stdGet("httpPath", array("pathID"=>$pathID), "domainID");
	
	$check = function($condition, $error) use($domainID, $pathID) {
		if(!$condition) die(page(makeHeader("Web hosting - " . httpDomainName($domainID), pathBreadcrumbs($pathID), crumbs("Edit site", "editpath.php?id=$pathID")) . editPathForm($pathID, $error, $_POST)));
	};
	
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
	
		$check((int)$mirrorTarget != (int)$pathID, "");
		$check(($path = stdGetTry("httpPath", array("pathID"=>$mirrorTarget), array("domainID", "type"))) !== null, "");
		$check($path["type"] != "MIRROR", "");
		$check(stdGet("httpDomain", array("domainID"=>$path["domainID"]), "customerID") == customerID(), "");
		
		$function = array("type"=>"MIRROR", "mirrorTargetPathID"=>$mirrorTarget);
	} else {
		die("Internal error");
	}
	
	$check(post("confirm") !== null, null);
	
	stdSet("httpPath", array("pathID"=>$pathID), array_merge(array("hostedUserID"=>null, "hostedPath"=>null, "redirectTarget"=>null, "mirrorTargetPathID"=>null), $function));
	
	updateHttp(customerID());
	
	redirect("http/domain.php?id=$domainID");
}

main();

?>