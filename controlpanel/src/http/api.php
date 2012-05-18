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

?>