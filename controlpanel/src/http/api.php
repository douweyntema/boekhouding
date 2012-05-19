<?php

$httpTitle = "Web hosting";
$httpDescription = "Web hosting";
$httpTarget = "customer";

function updateHttp($customerID)
{
	$fileSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "fileSystemID");
	$GLOBALS["database"]->stdIncrement("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "httpVersion", 1000000000);
	
	$hosts = $GLOBALS["database"]->stdList("infrastructureWebServer", array("fileSystemID"=>$fileSystemID), "hostID");
	updateHosts($hosts, "update-treva-apache");
}

function httpDomainName($domainID)
{
	$domain = $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$domainID), array("parentDomainID", "name", "domainTldID"));
	if($domain["parentDomainID"] === null) {
		$tld = $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$domain["domainTldID"]), "name");
		return $domain["name"] . "." . $tld;
	}
	return $domain["name"] . "." . httpDomainName($domain["parentDomainID"]);
}

function httpPathName($pathID)
{
	$path = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), array("name", "parentPathID", "domainID"));
	if($path["parentPathID"] == null) {
		return httpDomainName($path["domainID"]);
	} else {
		return httpPathName($path["parentPathID"]) . "/" . $path["name"];
	}
}

function httpRemoveDomain($domainID, $keepsubs)
{
	foreach(httpDescendantPathsDomain($domainID, !$keepsubs) as $pathID) {
		$GLOBALS["database"]->stdSet("httpPath", array("pathID"=>$pathID, "type"=>"MIRROR"), array("type"=>"NONE", "mirrorTargetPathID"=>null));
	}
	
	$pathID = $GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), "pathID");
	if($pathID !== null) {
		httpDoRemovePath($pathID, false);
	}
	$subdomains = $GLOBALS["database"]->stdList("httpDomain", array("parentDomainID"=>$domainID), "domainID");
	if($keepsubs) {
		if(count($subdomains) == 0) {
			$GLOBALS["database"]->stdDel("httpDomain", array("domainID"=>$domainID));
		}
	} else {
		foreach($subdomains as $subdomain) {
			httpRemoveDomain($subdomain, false);
		}
		$GLOBALS["database"]->stdDel("httpDomain", array("domainID"=>$domainID));
	}
}

function httpRemovePath($pathID, $keepsubs)
{
	foreach(httpDescendantPaths($pathID, !$keepsubs) as $childPathID) {
		$GLOBALS["database"]->stdSet("httpPath", array("pathID"=>$childPathID, "type"=>"MIRROR"), array("type"=>"NONE", "mirrorTargetPathID"=>null));
	}
	
	httpDoRemovePath($pathID, $keepsubs);
}

function httpDoRemovePath($pathID, $keepsubs)
{
	$subpaths = $GLOBALS["database"]->stdList("httpPath", array("parentPathID"=>$pathID), "pathID");
	if($keepsubs) {
		if(count($subpaths) == 0) {
			$GLOBALS["database"]->stdDel("httpPath", array("pathID"=>$pathID));
		} else {
			$GLOBALS["database"]->stdSet("httpPath", array("pathID"=>$pathID), array("type"=>"NONE"));
		}
	} else {
		foreach($subpaths as $subpath) {
			httpDoRemovePath($subpath, false);
		}
		$GLOBALS["database"]->stdDel("httpPath", array("pathID"=>$pathID));
	}
}

function httpAliasesToDomain($domainID, $recursive)
{
	$aliasesList = array();
	$pathIDs = httpDescendantPathsDomain($domainID, $recursive);
	foreach($pathIDs as $pathID) {
		$aliases = $GLOBALS["database"]->stdList("httpPath", array("mirrorTargetPathID"=>$pathID), "pathID");
		foreach($aliases as $alias) {
			if(!in_array($alias, $pathIDs)) {
				$aliasesList[] = $alias;
			}
		}
	}
	return $aliasesList;
}

function httpAliasesToPath($pathID, $recursive)
{
	$aliasesList = array();
	$pathIDs = httpDescendantPaths($pathID, $recursive);
	foreach($pathIDs as $pathID) {
		$aliases = $GLOBALS["database"]->stdList("httpPath", array("mirrorTargetPathID"=>$pathID), "pathID");
		foreach($aliases as $alias) {
			if(!in_array($alias, $pathIDs)) {
				$aliasesList[] = $alias;
			}
		}
	}
	return $aliasesList;
}

function httpDescendantPathsDomain($domainID, $recursive)
{
	$list = array();
	$pathID = $GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), "pathID");
	if($pathID !== null) {
		$list = httpDescendantPaths($pathID, true);
	}
	if($recursive) {
		foreach($GLOBALS["database"]->stdList("httpDomain", array("parentDomainID"=>$domainID), "domainID") as $subdomain) {
			$list = array_merge($list, httpDescendantPathsDomain($subdomain, $recursive));
		}
	}
	return $list;
}

function httpDescendantPaths($pathID, $recursive)
{
	$list = array($pathID);
	if($recursive) {
		foreach($GLOBALS["database"]->stdList("httpPath", array("parentPathID"=>$pathID), "pathID") as $subpath) {
			$list = array_merge($list, httpDescendantPaths($subpath, $recursive));
		}
	}
	return $list;
}

function httpPathFunctionForm($pathID)
{
	$users = array();
	foreach($GLOBALS["database"]->stdList("adminUser", array("customerID"=>customerID()), array("userID", "username")) as $user) {
		$users[] = array("value"=>$user["userID"], "label"=>$user["username"]);
	}
	
	$paths = array();
	foreach($GLOBALS["database"]->query("SELECT pathID FROM httpPath INNER JOIN httpDomain ON httpPath.domainID = httpDomain.domainID WHERE httpDomain.customerID = '" . $GLOBALS["database"]->addSlashes(customerID()) . "' AND httpPath.type != 'MIRROR'" . ($pathID === null ? "" : " AND httpPath.pathID <> '" . $GLOBALS["database"]->addSlashes($pathID) . "'"))->fetchList() as $path) {
		$paths[] = array("value"=>$path["pathID"], "label"=>httpPathName($path["pathID"]));
	}
	asort($paths);
	
	return array("type"=>"typechooser", "options"=>array(
		array("title"=>"Hosted site", "submitcaption"=>"Use Hosted Site", "name"=>"hosted", "subform"=>array(
			array("title"=>"Document root", "type"=>"colspan", "columns"=>array(
				array("type"=>"html", "html"=>"/home/"),
				array("type"=>"dropdown", "name"=>"documentOwner", "options"=>$users),
				array("type"=>"html", "html"=>"/www/"),
				array("type"=>"text", "name"=>"documentRoot", "fill"=>true),
				array("type"=>"html", "html"=>"/")
			))
		)),
		array("title"=>"Redirect", "submitcaption"=>"Use Redirect", "name"=>"redirect", "summary"=>"A redirect to an external site.", "subform"=>array(
			array("title"=>"Redirect target", "type"=>"text", "name"=>"redirectTarget")
		)),
		array("title"=>"Alias", "submitcaption"=>"Use Alias", "name"=>"mirror", "summary"=>"An alternative address to reach one of your existing sites.", "subform"=>array(
			array("title"=>"Aliased website", "type"=>"dropdown", "name"=>"mirrorTarget", "options"=>$paths)
		))
	));
}

function httpPathFunctionStubForm($pathName, $type, $userID, $hostedPath, $redirectTarget, $mirrorTargetPathID, $description)
{
	if($type == "HOSTED") {
		$function = "Hosted site";
		$dataTitle = "Document root";
		$username = $GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "username");
		$dataContent = htmlentities("/home/$username/www/$hostedPath/");
	} else if($type == "REDIRECT") {
		$function = "Redirect";
		$dataTitle = "Target";
		$urlHtml = htmlentities($redirectTarget);
		$dataContent = "<a href=\"$urlHtml\">$urlHtml</a>";
	} else if($type == "MIRROR") {
		$function = "Alias";
		$dataTitle = "Target";
		$urlHtml = htmlentities("http://" . httpPathName($mirrorTargetPathID) . "/");
		$dataContent = "<a href=\"$urlHtml\">$urlHtml</a>";
	} else {
		$function = "Unknown";
		$dataTitle = "Details";
		$dataContent = $description;
	}
	$urlHtml = htmlentities("http://$pathName/");
	return array(
		array("title"=>"Function", "type"=>"html", "html"=>$function),
		array("title"=>"Url", "type"=>"html", "html"=>"<a href=\"$urlHtml\">$urlHtml</a>"),
		array("title"=>$dataTitle, "type"=>"html", "html"=>$dataContent)
	);
}

?>