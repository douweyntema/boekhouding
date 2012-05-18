<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomain($domainID);
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(makeHeader("Domain " . domainsFormatDomainName($domainID), domainBreadcrumbs($domainID), crumbs("Edit email", "editmail.php?id=$domainID")) . editMailForm($domainID, $error, $_POST)));
	};
	
	$check(($type = searchKey($_POST, "none", "treva", "custom")) !== null, "");
	
	$remove = function() use($domainID, $check) {
		$check(post("confirm") !== null, null);
		
		$GLOBALS["database"]->startTransaction();
		$GLOBALS["database"]->stdDel("dnsMailServer", array("domainID"=>$domainID));
	};
	
	if($type == "none") {
		$remove();
		$function = array("mailType"=>"NONE");
	} else if($type == "treva") {
		$remove();
		$function = array("mailType"=>"TREVA");
	} else if($type == "custom") {
		$servers = parseArrayField($_POST, array("server"));
		
		$error = array();
		foreach($servers as $server) {
			if(!validDomain($server["server"])) {
				$error[] = "Invalid mailserver: " . htmlentities($server["server"]);
			}
		}
		if(count($error) > 0) {
			$check(false, implode("<br />", $error));
		}
		
		$remove();
		$index = 0;
		foreach($servers as $server) {
			$GLOBALS["database"]->stdNew("dnsMailServer", array("domainID"=>$domainID, "name"=>$server["server"], "priority"=>(10 * ++$index)));
		}
		$function = array("mailType"=>"CUSTOM");
	} else {
		die("Internal error");
	}
	
	$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), $function);
	$GLOBALS["database"]->commitTransaction();
	
	updateDomains(customerID());
	
	redirect("domains/domain.php?id=$domainID");
}

main();

?>