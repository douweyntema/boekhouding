<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doHttpDomain($domainID);
	
	if(isStubDomain($domainID)) {
		error404();
	}
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(makeHeader("Web hosting - " . httpDomainName($domainID), domainBreadcrumbs($domainID), crumbs("Remove domain", "removedomain.php?id=$domainID")) . removeDomainForm($domainID, $error, $_POST)));
	};
	
	$keepsubs = post("keepsubs") !== null;
	
	$aliases = array_unique(httpAliasesToDomain($domainID, !$keepsubs));
	if(count($aliases) > 0) {
		$error = "The requested site(s) cannot be removed because of the following aliases:";
		$error .= "<ul>";
		foreach($aliases as $alias) {
			$name = httpPathName($alias);
			$target = stdGet("httpPath", array("pathID"=>$alias), "mirrorTargetPathID");
			$targetName = httpPathName($target);
			$error .= "<li><a href=\"{$GLOBALS["root"]}http/path.php?id=$alias\">$name</a>: alias for <a href=\"{$GLOBALS["root"]}http/path.php?id=$target\">$targetName</a></li>";
		}
		$error .= "</ul>";
		$error .= "<p>Please remove there aliases and retry.</p>";
		$check(false, $error);
	}
	
	$check(post("confirm") !== null, null);
	
	$parentDomainID = stdGet("httpDomain", array("domainID"=>$domainID), "parentDomainID");
	$isRootDomain = isRootDomain($domainID);
	
	startTransaction();
	httpRemoveDomain($domainID, $keepsubs);
	commitTransaction();
	
	// Distribute the accounts database
	updateHttp(customerID());
	
	if($isRootDomain) {
		redirect("http/");
	} else {
		while(!stdExists("httpPath", array("domainID"=>$parentDomainID, "parentPathID"=>null))) {
			$parentDomainID = stdGet("httpDomain", array("domainID"=>$parentDomainID), "parentDomainID");
		}
		redirect("http/domain.php?id=$parentDomainID");
	}
}

main();

?>