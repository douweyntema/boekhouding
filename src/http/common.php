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

function domainBreadcrumbs($domainID)
{
	return breadcrumbs(domainBreadcrumbsList($domainID));
}

function domainBreadcrumbsList($domainID)
{
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
	$crumbs = array();
	$crumbs[] = array("name"=>"Web hosting", "url"=>"{$GLOBALS["root"]}http/");
	$postfix = "";
	while(count($parts) > 0) {
		$part = array_shift($parts);
		if(count($parts) == 0) {
			$crumbs[] = array("name"=>$part["name"] . $postfix, "url"=>"{$GLOBALS["root"]}http/domain.php?id={$part["id"]}");
		} else if($GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$part["id"], "parentPathID"=>null), "pathID") !== null) {
			$crumbs[] = array("name"=>$part["name"] . $postfix, "url"=>"{$GLOBALS["root"]}http/domain.php?id={$part["id"]}");
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
			$parts[] = array("id"=>$nextPathID, "name"=>$path["name"], "used"=>($path["type"] != "NONE" || $path["userDatabaseID"] !== null));
			$nextPathID = $path["parentPathID"];
		}
	}
	
	$parts = array_reverse($parts);
	$crumbs = domainBreadcrumbsList($domainID);
	while(count($parts) > 0) {
		$part = array_shift($parts);
		if(!$part["used"] && count($parts) > 0) {
			$parts[0]["name"] = $part["name"] . "/" . $parts[0]["name"];
		} else {
			$crumbs[] = array("name"=>$part["name"], "url"=>"{$GLOBALS["root"]}http/path.php?id={$part["id"]}");
		}
	}
	return breadcrumbs($crumbs);
}

function domainsList()
{
	$output = "";
	
	$customerIDEscaped = $GLOBALS["database"]->addSlashes(customerID());
	$ownDomains = $GLOBALS["database"]->query("SELECT domainID, parentDomainID, name FROM httpDomain AS child WHERE customerID='$customerIDEscaped' AND (parentDomainID IS NULL OR customerID <> (SELECT customerID FROM httpDomain AS parent WHERE parent.domainID = child.parentDomainID))")->fetchList();
	
	$domains = array();
	foreach($ownDomains as $ownDomain) {
		$domainName = subDomainName($ownDomain["name"], $ownDomain["parentDomainID"]);
		$domains[$domainName] = array("domainID"=>$ownDomain["domainID"], "name"=>$domainName);
	}
	ksort($domains);
	
	if(count($domains) == 0) {
		return "";
	}
	$output .= <<<HTML
<div class="list sortable">
<table>
<thead>
<tr><th>Domain</th></tr>
</thead>
<tbody>

HTML;
	foreach($domains as $domain) {
		$domainNameHtml = htmlentities($domain["name"]);
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}http/domain.php?id={$domain["domainID"]}\">$domainNameHtml</a></td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function domainSummary($domainID)
{
	$addressTree = domainTree($domainID);
	$addressList = flattenDomainTree($addressTree);
	
	$output  = "<div class=\"list tree\">\n";
	$output .= "<table>\n";
	$output .= "<thead>\n";
	$output .= "<tr><th>Address</th><th>Function</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($addressList as $address) {
		$functionHtml = htmlentities(functionDescription($address));
		$addressHtml = htmlentities($address["name"]);
		$parentHtml = ($address["parentID"] === null ? "" : "class=\"child-of-{$address["parentID"]}\"");
		if(isset($address["domainID"])) {
			$output .= "<tr id=\"{$address["id"]}\" $parentHtml><td><a href=\"domain.php?id={$address["domainID"]}\">$addressHtml</a></td><td>$functionHtml</td></tr>\n";
		} else {
			$output .= "<tr id=\"{$address["id"]}\" $parentHtml><td><a href=\"path.php?id={$address["pathID"]}\">$addressHtml</a></td><td>$functionHtml</td></tr>\n";
		}
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function pathSummary($pathID)
{
	$addressTree = pathTree($pathID);
	$addressList = flattenPathTree($addressTree);
	
	$output  = "<div class=\"list tree\">\n";
	$output .= "<table>\n";
	$output .= "<thead>\n";
	$output .= "<tr><th>Address</th><th>Function</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($addressList as $address) {
		$functionHtml = htmlentities(functionDescription($address));
		$addressHtml = htmlentities($address["name"]);
		$parentHtml = ($address["parentID"] === null ? "" : "class=\"child-of-{$address["parentID"]}\"");
		if(isset($address["domainID"])) {
			$output .= "<tr id=\"{$address["id"]}\" $parentHtml><td><a href=\"domain.php?id={$address["domainID"]}\">$addressHtml</a></td><td>$functionHtml</td></tr>\n";
		} else {
			$output .= "<tr id=\"{$address["id"]}\" $parentHtml><td><a href=\"path.php?id={$address["pathID"]}\">$addressHtml</a></td><td>$functionHtml</td></tr>\n";
		}
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function addSubdomainForm($domainID, $error = "", $name = null)
{
	$parentName = domainName($domainID);
	$parentNameHtml = htmlentities($parentName);
	
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = "readonly=\"readonly\"";
		$stub = false;
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
		$stub = false;
	} else if($error == "STUB") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
		$stub = true;
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
		$stub = false;
	}
	
	if($stub) {
		$operationsHtml = "";
	} else {
		$usersHtml = "<select name=\"documentOwner\">";
		foreach($GLOBALS["database"]->query("SELECT DISTINCT adminUser.userID AS userID, username FROM adminUser INNER JOIN adminUserRight ON adminUser.userID = adminUserRight.userID LEFT JOIN adminComponent ON adminUserRight.componentID = adminComponent.componentID WHERE (adminComponent.name = 'http' OR adminComponent.name IS NULL) AND adminUser.customerID = '" . $GLOBALS["database"]->addSlashes(customerID()) . "' ORDER BY username ASC")->fetchList() as $user) {
			$usernameHtml = htmlentities($user["username"]);
			$usersHtml .= "<option value=\"{$user["userID"]}\">$usernameHtml</option>";
		}
		$usersHtml .= "</select>";
		
		$paths = array();
		foreach($GLOBALS["database"]->query("SELECT pathID FROM httpPath INNER JOIN httpDomain ON httpPath.domainID = httpDomain.domainID WHERE httpDomain.customerID = '" . $GLOBALS["database"]->addSlashes(customerID()) . "' AND httpPath.type != 'MIRROR'")->fetchList() as $path) {
			$paths[$path["pathID"]] = pathName($path["pathID"]);
		}
		asort($paths);
		$pathsHtml = "<select name=\"mirrorTarget\">";
		foreach($paths as $id=>$address) {
			$addressHtml = htmlentities($address);
			$pathsHtml .= "<option value=\"$id\">$addressHtml</option>";
		}
		$pathsHtml .= "</select>";
		
		$operationsHtml = <<<HTML
<div class="operation">
<h3>Hosted website</h3>
<table>
<tr><th>Document root:</th><td>/home/</td><td>$usersHtml</td><td>/www/</td><td class="stretch"><input type="text" name="documentRoot" /></td><td>/</td></tr>
<tr class="submit"><td colspan="6"><input type="submit" name="type" value="Use Hosted Website" /></td></tr>
</table>
</div>

<div class="operation">
<h3>Redirect</h3>
A redirect to an external site.
<table>
<tr><th>Redirect target:</th><td><input type="text" name="redirectTarget" /></td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Use Redirect" /></td></tr>
</table>
</div>

<div class="operation">
<h3>Alias</h3>
Shows the same content as one of your other sites, effectively making the same content available under an alternative name.
<table>
<tr><th>Aliased website:</th><td>$pathsHtml</td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Use Alias" /></td></tr>
</table>
</div>

HTML;
	}
	
	return <<<HTML
<div class="operation">
<h2>Add subdomain</h2>
$messageHtml
<form action="addsubdomain.php" method="post">
$confirmHtml
<table>
<tr><th>Subdomain name:</th><td class="stretch"><input type="text" name="name" /></td><td>.$parentNameHtml</td></tr>
</table>

$operationsHtml

</form>
</div>

HTML;
}

function flattenDomainTree($tree, $parentID = null)
{
	$id = "domain-" . $tree["domainID"];
	$output = array();
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
	$path = $GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), array("pathID", "type", "hostedPath", "svnPath", "redirectTarget", "mirrorTargetPathID", "userDatabaseID"));
	if($path === null) {
		return null;
	}
	$output = array();
	$output["pathID"] = $path["pathID"];
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
		if($subPath["type"] == "NONE" && $subPath["userDatabaseID"] === null) {
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
		return "Mirror of {$address["target"]}";
	} else if($address["type"] == "NONE" && $address["userDatabaseID"] !== null) {
		return "Secured subdirectory";
	} else {
		return "";
	}
}

?>