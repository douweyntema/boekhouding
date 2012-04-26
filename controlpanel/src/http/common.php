<?php

require_once(dirname(__FILE__) . "/../common.php");

function doHttp()
{
	useComponent("http");
	$GLOBALS["menuComponent"] = "http";
}

function doHttpDomain($domainID)
{
	doHttp();
	useCustomer($GLOBALS["database"]->stdGetTry("httpDomain", array("domainID"=>$domainID), "customerID", false));
}

function doHttpPath($pathID)
{
	doHttpDomain($GLOBALS["database"]->stdGetTry("httpPath", array("pathID"=>$pathID), "domainID", null));
}

function crumb($name, $filename)
{
	return array("name"=>$name, "url"=>"{$GLOBALS["root"]}http/$filename");
}

function crumbs($name, $filename)
{
	return array(crumb($name, $filename));
}

function httpBreadcrumbs()
{
	return crumbs("Web hosting", "");
}

function domainBreadcrumbs($domainID)
{
	$crumbs = httpBreadcrumbs();
	
	$parts = array();
	$nextDomainID = $domainID;
	while(true) {
		$domain = $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$nextDomainID), array("name", "parentDomainID", "customerID"));
		if($domain["parentDomainID"] === null) {
			$parts[] = array("id"=>$nextDomainID, "name"=>$domain["name"]);
			break;
		} else if($domain["customerID"] != customerID()) {
			$parts[] = array("id"=>$nextDomainID, "name"=>subDomainName($domain["name"], $domain["parentDomainID"]));
			break;
		} else {
			$parts[] = array("id"=>$nextDomainID, "name"=>$domain["name"]);
			$nextDomainID = $domain["parentDomainID"];
		}
	}
	$parts = array_reverse($parts);
	
	$postfix = "";
	while(count($parts) > 0) {
		$part = array_shift($parts);
		if(count($parts) == 0 || $GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$part["id"], "parentPathID"=>null), "pathID") !== null) {
			$crumbs[] = crumb("{$part["name"]}$postfix", "domain.php?id={$part["id"]}");
		}
		$postfix = "." . $part["name"] . $postfix;
	}
	
	return $crumbs;
}

function pathBreadcrumbs($pathID)
{
	$parts = array();
	$nextPathID = $pathID;
	$domainID = null;
	while(true) {
		$path = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$nextPathID), array("name", "parentPathID", "domainID", "type", "userDatabaseID"));
		if($path["parentPathID"] === null) {
			$domainID = $path["domainID"];
			break;
		} else {
			$parts[] = array("id"=>$nextPathID, "name"=>$path["name"], "used"=>$path["type"] != "NONE");
			$nextPathID = $path["parentPathID"];
		}
	}
	$parts = array_reverse($parts);
	$crumbs = domainBreadcrumbs($domainID);
	
	while(count($parts) > 0) {
		$part = array_shift($parts);
		if(!$part["used"] && count($parts) > 0) {
			$parts[0]["name"] = $part["name"] . "/" . $parts[0]["name"];
		} else {
			$crumbs[] = crumb($part["name"], "path.php?id={$part["id"]}");
		}
	}
	
	return $crumbs;
}

function domainsList()
{
	$customerID = customerID();
	$ownDomains = $GLOBALS["database"]->query("SELECT domainID, parentDomainID, name FROM httpDomain AS child WHERE customerID='$customerID' AND (parentDomainID IS NULL OR (SELECT customerID FROM httpDomain AS parent WHERE parent.domainID = child.parentDomainID) IS NULL OR customerID <> (SELECT customerID FROM httpDomain AS parent WHERE parent.domainID = child.parentDomainID))")->fetchList();
	
	$domains = array();
	foreach($ownDomains as $ownDomain) {
		$domainName = subDomainName($ownDomain["name"], $ownDomain["parentDomainID"]);
		$domains[$domainName] = array("domainID"=>$ownDomain["domainID"], "name"=>$domainName);
	}
	ksort($domains);
	
	$rows = array();
	foreach($domains as $domain) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["root"]}http/domain.php?id={$domain["domainID"]}", "text"=>$domain["name"])
		);
	}
	return listTable(array("Domain"), $rows, "list sortable");
}

function domainSummary($domainID)
{
	$addressTree = domainTree($domainID);
	$addressList = flattenDomainTree($addressTree);
	
	$rows = array();
	foreach($addressList as $address) {
		$rows[] = array("id"=>$address["id"], "class"=>($address["parentID"] === null ? null : "child-of-{$address["parentID"]}"), "cells"=>array(
			array("url"=>(isset($address["domainID"]) ? (isset($address["customerID"]) ? null : "domain.php?id={$address["domainID"]}") : ("path.php?id={$address["pathID"]}")), "text"=>$address["name"]),
			functionDescription($address)
		));
	}
	return listTable(array("Address", "Function"), $rows, "list tree");
}

function pathSummary($pathID)
{
	$addressTree = pathTree($pathID);
	$addressList = flattenPathTree($addressTree);
	
	$rows = array();
	foreach($addressList as $address) {
		$rows[] = array("id"=>$address["id"], "class"=>($address["parentID"] === null ? null : "child-of-{$address["parentID"]}"), "cells"=>array(
			array("url"=>(isset($address["domainID"]) ? "domain.php?id={$address["domainID"]}" : ("path.php?id={$address["pathID"]}")), "text"=>$address["name"]),
			functionDescription($address)
		));
	}
	return listTable(array("Address", "Function"), $rows, "list tree");
}

function singlePathSummary($pathID)
{
	$address = pathTree($pathID);
	
	$rows = array(array(
		array("url"=>(isset($address["domainID"]) ? "domain.php?id={$address["domainID"]}" : ("path.php?id={$address["pathID"]}")), "text"=>$address["name"]),
		functionDescription($address)
	));
	return listTable(array("Address", "Function"), $rows, "list tree");
}

function pathFunctionForm($pathID)
{
	$users = array();
	foreach($GLOBALS["database"]->stdList("adminUser", array("customerID"=>customerID()), array("userID", "username")) as $user) {
		$users[] = array("value"=>$user["userID"], "label"=>$user["username"]);
	}
	
	$paths = array();
	foreach($GLOBALS["database"]->query("SELECT pathID FROM httpPath INNER JOIN httpDomain ON httpPath.domainID = httpDomain.domainID WHERE httpDomain.customerID = '" . $GLOBALS["database"]->addSlashes(customerID()) . "' AND httpPath.type != 'MIRROR'" . ($pathID === null ? "" : " AND httpPath.pathID <> '" . $GLOBALS["database"]->addSlashes($pathID) . "'"))->fetchList() as $path) {
		$paths[] = array("value"=>$path["pathID"], "label"=>pathName($path["pathID"]));
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

function addDomainForm($error = "", $values = null)
{
	$domains = array();
	foreach($GLOBALS["database"]->stdList("httpDomain", array("customerID"=>null), array("domainID", "name")) as $domain) {
		$domains[] = array("value"=>$domain["domainID"], "label"=>$domain["name"]);
	}
	
	if($values === null) {
		$values = array();
	}
	if(!isset($values["documentOwner"])) {
		$values["documentOwner"] = userID();
	}
	
	return operationForm("adddomain.php", $error, "Add domain", "Add",
		array(
			array("title"=>"Domain name", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"name", "fill"=>true),
				array("type"=>"dropdown", "name"=>"rootDomainID", "options"=>$domains)
			)),
			pathFunctionForm(null)
		),
		$values);
}

function addSubdomainForm($domainID, $error = "", $values = null)
{
	if($values === null) {
		$values = array();
	}
	if(!isset($values["documentOwner"])) {
		$values["documentOwner"] = userID();
	}
	
	return operationForm("addsubdomain.php?id=$domainID", $error, "Add subdomain", "Add",
		array(
			array("title"=>"Subdomain name", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"name", "fill"=>true),
				array("type"=>"html", "html"=>("." . domainName($domainID)))
			)),
			pathFunctionForm(null)
		),
		$values);
}

function addPathForm($pathID, $error = "", $values = null)
{
	if($values === null) {
		$values = array();
	}
	if(!isset($values["documentOwner"])) {
		$values["documentOwner"] = userID();
	}
	
	return operationForm("addpath.php?id=$pathID", $error, "Add subdirectory", "Add",
		array(
			array("title"=>"Directory name", "type"=>"colspan", "columns"=>array(
				array("type"=>"html", "html"=>(pathName($pathID) . "/")),
				array("type"=>"text", "name"=>"name", "fill"=>true)
			)),
			pathFunctionForm(null)
		),
		$values);
}

function editPathForm($pathID, $error = "", $values = null)
{
	$pathNameHtml = htmlentities(pathName($pathID));
	$path = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), array("type", "hostedUserID", "hostedPath", "redirectTarget", "mirrorTargetPathID"));
	
	if($error == "STUB") {
		if($path["type"] == "HOSTED") {
			$function = "Hosted site";
			$dataTitle = "Document root";
			$username = $GLOBALS["database"]->stdGet("adminUser", array("userID"=>$path["hostedUserID"]), "username");
			$dataContent = htmlentities("/home/$username/www/{$path["hostedPath"]}/");
		} else if($path["type"] == "REDIRECT") {
			$function = "Redirect";
			$dataTitle = "Target";
			$urlHtml = htmlentities($path["redirectTarget"]);
			$dataContent = "<a href=\"$urlHtml\">$urlHtml</a>";
		} else if($path["type"] == "MIRROR") {
			$function = "Alias";
			$dataTitle = "Target";
			$urlHtml = htmlentities("http://" . pathName($path["mirrorTargetPathID"]) . "/");
			$dataContent = "<a href=\"$urlHtml\">$urlHtml</a>";
		} else {
			$function = "Unknown";
			$dataTitle = "Details";
			$dataContent = functionDescription($pathID);
		}
		$urlHtml = htmlentities("http://" . pathName($pathID) . "/");
		return operationForm("editpath.php?id=$pathID", $error, "Site function", "Edit", array(
			array("title"=>"Function", "type"=>"html", "html"=>$function),
			array("title"=>"Url", "type"=>"html", "html"=>"<a href=\"$urlHtml\">$urlHtml</a>"),
			array("title"=>$dataTitle, "type"=>"html", "html"=>$dataContent)
		),
		array());
	}
	
	if($values === null || (!isset($values["hosted"]) && !isset($values["redirect"]) && !isset($values["mirror"]))) {
		if($path["type"] == "HOSTED") {
			$values = array("hosted"=>"1", "documentOwner"=>$path["hostedUserID"], "documentRoot"=>$path["hostedPath"]);
		} else if($path["type"] == "REDIRECT") {
			$values = array("redirect"=>"1", "redirectTarget"=>$path["redirectTarget"]);
		} else if($path["type"] == "MIRROR") {
			$values = array("mirror"=>"1", "mirrorTarget"=>$path["mirrorTargetPathID"]);
		}
	}
	
	return operationForm("editpath.php?id=$pathID", $error, "Edit site $pathNameHtml", "Edit",
		array(
			pathFunctionForm($pathID)
		),
		$values);
}

function removeDomainForm($domainID, $error = "", $values = null)
{
	$messages = array();
	$messages["confirmdelete"] = "The following sites will be removed:";
	if($error === null) {
		if(isset($values["keepsubs"]) || isRootDomain($domainID)) {
			$messages["custom"] = pathSummary(domainPath($domainID));
		} else {
			$messages["custom"] = domainSummary($domainID);
		}
	}
	
	if(isRootDomain($domainID)) {
		return operationForm("removedomain.php?id=$domainID", $error, "Remove site", "Remove Site", array(), $values, $messages);
	} else {
		return operationForm("removedomain.php?id=$domainID", $error, "Remove site", "Remove Site", array(
			array("type"=>"checkbox", "name"=>"keepsubs", "label"=>"Keep subdomains")
			),
			$values, $messages);
	}
}

function removePathForm($pathID, $error = "", $values = null)
{
	$messages = array();
	$messages["confirmdelete"] = "The following sites will be removed:";
	if($error === null) {
		if(isset($values["keepsubs"])) {
			$messages["custom"] = singlePathSummary($pathID);
		} else {
			$messages["custom"] = pathSummary($pathID);
		}
	}
	
	return operationForm("removepath.php?id=$pathID", $error, "Remove site", "Remove Site", array(
		array("type"=>"checkbox", "name"=>"keepsubs", "label"=>"Keep subdomains")
		),
		$values, $messages);
}

function removeDomain($domainID, $keepsubs)
{
	unsetAliases($domainID, !$keepsubs);
	$pathID = $GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), "pathID");
	if($pathID !== null) {
		removePath($pathID, false);
	}
	$subdomains = $GLOBALS["database"]->stdList("httpDomain", array("parentDomainID"=>$domainID), "domainID");
	if($keepsubs) {
		if(count($subdomains) == 0) {
			$GLOBALS["database"]->stdDel("httpDomain", array("domainID"=>$domainID));
		}
	} else {
		foreach($subdomains as $subdomain) {
			removeDomain($subdomain, false);
		}
		$GLOBALS["database"]->stdDel("httpDomain", array("domainID"=>$domainID));
	}
}

function removePath($pathID, $keepsubs)
{
	$subpaths = $GLOBALS["database"]->stdList("httpPath", array("parentPathID"=>$pathID), "pathID");
	$GLOBALS["database"]->stdDel("httpPathUser", array("pathID"=>$pathID));
	$GLOBALS["database"]->stdDel("httpPathGroup", array("pathID"=>$pathID));
	if($keepsubs) {
		if(count($subpaths) == 0) {
			$GLOBALS["database"]->stdDel("httpPath", array("pathID"=>$pathID));
		}
	} else {
		foreach($subpaths as $subpath) {
			removePath($subpath, false);
		}
		$GLOBALS["database"]->stdDel("httpPath", array("pathID"=>$pathID));
	}
}

function unsetAliases($domainID, $recursive)
{
	$aliasesList = array();
	$pathIDs = toBeRemovedPathsDomain($domainID, $recursive);
	foreach($pathIDs as $pathID) {
		$GLOBALS["database"]->stdSet("httpPath", array("pathID"=>$pathID), array("mirrorTargetPathID"=>null));
	}
	return $aliasesList;
}

function aliassesPointToDomain($domainID, $recursive)
{
	$aliasesList = array();
	$pathIDs = toBeRemovedPathsDomain($domainID, $recursive);
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

function aliassesPointToPath($pathID, $recursive)
{
	$aliasesList = array();
	$pathIDs = toBeRemovedPathsPath($pathID, $recursive);
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


function toBeRemovedPathsDomain($domainID, $recursive)
{
	$list = array();
	$pathID = $GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), "pathID");
	if($pathID !== null) {
		$list = toBeRemovedPathsPath($pathID, true);
	}
	$subdomains = $GLOBALS["database"]->stdList("httpDomain", array("parentDomainID"=>$domainID), "domainID");
	if($recursive) {
		foreach($subdomains as $subdomain) {
			$list = array_merge($list, toBeRemovedPathsDomain($subdomain, $recursive));
		}
	}
	return $list;
}

function toBeRemovedPathsPath($pathID, $recursive)
{
	$list = array($pathID);
	$subpaths = $GLOBALS["database"]->stdList("httpPath", array("parentPathID"=>$pathID), "pathID");
	if($recursive) {
		foreach($subpaths as $subpath) {
			$list = array_merge($list, toBeRemovedPathsPath($subpath, $recursive));
		}
	}
	return $list;
}



function validSubdomain($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	if(preg_match('/^[-a-zA-Z0-9_]*$/', $name) != 1) {
		return false;
	}
	return true;
}

function validDirectory($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	if(preg_match('/^[-a-zA-Z0-9_.]*$/', $name) != 1) {
		return false;
	}
	return true;
}

function validDocumentRoot($root)
{
	if(strlen($root) > 255) {
		return false;
	}
	if(substr($root, 0, 1) == '/') {
		$root = substr($root, 1);
	}
	if(substr($root, -1) == '/') {
		$root = substr($root, 0, -1);
	}
	$parts = explode("/", $root);
	foreach($parts as $part) {
		if(preg_match('/^[a-zA-Z0-9_\-.]+$/', $part) != 1) {
			return false;
		}
		if($part == "." || $part == "..") {
			return false;
		}
	}
	return true;
}

function isRootDomain($domainID)
{
	$parentDomainID = $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$domainID), "parentDomainID");
	if($parentDomainID === null) {
		return true;
	}
	return $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$parentDomainID), "customerID") === null;
}

function flattenDomainTree($tree, $parentID = null)
{
	$id = "domain-" . $tree["domainID"];
	$output = array();
	
	if(isset($tree["customerID"])) {
		$output[] = array_merge($tree, array("id"=>$id, "parentID"=>$parentID, "type"=>"OTHERUSER"));
		return $output;
	}
	$output[] = array_merge($tree, array("id"=>$id, "parentID"=>$parentID));
	foreach($tree["subdomains"] as $domain) {
		$output = array_merge($output, flattenDomainTree($domain, $id));
	}
	foreach($tree["paths"] as $path) {
		$output = array_merge($output, flattenPathTree($path, $id));
	}
	return $output;
}

function flattenPathTree($tree, $parentID = null)
{
	$id = "path-" . $tree["pathID"];
	$output = array();
	$output[] = array_merge($tree, array("id"=>$id, "parentID"=>$parentID));
	foreach($tree["paths"] as $path) {
		$output = array_merge($output, flattenPathTree($path, $id));
	}
	return $output;
}

function domainTree($domainID)
{
	$output = array();
	$output["domainID"] = $domainID;
	$output["name"] = domainName($domainID);
	$output["subdomains"] = subdomainTrees($domainID, $output["name"], $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$domainID), "customerID"));
	
	$path = domainPathTree($domainID, $output["name"]);
	return array_merge($output, $path);
}

function subdomainTrees($domainID, $name, $customerID)
{
	$output = array();
	foreach($GLOBALS["database"]->stdList("httpDomain", array("parentDomainID"=>$domainID), array("domainID", "name", "customerID")) as $subdomain) {
		$subID = $subdomain["domainID"];
		$subName = $subdomain["name"] . "." . $name;
		if($subdomain["customerID"] != $customerID) {
			$domain = array();
			$domain["domainID"] = $subID;
			$domain["name"] = $subName;
			$domain["customerID"] = $subdomain["customerID"];
			$output[] = $domain;
		} else {
			$path = domainPathTree($subID, $subName);
			$subdomains = subdomainTrees($subID, $subName, $customerID);
			if($path === null) {
				$output = array_merge($output, $subdomains);
			} else {
				$domain = array();
				$domain["domainID"] = $subID;
				$domain["name"] = $subName;
				$domain["subdomains"] = $subdomains;
				$output[] = array_merge($domain, $path);
			}
		}
	}
	return $output;
}

function domainPathTree($domainID, $name)
{
	$path = $GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), array("pathID", "type", "hostedUserID", "hostedPath", "svnPath", "redirectTarget", "mirrorTargetPathID", "userDatabaseID"));
	if($path === null) {
		return null;
	}
	$output = array();
	$output["pathID"] = $path["pathID"];
	$output["type"] = $path["type"];
	$output["userDatabaseID"] = $path["userDatabaseID"];
	if($path["type"] == "HOSTED") {
		$username = $GLOBALS["database"]->stdGet("adminUser", array("userID"=>$path["hostedUserID"]), "username");
		$output["target"] = "/home/$username/www/{$path["hostedPath"]}/";
	} else if($path["type"] == "SVN") {
		$output["target"] = $path["svnPath"];
	} else if($path["type"] == "REDIRECT") {
		$output["target"] = $path["redirectTarget"];
	} else if($path["type"] == "MIRROR") {
		$output["target"] = pathName($path["mirrorTargetPathID"]);
	}
	$output["paths"] = pathTrees($path["pathID"], $name);
	return $output;
}

function pathTree($pathID)
{
	$path = $GLOBALS["database"]->stdGetTry("httpPath", array("pathID"=>$pathID), array("pathID", "type", "hostedPath", "svnPath", "redirectTarget", "mirrorTargetPathID", "userDatabaseID"));
	if($path === null) {
		return null;
	}
	$name = pathName($pathID);
	$output = array();
	$output["pathID"] = $path["pathID"];
	$output["name"] = $name;
	$output["type"] = $path["type"];
	$output["userDatabaseID"] = $path["userDatabaseID"];
	if($path["type"] == "HOSTED") {
		$output["target"] = $path["hostedPath"];
	} else if($path["type"] == "SVN") {
		$output["target"] = $path["svnPath"];
	} else if($path["type"] == "REDIRECT") {
		$output["target"] = $path["redirectTarget"];
	} else if($path["type"] == "MIRROR") {
		$output["target"] = pathName($path["mirrorTargetPathID"]);
	}
	$output["paths"] = pathTrees($path["pathID"], $name);
	return $output;
}

function pathTrees($pathID, $name)
{
	$output = array();
	foreach($GLOBALS["database"]->stdList("httpPath", array("parentPathID"=>$pathID), array("pathID", "name", "type", "hostedPath", "svnPath", "redirectTarget", "mirrorTargetPathID", "userDatabaseID")) as $subPath) {
		$pathName = $name . "/" . $subPath["name"];
		$paths = pathTrees($subPath["pathID"], $pathName);
		if($subPath["type"] == "NONE") {
			$output = array_merge($output, $paths);
		} else {
			$path = array();
			$path["pathID"] = $subPath["pathID"];
			$path["name"] = $pathName;
			$path["type"] = $subPath["type"];
			$path["userDatabaseID"] = $subPath["userDatabaseID"];
			if($subPath["type"] == "HOSTED") {
				$path["target"] = $subPath["hostedPath"];
			} else if($subPath["type"] == "SVN") {
				$path["target"] = $subPath["svnPath"];
			} else if($subPath["type"] == "REDIRECT") {
				$path["target"] = $subPath["redirectTarget"];
			} else if($subPath["type"] == "MIRROR") {
				$path["target"] = pathName($subPath["mirrorTargetPathID"]);
			}
			$path["paths"] = $paths;
			$output[] = $path;
		}
	}
	return $output;
}

function isStubDomain($domainID)
{
	return $GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), "type", "NONE") == "NONE";
}

function domainPath($domainID)
{
	return $GLOBALS["database"]->stdGet("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), "pathID");
}

function domainName($domainID)
{
	$domain = $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$domainID), array("parentDomainID", "name"), null);
	if($domain === null) {
		return null;
	}
	if($domain["parentDomainID"] === null) {
		return $domain["name"];
	}
	return $domain["name"] . "." . domainName($domain["parentDomainID"]);
}

function subDomainName($domainName, $parentDomainID)
{
	if($parentDomainID === null) {
		return $domainName;
	}
	return $domainName . "." . domainName($parentDomainID);
}

function pathName($pathID)
{
	$path = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), array("name", "parentPathID", "domainID"));
	if($path["parentPathID"] == null) {
		return domainName($path["domainID"]);
	} else {
		return pathName($path["parentPathID"]) . "/" . $path["name"];
	}
}

function functionDescription($address)
{
	if($address["type"] == "HOSTED") {
		if($address["userDatabaseID"] !== null) {
			return "Secured hosted site: {$address["target"]}";
		} else {
			return "Hosted site: {$address["target"]}";
		}
	} else if($address["type"] == "SVN") {
		return "SVN repository: {$address["target"]}";
	} else if($address["type"] == "REDIRECT") {
		return "Redirect to {$address["target"]}";
	} else if($address["type"] == "MIRROR") {
		return "Alias for {$address["target"]}";
	} else if($address["type"] == "NONE" && $address["userDatabaseID"] !== null) {
		return "Secured subdirectory";
	} else if($address["type"] == "OTHERUSER") {
		$customerName = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$address["customerID"]), "name");
		return "Delegated to customer $customerName";
	} else {
		return "";
	}
}

?>