<?php

require_once("common.php");

function main()
{
	$pathID = get("id");
	doHttpPath($pathID);
	
	if(stdGet("httpPath", array("pathID"=>$pathID), "parentPathID") === null) {
		error404();
	}
	
	$check = function($condition, $error) use($pathID) {
		if(!$condition) die(page(makeHeader("Web hosting - " . httpPathName($pathID), pathBreadcrumbs($pathID), crumbs("Remove site", "removepath.php?id=$pathID")) . removePathForm($pathID, $error, $_POST)));
	};
	
	$keepsubs = post("keepsubs") !== null;
	
	$aliases = array_unique(httpAliasesToPath($pathID, !$keepsubs));
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
	
	$parentPathID = stdGet("httpPath", array("pathID"=>$pathID), "parentPathID");
	$domainID = stdGet("httpPath", array("pathID"=>$pathID), "domainID");
	
	startTransaction();
	httpRemovePath($pathID, $keepsubs);
	while($parentPathID !== null &&
		stdGet("httpPath", array("pathID"=>$parentPathID), "type") == "NONE" &&
		!stdExists("httpPath", array("parentPathID"=>$parentPathID)))
	{
		$grandparentPathID = stdGet("httpPath", array("pathID"=>$parentPathID), "parentPathID");
		stdDel("httpPath", array("pathID"=>$parentPathID));
		$parentPathID = $grandparentPathID;
	}
	commitTransaction();
	
	// Distribute the accounts database
	updateHttp(customerID());
	
	if(stdGet("httpPath", array("pathID"=>$parentPathID), "parentPathID") === null) {
		redirect("http/domain.php?id=$domainID");
	} else {
		redirect("http/path.php?id=$parentPathID");
	}
}

main();

?>