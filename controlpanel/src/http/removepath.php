<?php

require_once("common.php");

function main()
{
	$pathID = get("id");
	doHttpPath($pathID);
	
	$check = function($condition, $error) use($pathID) {
		if(!$condition) die(page(makeHeader("Web hosting - " . pathName($pathID), pathBreadcrumbs($pathID), crumbs("Remove site", "removepath.php?id=$pathID")) . removePathForm($pathID, $error, $_POST)));
	};
	
	$keepsubs = post("keepsubs") !== null;
	
	$aliases = array_unique(aliassesPointToPath($pathID, !$keepsubs));
	if(count($aliases) > 0) {
		$error = "The requested site(s) cannot be removed because of the following aliases:";
		$error .= "<ul>";
		foreach($aliases as $alias) {
			$name = pathName($alias);
			$target = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$alias), "mirrorTargetPathID");
			$targetName = pathName($target);
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
	removePath($pathID, $keepsubs);
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
	
	header("HTTP/1.1 303 See Other");
	if($parentPathID === null) {
		header("Location: {$GLOBALS["root"]}http/domain.php?id=$domainID");
	} else {
		header("Location: {$GLOBALS["root"]}http/path.php?id=$parentPathID");
	}
}

main();

?>