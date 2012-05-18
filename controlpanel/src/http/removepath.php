<?php

require_once("common.php");

function main()
{
	$pathID = get("id");
	doHttpPath($pathID);
	
	if($GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), "parentPathID") === null) {
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
			$target = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$alias), "mirrorTargetPathID");
			$targetName = httpPathName($target);
			$error .= "<li><a href=\"{$GLOBALS["root"]}http/path.php?id=$alias\">$name</a>: alias for <a href=\"{$GLOBALS["root"]}http/path.php?id=$target\">$targetName</a></li>";
		}
		$error .= "</ul>";
		$error .= "<p>Please remove there aliases and retry.</p>";
		$check(false, $error);
	}
	
	$check(post("confirm") !== null, null);
	
	$parentPathID = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), "parentPathID");
	$domainID = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), "domainID");
	
	$GLOBALS["database"]->startTransaction();
	httpRemovePath($pathID, $keepsubs);
	while($parentPathID !== null &&
		$GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$parentPathID), "type") == "NONE" &&
		!$GLOBALS["database"]->stdExists("httpPath", array("parentPathID"=>$parentPathID)))
	{
		$grandparentPathID = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$parentPathID), "parentPathID");
		$GLOBALS["database"]->stdDel("httpPath", array("pathID"=>$parentPathID));
		$parentPathID = $grandparentPathID;
	}
	$GLOBALS["database"]->commitTransaction();
	
	// Distribute the accounts database
	updateHttp(customerID());
	
	if($GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$parentPathID), "parentPathID") === null) {
		redirect("http/domain.php?id=$domainID");
	} else {
		redirect("http/path.php?id=$parentPathID");
	}
}

main();

?>