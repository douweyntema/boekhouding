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

function mailSystemList()
{
	$output  = "<div class=\"sortable list\">\n";
	$output .= "<table>\n";
	$output .= "<caption>mailsystems</caption>";
	$output .= "<thead>\n";
	$output .= "<tr><th>Name</th><th>Description</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->stdList("infrastructureMailSystem", array(), array("mailSystemID", "name", "description"), array("name"=>"ASC")) as $mailSystem) {
		$nameHtml = htmlentities($mailSystem["name"]);
		$descriptionHtml = htmlentities($mailSystem["description"]);
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id={$mailSystem["mailSystemID"]}\">$nameHtml</a></td><td>$descriptionHtml</td></tr>\n";
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
	$output .= "<tr><th>Hostname</th><th>Description</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->stdList("infrastructureHost", array(), array("hostID", "hostname", "description"), array("hostname"=>"ASC")) as $host) {
		$hostnameHtml = htmlentities($host["hostname"]);
		$descriptionHtml = htmlentities($host["description"]);
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$host["hostID"]}\">$hostnameHtml</a></td><td>$descriptionHtml</td></tr>\n";
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
	foreach($GLOBALS["database"]->stdList("adminCustomer", array("fileSystemID"=>$fileSystemID), array("customerID", "name", "initials", "lastName", "email"), array("name"=>"ASC")) as $customer) {
		$nicknameHtml = htmlentities($customer["name"]);
		$nameHtml = htmlentities($customer["initials"] . " " . $customer["lastName"]);
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
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$host["hostID"]}\">$hostnameHtml</a></td><td>$hostnameHtml</td><td>$customerLogin</td><td>$mountOK</td><td>$webserverOK</td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}


function mailSystemDetail($mailSystemID)
{
	$mailSystem = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), array("name", "description"));
	$mailSystemNameHtml = htmlentities($mailSystem["name"]);
	$mailSystemDescriptionHtml = htmlentities($mailSystem["description"]);
	
	$output  = "<div class=\"operation\">\n";
	$output .= "<h2>Mailsystem $mailSystemNameHtml</h2>";
	$output .= "<table>";
	$output .= "<tr><th>Name:</th><td class=\"stretch\">$mailSystemNameHtml</td></tr>";
	$output .= "<tr><th>Description:</th><td class=\"stretch\">$mailSystemDescriptionHtml</td></tr>";
	$output .= "</table>";
	$output .= "</div>";
	return $output;
}

function mailSystemCustomersList($mailSystemID)
{
	$mailSystem = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), array("name", "description"));
	$mailSystemNameHtml = htmlentities($mailSystem["name"]);
	$output  = "<div class=\"sortable list\">\n";
	$output .= "<table>\n";
	$output .= "<caption>Customers using mailsystem $mailSystemNameHtml</caption>";
	$output .= "<thead>\n";
	$output .= "<tr><th>Nickname</th><th>Name</th><th>Email</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->stdList("adminCustomer", array("mailSystemID"=>$mailSystemID), array("customerID", "name", "initials", "lastName", "email"), array("name"=>"ASC")) as $customer) {
		$nicknameHtml = htmlentities($customer["name"]);
		$nameHtml = htmlentities($customer["initials"] . " " . $customer["lastName"]);
		$emailHtml = htmlentities($customer["email"]);
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}customers/customer.php?id={$customer["customerID"]}\">$nicknameHtml</a></td><td>$nameHtml</td><td><a href=\"mailto:{$customer["email"]}\">$emailHtml</a></td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function mailSystemHostList($mailSystemID)
{
	$mailSystem = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), array("name", "description", "version"));
	$mailSystemNameHtml = htmlentities($mailSystem["name"]);
	$output  = "<div class=\"sortable list\">\n";
	$output .= "<table>\n";
	$output .= "<caption>Hosts in mailsystem $mailSystemNameHtml</caption>";
	$output .= "<thead>\n";
	$output .= "<tr><th>Hostname</th><th>Primary</th><th>Dovecot</th><th>Exim</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->query("SELECT host.hostID, host.hostname, mailSystem.mailSystemID, mailSystem.description, mailSystem.version AS systemVersion, mailServer.dovecotVersion, mailServer.eximVersion, mailServer.primary
	FROM infrastructureMailSystem AS mailSystem 
	LEFT JOIN infrastructureMailServer AS mailServer ON mailServer.mailSystemID = mailSystem.mailSystemID 
	LEFT JOIN infrastructureHost AS host ON mailServer.hostID = host.hostID 
	WHERE mailSystem.mailSystemID = $mailSystemID
	ORDER BY mailServer.primary DESC, host.hostname
	")->fetchList() as $host) {
		$hostnameHtml = htmlentities($host["hostname"]);
		$customerLogin = $host["primary"] == 1 ? "Yes" : "No";
		if($host["dovecotVersion"] == null) {
			$dovecotOK = "-";
		} else if($host["dovecotVersion"] == $host["systemVersion"]) {
			$dovecotOK = "OK";
		} else {
			$dovecotOK = "Out of date";
		}
		if($host["eximVersion"] == null) {
			$eximOK = "-";
		} else if($host["eximVersion"] == $host["systemVersion"]) {
			$eximOK = "OK";
		} else {
			$eximOK = "Out of date";
		}
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$host["hostID"]}\">$hostnameHtml</a></td><td>$customerLogin</td><td>$dovecotOK</td><td>$eximOK</td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}


function hostDetail($hostID)
{
	$host = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("hostname", "sshPort", "description"));
	$hostHostnameHtml = htmlentities($host["hostname"]);
	$hostSshPortHtml = htmlentities($host["sshPort"]);
	$hostDescriptionHtml = htmlentities($host["description"]);
	
	$output  = "<div class=\"operation\">\n";
	$output .= "<h2>Host $hostHostnameHtml</h2>";
	$output .= "<table>";
	$output .= "<tr><th>Hostname:</th><td class=\"stretch\">$hostHostnameHtml</td></tr>";
	$output .= "<tr><th>SSH port:</th><td class=\"stretch\">$hostSshPortHtml</td></tr>";
	$output .= "<tr><th>Description:</th><td class=\"stretch\">$hostDescriptionHtml</td></tr>";
	$output .= "</table>";
	$output .= "</div>";
	return $output;
}

function hostFileSystemList($hostID)
{
	$host = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("hostname", "description"));
	$hostnameHtml = htmlentities($host["hostname"]);
	$output  = "<div class=\"sortable list\">\n";
	$output .= "<table>\n";
	$output .= "<caption>Filesystems used by host $hostnameHtml</caption>";
	$output .= "<thead>\n";
	$output .= "<tr><th>Name</th><th>Login</th><th>Mount</th><th>Webhosting</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach(magicQuery(array("host.hostID"=>$hostID)) as $fileSystem) {
		$hostnameHtml = htmlentities($fileSystem["hostname"]);
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
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$fileSystem["fileSystemID"]}\">$hostnameHtml</a></td><td>$customerLogin</td><td>$mountOK</td><td>$webserverOK</td></tr>\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	return $output;
}

function hostMailSystemList($hostID)
{
	$host = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("hostname", "description"));
	$hostnameHtml = htmlentities($host["hostname"]);
	$output  = "<div class=\"sortable list\">\n";
	$output .= "<table>\n";
	$output .= "<caption>Mailsystems used by host $hostnameHtml</caption>";
	$output .= "<thead>\n";
	$output .= "<tr><th>Name</th><th>Primary</th><th>Dovecot</th><th>Exim</th></tr>\n";
	$output .= "</thead>\n";
	$output .= "<tbody>\n";
	foreach($GLOBALS["database"]->query("SELECT host.hostID, host.hostname, mailSystem.name AS systemName, mailSystem.mailSystemID, mailSystem.description, mailSystem.version AS systemVersion, mailServer.dovecotVersion, mailServer.eximVersion, mailServer.primary
	FROM infrastructureMailSystem AS mailSystem 
	LEFT JOIN infrastructureMailServer AS mailServer ON mailServer.mailSystemID = mailSystem.mailSystemID 
	LEFT JOIN infrastructureHost AS host ON mailServer.hostID = host.hostID 
	WHERE host.hostID = $hostID
	ORDER BY mailServer.primary DESC, host.hostname
	")->fetchList() as $mailsystem) {
		$nameHtml = htmlentities($mailsystem["systemName"]);
		$customerLogin = $mailsystem["primary"] == 1 ? "Yes" : "No";
		if($mailsystem["dovecotVersion"] == null) {
			$dovecotOK = "-";
		} else if($mailsystem["dovecotVersion"] == $mailsystem["systemVersion"]) {
			$dovecotOK = "OK";
		} else {
			$dovecotOK = "Out of date";
		}
		if($mailsystem["eximVersion"] == null) {
			$eximOK = "-";
		} else if($mailsystem["eximVersion"] == $mailsystem["systemVersion"]) {
			$eximOK = "OK";
		} else {
			$eximOK = "Out of date";
		}
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id={$mailsystem["mailSystemID"]}\">$nameHtml</a></td><td>$customerLogin</td><td>$dovecotOK</td><td>$eximOK</td></tr>\n";
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
	return $GLOBALS["database"]->query("SELECT host.hostID, host.hostname, fileSystem.fileSystemID, fileSystem.name, fileSystem.description, fileSystem.fileSystemVersion, fileSystem.httpVersion, mount.version AS mountVersion, mount.allowCustomerLogin, webServer.version AS webserverVersion 
	FROM infrastructureHost AS host 
	CROSS JOIN infrastructureFileSystem AS fileSystem 
	LEFT JOIN infrastructureMount AS mount ON mount.hostID = host.hostID AND mount.fileSystemID = fileSystem.fileSystemID 
	LEFT JOIN infrastructureWebServer AS webServer ON webServer.hostID = host.hostID AND webServer.fileSystemID = fileSystem.fileSystemID 
	WHERE (fileSystem.fileSystemID IN (SELECT fileSystemID FROM infrastructureMount WHERE infrastructureMount.hostID = host.hostID) 
	OR fileSystem.fileSystemID IN (SELECT fileSystemID FROM infrastructureWebServer WHERE infrastructureWebServer.hostID = host.hostID)) " . $whereSql)->fetchList();
}

function hostRefresh($hostID)
{
	$hostname = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), "hostname");
	$hostnameHtml = htmlentities($hostname);
	
	$output  = "<div class=\"operation\">\n";
	$output .= "<h2>Refresh host $hostnameHtml</h2>";
	$output .= "<table>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id=$hostID\" method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"all\"><input type=\"submit\" value=\"Refresh everything\"></form>";
	$output .= "</td></tr>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id=$hostID\" method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"fileSystem\"><input type=\"submit\" value=\"Refresh fileSystem\"></form>";
	$output .= "</td></tr>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id=$hostID\" method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"webserver\"><input type=\"submit\" value=\"Refresh webserver\"></form>";
	$output .= "</td></tr>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id=$hostID\" method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"dovecot\"><input type=\"submit\" value=\"Refresh dovecot\"></form>";
	$output .= "</td></tr>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/host.php?id=$hostID\" method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"exim\"><input type=\"submit\" value=\"Refresh exim\"></form>";
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

function mailSystemRefresh($mailSystemID)
{
	$mailSystemName = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "name");
	$mailSystemNameHtml = htmlentities($mailSystemName);
	
	$output  = "<div class=\"operation\">\n";
	$output .= "<h2>Refresh mailsystem $mailSystemNameHtml</h2>";
	$output .= "<table>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id=$mailSystemID\" method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"dovecot\"><input type=\"submit\" value=\"Refresh dovecot\"></form>";
	$output .= "</td></tr>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id=$mailSystemID\" method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"exim\"><input type=\"submit\" value=\"Refresh exim\"></form>";
	$output .= "</td></tr>";
	$output .= "<tr class=\"submit\"><td>";
	$output .= "<form action=\"{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id=$mailSystemID\" method=\"post\"><input type=\"hidden\" name=\"refresh\" value=\"all\"><input type=\"submit\" value=\"Refresh everything\"></form>";
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

function refreshHostDovecot($hostID)
{
	updateHosts(array($hostID), "update-treva-dovecot --force");
}

function refreshHostExim($hostID)
{
	updateHosts(array($hostID), "update-treva-exim --force");
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

function refreshMailSystemDovecot($mailSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), "hostID");
	updateHosts($hosts, "update-treva-dovecot --force");
}

function refreshMailSystemExim($mailSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), "hostID");
	updateHosts($hosts, "update-treva-dovecot --force");
}

?>