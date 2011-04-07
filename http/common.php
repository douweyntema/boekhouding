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

function doHttpVirtualhost($virtualhostID)
{
	doHttpDomain($GLOBALS["database"]->stdGetTry("httpVirtualhost", array("virtualhostID"=>$virtualhostID), "domainID", null));
}

function doHttpMountpoint($mountpointID)
{
	doHttpVirtualhost($GLOBALS["database"]->stdGetTry("httpMountpoint", array("mountpointID"=>$mountpointID), "virtualhostID", null));
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
<div class="list">
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

function virtualhostList($domainID)
{
	$output = "";
	
	$virtualhosts = virtualhosts($domainID, "");
	$output .= <<<HTML
<div class="list">
<table>
<thead>
<tr><th>Domain</th><th>Type</th><th>Visit</th></tr>
</thead>
<tbody>

HTML;
	foreach($virtualhosts as $virtualhost) {
		$url = $virtualhost["domainName"];
		if($virtualhost["https"] == 1) {
			$urlType = "https://";
			if($virtualhost["port"] != "443") {
				$url .= ":" . $virtualhost["port"];
			}
		} else {
			$urlType = "http://";
			if($virtualhost["port"] != "80") {
				$url .= ":" . $virtualhost["port"];
			}
		}
		$domainNameHtml = htmlentities($urlType . $url);
		$urlHtml = htmlentities($urlType . $url);
		$targetHtml = htmlentities($virtualhost["target"]);
		
		if($virtualhost["type"] == "NONE") {
			$type = "Not in use";
			continue;
		} else if($virtualhost["type"] == "HOSTED") {
			$type = "Hosted site";
		} else if($virtualhost["type"] == "SVN") {
			$type = "SVN repository";
		} else if($virtualhost["type"] == "REDIRECT") {
			$type = "Redirect to";
		} else if($virtualhost["type"] == "MIRROR") {
			$type = "Mirror of";
		}
		
		$output .= "<tr>";
		$output .= "<td><a href=\"{$GLOBALS["rootHtml"]}http/virtualhost.php?id={$virtualhost["virtualhostID"]}\">$domainNameHtml</a></td>";
		$output .= "<td>$type: $targetHtml</td>";
		$output .= "<td><a href=\"$urlHtml\" class=\"external\">Visit</a></td>";
		$output .= "</tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	
	return $output;
}

function virtualhosts($domainID, $parentDomainName)
{
	$name = $GLOBALS["database"]->stdGet("httpDomain", array("domainID"=>$domainID), "name");
	if($parentDomainName == "") {
		$domainName = $name;
	} else {
		$domainName = $name . "." . $parentDomainName;
	}
	
	$virtualhosts = array();
	
	$vhosts = $GLOBALS["database"]->stdList("httpVirtualhost", array("domainID"=>$domainID), array("virtualhostID", "socketID", "serverAdmin", "customConfigText"));
	foreach($vhosts as $vhost) {
		$socket = $GLOBALS["database"]->stdGet("httpSocket", array("socketID"=>$vhost["socketID"]), array("ip", "port", "https"));
		$mountpoint = mountpointInfo($vhost["virtualhostID"]);
		
		$info = array("domainName"=>$domainName);
		$info = array_merge($info, $vhost);
		$info = array_merge($info, $socket);
		$info = array_merge($info, $mountpoint);
		$virtualhosts[] = $info;
	}
	
	$childs = $GLOBALS["database"]->stdList("httpDomain", array("parentDomainID"=>$domainID), "domainID");
	foreach($childs as $child) {
		$virtualhosts = array_merge($virtualhosts, virtualhosts($child, $domainName));
	}
	return $virtualhosts;
}

function mountpointInfo($virtualhostID)
{
	$mountpoint = $GLOBALS["database"]->stdGet("httpMountpoint", array("virtualhostID"=>$virtualhostID, "parentMountpointID"=>null), array("mountpointID", "type", "hostedPath", "svnPath", "redirectTarget", "mirrorTargetMountpointID"));
	
	if($mountpoint["type"] == "NONE") {
		$target = "";
	} else if($mountpoint["type"] == "HOSTED") {
		$target = $mountpoint["hostedPath"];
	} else if($mountpoint["type"] == "SVN") {
		$target = $mountpoint["svnPath"];
	} else if($mountpoint["type"] == "REDIRECT") {
		$target = $mountpoint["redirectTarget"];
	} else if($mountpoint["type"] == "MIRROR") {
		$info = mountpointInfo($mountpoint["mirrorTargetMountpointID"]);
		$target = $info["target"];
	}
	$mountpoint["target"] = $target;
	
	return $mountpoint;
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

?>