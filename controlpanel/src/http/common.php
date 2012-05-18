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
		$domain = $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$nextDomainID), array("name", "domainTldID", "parentDomainID", "customerID"));
		if($domain["parentDomainID"] === null) {
			$tld = $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$domain["domainTldID"]), "name");
			$parts[] = array("id"=>$nextDomainID, "name"=>$domain["name"] . "." . $tld);
			break;
		} else if($domain["customerID"] != customerID()) {
			$parts[] = array("id"=>$nextDomainID, "name"=>httpDomainName($domainID));
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
		$path = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$nextPathID), array("name", "parentPathID", "domainID", "type"));
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
		$domainName = httpDomainName($ownDomain["domainID"]);
		$domains[$domainName] = array("domainID"=>$ownDomain["domainID"], "name"=>$domainName);
	}
	ksort($domains);
	
	$rows = array();
	foreach($domains as $domain) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["root"]}http/domain.php?id={$domain["domainID"]}", "text"=>$domain["name"])
		);
	}
	return listTable(array("Domain"), $rows, null, false, "list sortable");
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
	return listTable(array("Address", "Function"), $rows, null, true, "list tree");
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
	return listTable(array("Address", "Function"), $rows, null, true, "list tree");
}

function singlePathSummary($pathID)
{
	$address = pathTree($pathID);
	
	$rows = array(array(
		array("url"=>(isset($address["domainID"]) ? "domain.php?id={$address["domainID"]}" : ("path.php?id={$address["pathID"]}")), "text"=>$address["name"]),
		functionDescription($address)
	));
	return listTable(array("Address", "Function"), $rows, null, true, "list tree");
}

function pathFunctionForm($pathID)
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

function addDomainForm($error = "", $values = null)
{
	$domains = array();
	foreach($GLOBALS["database"]->stdList("infrastructureDomainTld", array("active"=>1), array("domainTldID", "name")) as $domain) {
		$domains[] = array("value"=>$domain["domainTldID"], "label"=>$domain["name"]);
	}
	
	if($values === null) {
		$values = array();
	}
	if(!isset($values["documentOwner"])) {
		$values["documentOwner"] = userID();
	}
	if(!isset($values["redirectTarget"])) {
		$values["redirectTarget"] = "http://";
	}
	
	return operationForm("adddomain.php", $error, "Add domain", "Add",
		array(
			array("title"=>"Domain name", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"name", "fill"=>true),
				array("type"=>"html", "html"=>"."),
				array("type"=>"dropdown", "name"=>"domainTldID", "options"=>$domains)
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
	if(!isset($values["redirectTarget"])) {
		$values["redirectTarget"] = "http://";
	}
	
	return operationForm("addsubdomain.php?id=$domainID", $error, "Add subdomain", "Add",
		array(
			array("title"=>"Subdomain name", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"name", "fill"=>true),
				array("type"=>"html", "html"=>("." . httpDomainName($domainID)))
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
	if(!isset($values["redirectTarget"])) {
		$values["redirectTarget"] = "http://";
	}
	
	return operationForm("addpath.php?id=$pathID", $error, "Add subdirectory", "Add",
		array(
			array("title"=>"Directory name", "type"=>"colspan", "columns"=>array(
				array("type"=>"html", "html"=>(httpPathName($pathID) . "/")),
				array("type"=>"text", "name"=>"name", "fill"=>true)
			)),
			pathFunctionForm(null)
		),
		$values);
}

function editPathForm($pathID, $error = "", $values = null)
{
	$pathNameHtml = htmlentities(httpPathName($pathID));
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
			$urlHtml = htmlentities("http://" . httpPathName($path["mirrorTargetPathID"]) . "/");
			$dataContent = "<a href=\"$urlHtml\">$urlHtml</a>";
		} else {
			$function = "Unknown";
			$dataTitle = "Details";
			$dataContent = functionDescription($pathID);
		}
		$urlHtml = htmlentities("http://" . httpPathName($pathID) . "/");
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
	if(!isset($values["redirectTarget"])) {
		$values["redirectTarget"] = "http://";
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



function domainTree($domainID)
{
	$output = array();
	$output["domainID"] = $domainID;
	$output["name"] = httpDomainName($domainID);
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
	$path = $GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), array("pathID", "type", "hostedUserID", "hostedPath", "redirectTarget", "mirrorTargetPathID"));
	if($path === null) {
		return null;
	}
	$output = array();
	$output["pathID"] = $path["pathID"];
	$output["type"] = $path["type"];
	if($path["type"] == "HOSTED") {
		$username = $GLOBALS["database"]->stdGet("adminUser", array("userID"=>$path["hostedUserID"]), "username");
		$output["target"] = "/home/$username/www/{$path["hostedPath"]}/";
	} else if($path["type"] == "REDIRECT") {
		$output["target"] = $path["redirectTarget"];
	} else if($path["type"] == "MIRROR") {
		$output["target"] = httpPathName($path["mirrorTargetPathID"]);
	}
	$output["paths"] = pathTrees($path["pathID"], $name);
	return $output;
}

function pathTree($pathID)
{
	$path = $GLOBALS["database"]->stdGetTry("httpPath", array("pathID"=>$pathID), array("pathID", "type", "hostedPath", "redirectTarget", "mirrorTargetPathID"));
	if($path === null) {
		return null;
	}
	$name = httpPathName($pathID);
	$output = array();
	$output["pathID"] = $path["pathID"];
	$output["name"] = $name;
	$output["type"] = $path["type"];
	if($path["type"] == "HOSTED") {
		$output["target"] = $path["hostedPath"];
	} else if($path["type"] == "REDIRECT") {
		$output["target"] = $path["redirectTarget"];
	} else if($path["type"] == "MIRROR") {
		$output["target"] = httpPathName($path["mirrorTargetPathID"]);
	}
	$output["paths"] = pathTrees($path["pathID"], $name);
	return $output;
}

function pathTrees($pathID, $name)
{
	$output = array();
	foreach($GLOBALS["database"]->stdList("httpPath", array("parentPathID"=>$pathID), array("pathID", "name", "type", "hostedPath", "redirectTarget", "mirrorTargetPathID")) as $subPath) {
		$pathName = $name . "/" . $subPath["name"];
		$paths = pathTrees($subPath["pathID"], $pathName);
		if($subPath["type"] == "NONE") {
			$output = array_merge($output, $paths);
		} else {
			$path = array();
			$path["pathID"] = $subPath["pathID"];
			$path["name"] = $pathName;
			$path["type"] = $subPath["type"];
			if($subPath["type"] == "HOSTED") {
				$path["target"] = $subPath["hostedPath"];
			} else if($subPath["type"] == "REDIRECT") {
				$path["target"] = $subPath["redirectTarget"];
			} else if($subPath["type"] == "MIRROR") {
				$path["target"] = httpPathName($subPath["mirrorTargetPathID"]);
			}
			$path["paths"] = $paths;
			$output[] = $path;
		}
	}
	return $output;
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

function isRootDomain($domainID)
{
	return $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$domainID), "parentDomainID") === null;
}

function isStubDomain($domainID)
{
	return $GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), "type", "NONE") == "NONE";
}

function domainPath($domainID)
{
	return $GLOBALS["database"]->stdGet("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), "pathID");
}

function functionDescription($address)
{
	if($address["type"] == "HOSTED") {
		return "Hosted site: {$address["target"]}";
	} else if($address["type"] == "REDIRECT") {
		return "Redirect to {$address["target"]}";
	} else if($address["type"] == "MIRROR") {
		return "Alias for {$address["target"]}";
	} else if($address["type"] == "OTHERUSER") {
		$customerName = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$address["customerID"]), "name");
		return "Delegated to customer $customerName";
	} else {
		return "";
	}
}

?>