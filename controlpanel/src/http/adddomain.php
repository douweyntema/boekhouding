<?php

require_once("common.php");

function main()
{
	doHttp();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader("Add domain", httpBreadcrumbs(), crumbs("Add domain", "adddomain.php")) . addDomainForm($error, $_POST)));
	};
	
	$check(($domainTldID = post("domainTldID")) !== null, "");
	$check(($name = post("name")) !== null, "");
	$check(validDomainPart($name), "Invalid domain name.");
	
	$tld = stdGetTry("infrastructureDomainTld", array("domainTldID"=>post("domainTldID")), "name", false);
	$check($tld !== false, "");
	$fullDomainNameSql = dbAddSlashes("$name.$tld");
	
	$check(query("SELECT `httpDomain`.`domainID` FROM `httpDomain` INNER JOIN `infrastructureDomainTld` USING(`domainTldID`) WHERE CONCAT_WS('.', `httpDomain`.`name`, `infrastructureDomainTld`.`name`) = '$fullDomainNameSql'")->numRows() == 0, "A domain with the chosen name already exists.");
	
	if(post("documentRoot") == null) {
		$_POST["documentRoot"] = $name . "." . $tld;
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
	$newDomainID = stdNew("httpDomain", array("customerID"=>customerID(), "domainTldID"=>$domainTldID, "parentDomainID"=>null, "name"=>$name));
	stdNew("httpPath", array_merge(array("parentPathID"=>null, "domainID"=>$newDomainID, "name"=>null), $function));
	commitTransaction();
	
	updateHttp(customerID());
	
	redirect("http/domain.php?id=$newDomainID");
}

main();

?>