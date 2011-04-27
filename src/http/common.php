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

function updateHttp($customerID)
{
	$filesystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "filesystemID");
	$GLOBALS["database"]->stdIncrement("infrastructureFilesystem", array("filesystemID"=>$filesystemID), "httpVersion", 1000000000);
	
	$hosts = $GLOBALS["database"]->stdList("infrastructureWebServer", array("filesystemID"=>$filesystemID), "hostID");
	updateHosts($hosts, "update-treva-http");
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

function domainBreadcrumbs($domainID, $postfix = array())
{
	return breadcrumbs(array_merge(domainBreadcrumbsList($domainID), $postfix));
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

function pathBreadcrumbs($pathID, $postfix = array())
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
	$crumbs = domainBreadcrumbsList($domainID);
	while(count($parts) > 0) {
		$part = array_shift($parts);
		if(!$part["used"] && count($parts) > 0) {
			$parts[0]["name"] = $part["name"] . "/" . $parts[0]["name"];
		} else {
			$crumbs[] = array("name"=>$part["name"], "url"=>"{$GLOBALS["root"]}http/path.php?id={$part["id"]}");
		}
	}
	return breadcrumbs(array_merge($crumbs, $postfix));
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

function pathFunctionSubformHosted($confirm, $selected, $hostedUserID, $hostedPath)
{
	$readonlyHtml = ($confirm ? "readonly=\"readonly\"" : "");
	
	$usersHtml = "<input type=\"hidden\" name=\"documentOwner\" value=\"\">";
	$usersHtml = "<select name=\"documentOwner\" $readonlyHtml>";
	$usersHtmlDisabled = "";
	foreach($GLOBALS["database"]->query("SELECT DISTINCT adminUser.userID AS userID, username FROM adminUser INNER JOIN adminUserRight ON adminUser.userID = adminUserRight.userID LEFT JOIN adminComponent ON adminUserRight.componentID = adminComponent.componentID WHERE (adminComponent.name = 'http' OR adminComponent.name IS NULL) AND adminUser.customerID = '" . $GLOBALS["database"]->addSlashes(customerID()) . "' ORDER BY username ASC")->fetchList() as $user) {
		$usernameHtml = htmlentities($user["username"]);
		$selectedHtml = ($hostedUserID == $user["userID"]) ? "selected=\"selected\"" : "";
		$usersHtml .= "<option value=\"{$user["userID"]}\" $selectedHtml>$usernameHtml</option>";
		if($hostedUserID == $user["userID"]) {
			$size = strlen($usernameHtml);
			$usersHtmlDisabled = "<input type=\"hidden\" name=\"documentOwner\" value=\"{$user["userID"]}\">\n<input type=\"text\" name=\"documentOwnerText\" value=\"$usernameHtml\" readonly=\"readonly\" style=\"width: {$size}em\">";
		}
	}
	$usersHtml .= "</select>";
	if($confirm) {
		$usersHtml = $usersHtmlDisabled;
	}
	$documentRootValueHtml = ($hostedPath !== null) ? "value=\"" . htmlentities($hostedPath) . "\"" : "";
	$currentlySelectedHtml = $selected ? "Currently selected:" : "";
	$currentlySelectedClass = $selected ? "selected" : "";
	
	$output = "";
	$output .= <<<HTML
<div class="operation $currentlySelectedClass">
<h3>$currentlySelectedHtml Hosted site</h3>
<table>
<tr><th>Document root:</th><td>/home/</td><td>$usersHtml</td><td>/www/</td><td class="stretch"><input type="text" name="documentRoot" $documentRootValueHtml $readonlyHtml /></td><td>/</td></tr>
<tr class="submit"><td colspan="6"><input type="submit" name="type" value="Use Hosted Site" /></td></tr>
</table>
</div>

HTML;
	return $output;
}

function pathFunctionSubformRedirect($confirm, $selected, $redirectTarget)
{
	$readonlyHtml = ($confirm ? "readonly=\"readonly\"" : "");
	$redirectTargetValueHtml = $selected ? "value=\"" . htmlentities($redirectTarget) . "\"" : "value=\"http://\"";
	$currentlySelectedHtml = $selected ? "Currently selected:" : "";
	$currentlySelectedClass = $selected ? "selected" : "";
	
	return <<<HTML
<div class="operation $currentlySelectedClass">
<h3>$currentlySelectedHtml Redirect</h3>
A redirect to an external site.
<table>
<tr><th>Redirect target:</th><td><input type="text" name="redirectTarget" $redirectTargetValueHtml $readonlyHtml /></td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Use Redirect" /></td></tr>
</table>
</div>

HTML;
}

function pathFunctionSubformMirror($confirm, $selected, $pathID, $mirrorTargetPathID)
{
	$readonlyHtml = ($confirm ? "readonly=\"readonly\"" : "");
	$paths = array();
	foreach($GLOBALS["database"]->query("SELECT pathID FROM httpPath INNER JOIN httpDomain ON httpPath.domainID = httpDomain.domainID WHERE httpDomain.customerID = '" . $GLOBALS["database"]->addSlashes(customerID()) . "' AND httpPath.type != 'MIRROR'" . ($pathID === null ? "" : " AND httpPath.pathID <> '" . $GLOBALS["database"]->addSlashes($pathID) . "'"))->fetchList() as $path) {
		$paths[$path["pathID"]] = pathName($path["pathID"]);
	}
	asort($paths);
	if(!$confirm) {
		$pathsHtml = "<select name=\"mirrorTarget\" $readonlyHtml >";
		foreach($paths as $id=>$address) {
			$addressHtml = htmlentities($address);
			$selectedHtml = ($mirrorTargetPathID == $id) ? "selected=\"selected\"" : "";
			$pathsHtml .= "<option value=\"$id\" $selectedHtml>$addressHtml</option>";
		}
		$pathsHtml .= "</select>";
	} else {
		$addressHtml = "";
		foreach($paths as $id=>$address) {
			if($mirrorTargetPathID == $id) {
				$addressHtml = htmlentities($address);
			}
		}
		$pathsHtml = "<input type=\"hidden\" name=\"mirrorTarget\" value=\"$mirrorTargetPathID\">";
		$pathsHtml .= "<input type=\"text\" name=\"mirrorTargetText\" value=\"$addressHtml\" readonly=\"readonly\">";
	}
	$currentlySelectedHtml = $selected ? "Currently selected:" : "";
	$currentlySelectedClass = $selected ? "selected" : "";
	return <<<HTML
<div class="operation $currentlySelectedClass">
<h3>$currentlySelectedHtml Alias</h3>
An alternative address to reach one of your existing sites.
<table>
<tr><th>Aliased website:</th><td>$pathsHtml</td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Use Alias" /></td></tr>
</table>
</div>

HTML;
}

function pathFunctionSubform($confirm = false, $type = null, $pathID = null, $hostedUserID = null, $hostedPath = null, $redirectTarget = null, $mirrorTargetPathID = null)
{
	$output = "";
	
	$hostedHtml = pathFunctionSubformHosted($confirm, $type == "HOSTED", $hostedUserID, $hostedPath);
	if($type == "HOSTED") {
		$output = $hostedHtml . "\n" . $output;
	} else if(!$confirm) {
		$output .= "\n" . $hostedHtml;
	}
	
	$redirectHtml = pathFunctionSubformRedirect($confirm, $type == "REDIRECT", $redirectTarget);
	if($type == "REDIRECT") {
		$output = $redirectHtml . "\n" . $output;
	} else if(!$confirm) {
		$output .= "\n" . $redirectHtml;
	}
	
	$mirrorHtml = pathFunctionSubformMirror($confirm, $type == "MIRROR", $pathID, $mirrorTargetPathID);
	if($type == "MIRROR") {
		$output = $mirrorHtml . "\n" . $output;
	} else if(!$confirm) {
		$output .= "\n" . $mirrorHtml;
	}
	
	return $output;
}

function typeFromTitle($title)
{
	if($title == "Use Hosted Site") {
		return "HOSTED";
	} else if($title == "Use Redirect") {
		return "REDIRECT";
	} else if($title == "Use Alias") {
		return "MIRROR";
	} else {
		return null;
	}
}

function addSubdomainForm($domainID, $error = "", $name = null, $type = null, $hostedUserID = null, $hostedPath = null, $redirectTarget = null, $mirrorTargetPathID = null)
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
		$submitHTML = "<tr class=\"submit\"><td colspan=\"3\"><input type=\"submit\" value=\"Add\"></td></tr>";
	} else {
		$operationsHtml = pathFunctionSubform($readonly != "", $type, null, $hostedUserID, $hostedPath, $redirectTarget, $mirrorTargetPathID);
		$submitHTML = "";
	}
	
	$nameValue = inputValue($name);
	return <<<HTML
<div class="operation">
<h2>Add subdomain</h2>
$messageHtml
<form action="addsubdomain.php?id=$domainID" method="post">
$confirmHtml
<table>
<tr><th>Subdomain name:</th><td class="stretch"><input type="text" name="name" $nameValue /></td><td>.$parentNameHtml</td></tr>
$submitHTML
</table>

$operationsHtml

</form>
</div>

HTML;
}

function addPathForm($pathID, $error = "", $name = null, $type = null, $hostedUserID = null, $hostedPath = null, $redirectTarget = null, $mirrorTargetPathID = null)
{
	$parentName = pathName($pathID);
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
		$submitHTML = "<tr class=\"submit\"><td colspan=\"3\"><input type=\"submit\" value=\"Add\"></td></tr>";
	} else {
		$operationsHtml = pathFunctionSubform($readonly != "", $type, null, $hostedUserID, $hostedPath, $redirectTarget, $mirrorTargetPathID);
		$submitHTML = "";
	}
	
	$nameValue = inputValue($name);
	return <<<HTML
<div class="operation">
<h2>Add subdirectory</h2>
$messageHtml
<form action="addpath.php?id=$pathID" method="post">
$confirmHtml
<table>
<tr><th>Directory name:</th><td>$parentNameHtml/</td><td class="stretch"><input type="text" name="name" $nameValue /></td></tr>
$submitHTML
</table>

$operationsHtml

</form>
</div>

HTML;
}

function editPathForm($domainID, $pathID = null, $error = "", $type = null, $hostedUserID = null, $hostedPath = null, $redirectTarget = null, $mirrorTargetPathID = null)
{
	if($pathID === null) {
		$pathID = $GLOBALS["database"]->stdGet("httpPath", array("parentPathID"=>null, "domainID"=>$domainID), "pathID");
	}
	$pathName = pathName($pathID);
	$pathNameHtml = htmlentities($pathName);
	
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
	if($type == null) {
		$info = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), array("type", "hostedUserID", "hostedPath", "redirectTarget", "mirrorTargetPathID"));
		$type = $info["type"];
		$hostedUserID = $info["hostedUserID"];
		$hostedPath = $info["hostedPath"];
		$redirectTarget = $info["redirectTarget"];
		$mirrorTargetPathID = $info["mirrorTargetPathID"];
	}
	
	if($stub) {
		$url = "http://" . $pathNameHtml . "/";
		if($type == "HOSTED") {
			$username = $GLOBALS["database"]->stdGet("adminUser", array("userID"=>$hostedUserID), "username");
			$documentrootHtml = htmlentities("/home/$username/www/$hostedPath/");
			return <<<HTML
<div class="operation">
<h2>Site function</h2>
<form action="editpath.php?id=$pathID" method="post">
<table>
<tr><th>Function:</th><td class="stretch">Hosted site</td></tr>
<tr><th>Url:</th><td class="stretch"><a href="$url">$url</a></td></tr>
<tr><th>Document root:</th><td>$documentrootHtml</td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		} else if($type == "REDIRECT") {
			$targetHtml = htmlentities($redirectTarget);
			return <<<HTML
<div class="operation">
<h2>Site function</h2>
<form action="editpath.php?id=$pathID" method="post">
<table>
<tr><th>Function:</th><td class="stretch">Redirect</td></tr>
<tr><th>Url:</th><td class="stretch"><a href="$url">$url</a></td></tr>
<tr><th>Target:</th><td><a href="$targetHtml">$targetHtml</a></td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		} else if($type == "MIRROR") {
			$targetHtml = htmlentities("http://" . pathName($mirrorTargetPathID) . "/");
			return <<<HTML
<div class="operation">
<h2>Site function</h2>
<form action="editpath.php?id=$pathID" method="post">
<table>
<tr><th>Function:</th><td class="stretch">Alias</td></tr>
<tr><th>Url:</th><td class="stretch"><a href="$url">$url</a></td></tr>
<tr><th>Target:</th><td><a href="$targetHtml">$targetHtml</a></td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		}
		$operationsHtml = "";
	} else {
		$operationsHtml = pathFunctionSubform($readonly != "", $type, $pathID, $hostedUserID, $hostedPath, $redirectTarget, $mirrorTargetPathID);
	}
	
	return <<<HTML
<div class="operation">
<h2>Edit site $pathNameHtml</h2>
$messageHtml
<form action="editpath.php?id=$pathID" method="post">
$confirmHtml

$operationsHtml

</form>
</div>

HTML;
}

function removeDomainForm($domainID, $error = "", $keepSubs = false)
{
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$messageHtml .= "<p class=\"confirmdelete\">The following sites will be removed:</p>";
		if($keepSubs) {
			$messageHtml .= pathSummary(domainPath($domainID));
		} else {
			$messageHtml .= domainSummary($domainID);
		}
		$confirmHtml  = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$keepSubsValue = $keepSubs ? "keep" : "remove";
		$confirmHtml .= "<input type=\"hidden\" name=\"keepsubs\" value=\"$keepSubsValue\" />\n";
		$keepSubsHtml = "";
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		if(isRootDomain($domainID)) {
			$keepSubsHtml = "";
		} else {
			$keepSubsHtml = "<tr><td><label><input type=\"checkbox\" name=\"keepsubs\" value=\"keep\"/> Keep subdomains</label></td></tr>";
		}
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		if(isRootDomain($domainID)) {
			$keepSubsHtml = "";
		} else {
			$keepSubsHtml = "<tr><td><label><input type=\"checkbox\" name=\"keepsubs\" value=\"keep\"/> Keep subdomains</label></td></tr>";
		}
	}
	
	if($keepSubs) {
		$keepSubsChecked = "checked=\"checked\"";
	} else {
		$keepSubsChecked = "";
	}
	
	return <<<HTML
<div class="operation">
<h2>Remove domain</h2>
$messageHtml
<form action="removedomain.php?id=$domainID" method="post">
$confirmHtml
<table>
$keepSubsHtml
<tr class="submit"><td><input type="submit" value="Remove domain" /></td></tr>
</table>
</form>
</div>

HTML;
}

function removeDomain($domainID, $keepsubs)
{
	echo "removeDomain($domainID)<br>\n";
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
	echo "removePath($pathID)<br>\n";
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

function isRootDomain($domainID)
{
	return $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$domainID), "parentDomainID") === null;
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
	$path = $GLOBALS["database"]->stdGetTry("httpPath", array("domainID"=>$domainID, "parentPathID"=>null), array("pathID", "type", "hostedUserID", "hostedPath", "svnPath", "redirectTarget", "mirrorTargetPathID", "userDatabaseID"));
	if($path === null) {
		return null;
	}
	$output = array();
	$output["pathID"] = $path["pathID"];
	$output["type"] = $path["type"];
	$output["userDatabaseID"] = $path["userDatabaseID"];
	if($path["type"] == "HOSTED") {
		$username = username($path["hostedUserID"]);
		$output["target"] = "/home/$username/{$path["hostedPath"]}/";
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
	} else {
		return "";
	}
}

?>