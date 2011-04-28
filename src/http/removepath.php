<?php

require_once("common.php");

function main()
{
	$pathID = get("id");
	doHttpPath($pathID);
	
	$content = "<h1>Web hosting - " . pathName($pathID) . "</h1>\n";
	
	$content .= pathBreadcrumbs($pathID, array(array("url"=>"{$GLOBALS["root"]}http/removepath.php?id=$pathID", "name"=>"Remove site")));
	
	$keepsubs = post("keepsubs") == "keep";
	
	$aliases = aliassesPointToPath($pathID, !$keepsubs);
	$aliases = array_unique($aliases);
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
		$error .= "<p>Please remove there aliases and press <em>Retry</em>.</p>";
		$content .= removePathForm($pathID, $error, $keepsubs);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= removePathForm($pathID, null, $keepsubs);
		die(page($content));
	}
	
	$parentPathID = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), "parentPathID");
	$domainID = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), "domainID");
	
	$GLOBALS["database"]->startTransaction();
	removePath($pathID, $keepsubs);
	// TODO: NONE-type parents ook opruimen
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