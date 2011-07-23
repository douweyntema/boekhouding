<?php

require_once(dirname(__FILE__) . "/../common.php");

function doInfrastructure()
{
	useComponent("infrastructure");
	$GLOBALS["menuComponent"] = "infrastructure";
	useCustomer(0);
}

function fileSystemList()
{
	$output  = "<div class=\"sortable list\">\n";
	$output .= "<table>\n";
	$output .= "<caption>Filesystems</caption>";
	$output .= "<thead>\n";
	$output .= "<tr><th>Name</th><th>Description</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->stdList("infrastructureFileSystem", array(), array("fileSystemID", "name", "description"), array("name"=>"ASC")) as $fileSystem) {
		$nameHtml = htmlentities($fileSystem["name"]);
		$descriptionHtml = htmlentities($fileSystem["description"]);
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$fileSystem["fileSystemID"]}\">$nameHtml</a></td><td>$descriptionHtml</td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function hostList()
{
	$output  = "<div class=\"sortable list\">\n";
	$output .= "<table>\n";
	$output .= "<caption>Hosts</caption>";
	$output .= "<thead>\n";
	$output .= "<tr><th>Name</th><th>Description</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->stdList("infrastructureHost", array(), array("hostID", "name", "description"), array("name"=>"ASC")) as $host) {
		$nameHtml = htmlentities($host["name"]);
		$descriptionHtml = htmlentities($host["description"]);
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$host["hostID"]}\">$nameHtml</a></td><td>$descriptionHtml</td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function fileSystemDetail($fileSystemID)
{
	$fileSystem = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), array("name", "description"));
	$fileSystemNameHtml = htmlentities($fileSystem["name"]);
	$fileSystemDescriptionHtml = htmlentities($fileSystem["description"]);
	
	$output  = "<div class=\"operation\">\n";
	$output .= "<h2>Filesystem $fileSystemNameHtml</h2>";
	$output .= "<table>";
	$output .= "<tr><th>Name:</th><td class=\"stretch\">$fileSystemNameHtml</td></tr>";
	$output .= "<tr><th>Description:</th><td class=\"stretch\">$fileSystemDescriptionHtml</td></tr>";
	$output .= "</table>";
	$output .= "</div>";
	return $output;
}

function fileSystemCustomersList($fileSystemID)
{
	$fileSystem = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), array("name", "description"));
	$fileSystemNameHtml = htmlentities($fileSystem["name"]);
	$output  = "<div class=\"sortable list\">\n";
	$output .= "<table>\n";
	$output .= "<caption>Customers using filesystem $fileSystemNameHtml</caption>";
	$output .= "<thead>\n";
	$output .= "<tr><th>Nickname</th><th>Name</th><th>Email</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->stdList("adminCustomer", array("fileSystemID"=>$fileSystemID), array("customerID", "name", "realname", "email"), array("name"=>"ASC")) as $customer) {
		$nicknameHtml = htmlentities($customer["name"]);
		$nameHtml = htmlentities($customer["realname"]);
		$emailHtml = htmlentities($customer["email"]);
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}customers/customer.php?id={$customer["customerID"]}\">$nicknameHtml</a></td><td>$nameHtml</td><td><a href=\"mailto:{$customer["email"]}\">$emailHtml</a></td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function fileSystemHostList($fileSystemID)
{
	$fileSystem = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), array("name", "description", "fileSystemVersion", "httpVersion"));
	$fileSystemNameHtml = htmlentities($fileSystem["name"]);
	$output  = "<div class=\"sortable list\">\n";
	$output .= "<table>\n";
	$output .= "<caption>Hosts in filesystem $fileSystemNameHtml</caption>";
	$output .= "<thead>\n";
	$output .= "<tr><th>Name</th><th>Hostname</th><th>Login</th><th>Mount</th><th>Webhosting</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach(magicQuery(array("fileSystem.fileSystemID"=>$fileSystemID)) as $host) {
		$hostnameHtml = htmlentities($host["hostname"]);
		$nameHtml = htmlentities($host["name"]);
		$customerLogin = $host["allowCustomerLogin"] == 1 ? "Yes" : "No";
		if($host["mountVersion"] == null) {
			$mountOK = "-";
		} else if($host["mountVersion"] == $fileSystem["fileSystemVersion"]) {
			$mountOK = "OK";
		} else {
			$mountOK = "Out of date";
		}
		if($host["webserverVersion"] == null) {
			$webserverOK = "-";
		} else if($host["webserverVersion"] == $fileSystem["httpVersion"]) {
			$webserverOK = "OK";
		} else {
			$webserverOK = "Out of date";
		}
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$host["hostID"]}\">$nameHtml</a></td><td>$hostnameHtml</td><td>$customerLogin</td><td>$mountOK</td><td>$webserverOK</td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function hostDetail($hostID)
{
	$host = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("name", "hostname", "sshPort", "description"));
	$hostNameHtml = htmlentities($host["name"]);
	$hostHostnameHtml = htmlentities($host["hostname"]);
	$hostSshPortHtml = htmlentities($host["sshPort"]);
	$hostDescriptionHtml = htmlentities($host["description"]);
	
	$output  = "<div class=\"operation\">\n";
	$output .= "<h2>Host $hostNameHtml</h2>";
	$output .= "<table>";
	$output .= "<tr><th>Name:</th><td class=\"stretch\">$hostNameHtml</td></tr>";
	$output .= "<tr><th>Hostname:</th><td class=\"stretch\">$hostHostnameHtml</td></tr>";
	$output .= "<tr><th>SSH port:</th><td class=\"stretch\">$hostSshPortHtml</td></tr>";
	$output .= "<tr><th>Description:</th><td class=\"stretch\">$hostDescriptionHtml</td></tr>";
	$output .= "</table>";
	$output .= "</div>";
	return $output;
}

function hostFileSystemList($hostID)
{
	$host = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("name", "description"));
	$hostNameHtml = htmlentities($host["name"]);
	$output  = "<div class=\"sortable list\">\n";
	$output .= "<table>\n";
	$output .= "<caption>Filesystems used by host $hostNameHtml</caption>";
	$output .= "<thead>\n";
	$output .= "<tr><th>Name</th><th>Login</th><th>Mount</th><th>Webhosting</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach(magicQuery(array("host.hostID"=>$hostID)) as $fileSystem) {
		$nameHtml = htmlentities($fileSystem["name"]);
		$customerLogin = $fileSystem["allowCustomerLogin"] == 1 ? "Yes" : "No";
		if($fileSystem["mountVersion"] == null) {
			$mountOK = "-";
		} else if($fileSystem["mountVersion"] == $fileSystem["fileSystemVersion"]) {
			$mountOK = "OK";
		} else {
			$mountOK = "Out of date";
		}
		if($fileSystem["webserverVersion"] == null) {
			$webserverOK = "-";
		} else if($fileSystem["webserverVersion"] == $fileSystem["httpVersion"]) {
			$webserverOK = "OK";
		} else {
			$webserverOK = "Out of date";
		}
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$fileSystem["fileSystemID"]}\">$nameHtml</a></td><td>$customerLogin</td><td>$mountOK</td><td>$webserverOK</td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function magicQuery($where = null)
{
	if($where === null) {
		$whereSql = "";
	} else {
		if(is_array($where)) {
			$whereSql = "";
			if(count($where) != 0) {
				reset($where);
				while(list($key, $value) = each($where)) {
					if($value === null) {
						$whereSql .= "AND " . $key . " IS NULL ";
					} else {
						$whereSql .= "AND " . $key . "='" . $GLOBALS["database"]->addSlashes($value) . "' ";
					}
				}
			}
		} else {
			$whereSql = $where;
		}
	}
	return $GLOBALS["database"]->query("SELECT host.hostID, host.hostname, host.name, fileSystem.fileSystemID, fileSystem.name, fileSystem.description, fileSystem.fileSystemVersion, fileSystem.httpVersion, mount.version AS mountVersion, mount.allowCustomerLogin, webServer.version AS webserverVersion 
	FROM infrastructureHost AS host 
	CROSS JOIN infrastructureFileSystem AS fileSystem 
	LEFT JOIN infrastructureMount AS mount ON mount.hostID = host.hostID AND mount.fileSystemID = fileSystem.fileSystemID 
	LEFT JOIN infrastructureWebServer AS webServer ON webServer.hostID = host.hostID AND webServer.fileSystemID = fileSystem.fileSystemID 
	WHERE (fileSystem.fileSystemID IN (SELECT fileSystemID FROM infrastructureMount WHERE infrastructureMount.hostID = host.hostID) 
	OR fileSystem.fileSystemID IN (SELECT fileSystemID FROM infrastructureWebServer WHERE infrastructureWebServer.hostID = host.hostID)) " . $whereSql)->fetchList();
}

function hostRefresh($hostID)
{
	$hostName = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), "name");
	$hostNameHtml = htmlentities($hostName);
	
	$output  = "<div class=\"operation\">\n";
	$output .= "<h2>Refresh host $hostNameHtml</h2>";
	$output .= "<table>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id=$hostID\" method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"all\"><input type=\"submit\" value=\"Refresh everything\"></form>";
	$output .= "</td></tr>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id=$hostID\" method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"fileSystem\"><input type=\"submit\" value=\"Refresh fileSystem\"></form>";
	$output .= "</td></tr>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id=$hostID\"  method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"webserver\"><input type=\"submit\" value=\"Refresh webserver\"></form>";
	$output .= "</td></tr>";
	$output .= "</table>";
	$output .= "</div>";
	return $output;
}

function fileSystemRefresh($fileSystemID)
{
	$fileSystemName = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "name");
	$fileSystemNameHtml = htmlentities($fileSystemName);
	
	$output  = "<div class=\"operation\">\n";
	$output .= "<h2>Refresh filesystem $fileSystemNameHtml</h2>";
	$output .= "<table>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id=$fileSystemID\" method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"all\"><input type=\"submit\" value=\"Refresh everything\"></form>";
	$output .= "</td></tr>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id=$fileSystemID\" method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"fileSystem\"><input type=\"submit\" value=\"Refresh mounts\"></form>";
	$output .= "</td></tr>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id=$fileSystemID\"  method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"webserver\"><input type=\"submit\" value=\"Refresh webservers\"></form>";
	$output .= "</td></tr>";
	$output .= "</table>";
	$output .= "</div>";
	return $output;
}

function refreshHostMount($hostID)
{
	updateHosts(array($hostID), "update-treva-passwd --force");
}

function refreshHostWebServer($hostID)
{
	updateHosts(array($hostID), "update-treva-apache --force");
}

function refreshFileSystemMount($fileSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureMount", array("fileSystemID"=>$fileSystemID), "hostID");
	updateHosts($hosts, "update-treva-passwd --force");
}

function refreshFileSystemWebServer($fileSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureWebServer", array("fileSystemID"=>$fileSystemID), "hostID");
	updateHosts($hosts, "update-treva-apache --force");
}

?>