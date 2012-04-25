<?php

require_once(dirname(__FILE__) . "/../common.php");

function doInfrastructure()
{
	useComponent("infrastructure");
	$GLOBALS["menuComponent"] = "infrastructure";
	useCustomer(0);
}

function crumb($name, $filename)
{
	return array("name"=>$name, "url"=>"{$GLOBALS["root"]}infrastructure/$filename");
}

function crumbs($name, $filename)
{
	return array(crumb($name, $filename));
}

function infrastructureBreadcrumbs()
{
	return crumbs("Infrastructure", "");
}

function fileSystemList()
{
	$rows = array();
	foreach($GLOBALS["database"]->stdList("infrastructureFileSystem", array(), array("fileSystemID", "name", "description"), array("name"=>"ASC")) as $fileSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$fileSystem["fileSystemID"]}", "text"=>$fileSystem["name"]),
			$fileSystem["description"]
		);
	}
	return listTable(array("Name", "Description"), $rows, "sortable list", "Filesystems");
}

function mailSystemList()
{
	$rows = array();
	foreach($GLOBALS["database"]->stdList("infrastructureMailSystem", array(), array("mailSystemID", "name", "description"), array("name"=>"ASC")) as $mailSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id={$mailSystem["mailSystemID"]}", "text"=>$mailSystem["name"]),
			$mailSystem["description"]
		);
	}
	return listTable(array("Name", "Description"), $rows, "sortable list", "Mailsystems");
}

function nameSystemList()
{
	$rows = array();
	foreach($GLOBALS["database"]->stdList("infrastructureNameSystem", array(), array("nameSystemID", "name", "description"), array("name"=>"ASC")) as $nameSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/namesystem.php?id={$nameSystem["nameSystemID"]}", "text"=>$nameSystem["name"]),
			$nameSystem["description"]
		);
	}
	return listTable(array("Name", "Description"), $rows, "sortable list", "Namesystems");
}

function hostList()
{
	$rows = array();
	foreach($GLOBALS["database"]->stdList("infrastructureHost", array(), array("hostID", "hostname", "description"), array("hostname"=>"ASC")) as $host) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$host["hostID"]}", "text"=>$host["hostname"]),
			$host["description"]
		);
	}
	return listTable(array("Name", "Description"), $rows, "sortable list", "Hosts");
}

function customerList($entityName, $customers)
{
	$rows = array();
	foreach($customers as $customer) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}customers/customer.php?id={$customer["customerID"]}", "text"=>$customer["name"]),
			$customer["initials"] . " " . $customer["lastName"],
			array("url"=>"mailto:{$customer["email"]}", "text"=>$customer["email"])
		);
	}
	return listTable(array("Nickname", "Name", "Email"), $rows, "sortable list", "Customers using $entityName");
}

function fileSystemDetail($fileSystemID)
{
	$fileSystem = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), array("name", "description"));
	return summaryTable("Filesystem {$fileSystem["name"]}", array(
		"Name"=>$fileSystem["name"],
		"Description"=>$fileSystem["description"]
		));
}

function fileSystemCustomersList($fileSystemID)
{
	$fileSystemName = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "name");
	$customers = $GLOBALS["database"]->stdList("adminCustomer", array("fileSystemID"=>$fileSystemID), array("customerID", "name", "initials", "lastName", "email"), array("name"=>"ASC"));
	return customerList("filesystem $fileSystemName", $customers);
}

function fileSystemHostList($fileSystemID)
{
	$fileSystemName = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "name");
	$rows = array();
	foreach(hostFileSystems(null, $fileSystemID) as $hostFileSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$hostFileSystem["hostID"]}", "text"=>$hostFileSystem["hostname"]),
			($hostFileSystem["allowCustomerLogin"] == 1 ? "Yes" : "No"),
			($hostFileSystem["mountVersion"] === null ? "-" : ($hostFileSystem["mountVersion"] == $hostFileSystem["fileSystemVersion"] ? "OK" : "Out of date")),
			($hostFileSystem["webserverVersion"] === null ? "-" : ($hostFileSystem["webserverVersion"] == $hostFileSystem["httpVersion"] ? "OK" : "Out of date"))
		);
	}
	return listTable(array("Name", "Login", "Mount", "Webhosting"), $rows, "sortable list", "Hosts in filesystem $fileSystemName");
}

function mailSystemDetail($mailSystemID)
{
	$mailSystem = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), array("name", "description"));
	return summaryTable("Mailsystem {$mailSystem["name"]}", array(
		"Name"=>$mailSystem["name"],
		"Description"=>$mailSystem["description"]
		));
}

function mailSystemCustomersList($mailSystemID)
{
	$mailSystemName = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "name");
	$customers = $GLOBALS["database"]->stdList("adminCustomer", array("mailSystemID"=>$mailSystemID), array("customerID", "name", "initials", "lastName", "email"), array("name"=>"ASC"));
	return customerList("mailsystem $mailSystemName", $customers);
}

function mailSystemHostList($mailSystemID)
{
	$mailSystemName = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "name");
	$rows = array();
	foreach(hostMailSystems(null, $mailSystemID) as $hostMailSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$hostMailSystem["hostID"]}", "text"=>$hostMailSystem["hostname"]),
			($hostMailSystem["primary"] == 1 ? "Yes" : "No"),
			($hostMailSystem["dovecotVersion"] === null ? "-" : ($hostMailSystem["dovecotVersion"] == $hostMailSystem["systemVersion"] ? "OK" : "Out of date")),
			($hostMailSystem["eximVersion"] === null ? "-" : ($hostMailSystem["eximVersion"] == $hostMailSystem["systemVersion"] ? "OK" : "Out of date"))
		);
	}
	return listTable(array("Hostname", "Primary", "Dovecot", "Exim"), $rows, "sortable list", "Hosts in mailsystem $mailSystemName");
}

function nameSystemDetail($nameSystemID)
{
	$nameSystem = $GLOBALS["database"]->stdGet("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), array("name", "description"));
	return summaryTable("Namesystem {$nameSystem["name"]}", array(
		"Name"=>$nameSystem["name"],
		"Description"=>$nameSystem["description"]
		));
}

function nameSystemCustomersList($nameSystemID)
{
	$nameSystemName = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$nameSystemID), "name");
	$customers = $GLOBALS["database"]->stdList("adminCustomer", array("nameSystemID"=>$nameSystemID), array("customerID", "name", "initials", "lastName", "email"), array("name"=>"ASC"));
	return customerList("namesystem $nameSystemName", $customers);
}

function nameSystemHostList($nameSystemID)
{
	$nameSystemName = $GLOBALS["database"]->stdGet("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), "name");
	$rows = array();
	foreach(hostNameSystems(null, $nameSystemID) as $hostNameSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$hostNameSystem["hostID"]}", "text"=>$hostNameSystem["hostname"]),
			($hostNameSystem["hostVersion"] === null ? "-" : ($hostNameSystem["hostVersion"] == $hostNameSystem["systemVersion"] ? "OK" : "Out of date"))
		);
	}
	return listTable(array("Hostname", "Bind"), $rows, "sortable list", "Hosts in namesystem $nameSystemName");
}

function hostDetail($hostID)
{
	$host = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), array("hostname", "sshPort", "description"));
	return summaryTable("Host {$host["hostname"]}", array(
		"Hostname"=>$host["hostname"],
		"SSH port"=>$host["sshPort"],
		"Description"=>$host["description"]));
}

function hostFileSystemList($hostID)
{
	$hostname = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), "hostname");
	$rows = array();
	foreach(hostFileSystems($hostID, null) as $hostFileSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$hostFileSystem["fileSystemID"]}", "text"=>$hostFileSystem["name"]),
			($hostFileSystem["allowCustomerLogin"] == 1 ? "Yes" : "No"),
			($hostFileSystem["mountVersion"] === null ? "-" : ($hostFileSystem["mountVersion"] == $hostFileSystem["fileSystemVersion"] ? "OK" : "Out of date")),
			($hostFileSystem["webserverVersion"] === null ? "-" : ($hostFileSystem["webserverVersion"] == $hostFileSystem["httpVersion"] ? "OK" : "Out of date"))
		);
	}
	return listTable(array("Name", "Login", "Mount", "Webhosting"), $rows, "sortable list", "Filesystems used by host $hostname");
}

function hostMailSystemList($hostID)
{
	$hostname = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), "hostname");
	$rows = array();
	foreach(hostMailSystems($hostID, null) as $hostMailSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id={$hostMailSystem["mailSystemID"]}", "text"=>$hostMailSystem["name"]),
			($hostMailSystem["primary"] == 1 ? "Yes" : "No"),
			($hostMailSystem["dovecotVersion"] === null ? "-" : ($hostMailSystem["dovecotVersion"] == $hostMailSystem["systemVersion"] ? "OK" : "Out of date")),
			($hostMailSystem["eximVersion"] === null ? "-" : ($hostMailSystem["eximVersion"] == $hostMailSystem["systemVersion"] ? "OK" : "Out of date"))
		);
	}
	return listTable(array("Hostname", "Primary", "Dovecot", "Exim"), $rows, "sortable list", "Mailsystems used by host $hostname");
}

function hostNameSystemList($hostID)
{
	$hostname = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), "hostname");
	$rows = array();
	foreach(hostNameSystems($hostID, null) as $hostNameSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/namesystem.php?id={$hostNameSystem["nameSystemID"]}", "text"=>$hostNameSystem["name"]),
			($hostNameSystem["hostVersion"] === null ? "-" : ($hostNameSystem["hostVersion"] == $hostNameSystem["systemVersion"] ? "OK" : "Out of date"))
		);
	}
	return listTable(array("Hostname", "Bind"), $rows, "sortable list", "Namesystems used by host $hostname");
}

function fileSystemRefreshForm($fileSystemID)
{
	$fileSystemName = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "name");
	return operationForm("filesystem.php?id=$fileSystemID", "", "Refresh filesystem $fileSystemName", null, array(
		array("type"=>"submit", "name"=>"refreshall", "label"=>"Refresh everything"),
		array("type"=>"submit", "name"=>"refreshmount", "label"=>"Refresh mounts"),
		array("type"=>"submit", "name"=>"refreshwebserver", "label"=>"Refresh webservers")
	), null);
}

function mailSystemRefreshForm($mailSystemID)
{
	$mailSystemName = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "name");
	return operationForm("mailsystem.php?id=$mailSystemID", "", "Refresh mailsystem $mailSystemName", null, array(
		array("type"=>"submit", "name"=>"refreshall", "label"=>"Refresh everything"),
		array("type"=>"submit", "name"=>"refreshdovecot", "label"=>"Refresh dovecot"),
		array("type"=>"submit", "name"=>"refreshexim", "label"=>"Refresh exim")
	), null);
}

function nameSystemRefreshForm($nameSystemID)
{
	$nameSystemName = $GLOBALS["database"]->stdGet("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), "name");
	return operationForm("namesystem.php?id=$nameSystemID", "", "Refresh namesystem $nameSystemName", null, array(
		array("type"=>"submit", "name"=>"refreshall", "label"=>"Refresh everything"),
		array("type"=>"submit", "name"=>"refreshbind", "label"=>"Refresh bind")
	), null);
}

function hostRefreshForm($hostID)
{
	$hostname = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), "hostname");
	return operationForm("host.php?id=$hostID", "", "Refresh host $hostname", null, array(
		array("type"=>"submit", "name"=>"refreshall", "label"=>"Refresh everything"),
		array("type"=>"submit", "name"=>"refreshmount", "label"=>"Refresh mounts"),
		array("type"=>"submit", "name"=>"refreshwebserver", "label"=>"Refresh webservers"),
		array("type"=>"submit", "name"=>"refreshdovecot", "label"=>"Refresh dovecot"),
		array("type"=>"submit", "name"=>"refreshexim", "label"=>"Refresh exim"),
		array("type"=>"submit", "name"=>"refreshbind", "label"=>"Refresh bind")
	), null);
}

function hostFileSystems($hostID = null, $fileSystemID = null)
{
	return $GLOBALS["database"]->query("SELECT host.hostID, host.hostname, fileSystem.fileSystemID, fileSystem.name, fileSystem.description, fileSystem.fileSystemVersion, fileSystem.httpVersion, mount.version AS mountVersion, mount.allowCustomerLogin, webServer.version AS webserverVersion 
		FROM infrastructureHost AS host 
		CROSS JOIN infrastructureFileSystem AS fileSystem 
		LEFT JOIN infrastructureMount AS mount ON mount.hostID = host.hostID AND mount.fileSystemID = fileSystem.fileSystemID 
		LEFT JOIN infrastructureWebServer AS webServer ON webServer.hostID = host.hostID AND webServer.fileSystemID = fileSystem.fileSystemID 
		WHERE (fileSystem.fileSystemID IN (SELECT fileSystemID FROM infrastructureMount WHERE infrastructureMount.hostID = host.hostID) 
		OR fileSystem.fileSystemID IN (SELECT fileSystemID FROM infrastructureWebServer WHERE infrastructureWebServer.hostID = host.hostID))"
		. ($hostID === null ? "" : " AND host.hostID = $hostID")
		. ($fileSystemID === null ? "" : " AND fileSystem.fileSystemID = $fileSystemID")
	)->fetchList();
}

function hostMailSystems($hostID = null, $mailSystemID = null)
{
	return $GLOBALS["database"]->query("SELECT host.hostID, host.hostname, mailSystem.mailSystemID, mailSystem.name, mailSystem.description, mailSystem.version AS systemVersion, mailServer.dovecotVersion, mailServer.eximVersion, mailServer.primary
		FROM infrastructureMailSystem AS mailSystem 
		LEFT JOIN infrastructureMailServer AS mailServer ON mailServer.mailSystemID = mailSystem.mailSystemID 
		LEFT JOIN infrastructureHost AS host ON mailServer.hostID = host.hostID"
		. ($hostID === null ? "" : " WHERE host.hostID = $hostID")
		. ($mailSystemID == null ? "" : " WHERE mailSystem.mailSystemID = $mailSystemID")
		. " ORDER BY mailServer.primary DESC, host.hostname ASC"
	)->fetchList();
}

function hostNameSystems($hostID = null, $nameSystemID = null)
{
	return $GLOBALS["database"]->query("SELECT host.hostID, host.hostname, nameSystem.nameSystemID, nameSystem.name, nameSystem.description, nameSystem.version AS systemVersion, nameServer.version AS hostVersion
		FROM infrastructureNameSystem AS nameSystem 
		LEFT JOIN infrastructureNameServer AS nameServer USING(nameSystemID)
		LEFT JOIN infrastructureHost AS host USING(hostID)"
		. ($hostID === null ? "" : " WHERE host.hostID = $hostID")
		. ($nameSystemID === null ? "" : " WHERE nameSystem.nameSystemID = $nameSystemID")
		. " ORDER BY host.hostname"
	)->fetchList();
}

function refreshHostMount($hostID)
{
	$GLOBALS["database"]->stdSet("infrastructureMount", array("hostID"=>$hostID), array("version"=>-1));
	updateHosts(array($hostID), "update-treva-passwd --force");
}

function refreshHostWebServer($hostID)
{
	$GLOBALS["database"]->stdSet("infrastructureWebServer", array("hostID"=>$hostID), array("version"=>-1));
	updateHosts(array($hostID), "update-treva-apache --force");
}

function refreshHostDovecot($hostID)
{
	$GLOBALS["database"]->stdSet("infrastructureMailServer", array("hostID"=>$hostID), array("dovecotVersion"=>-1));
	updateHosts(array($hostID), "update-treva-dovecot --force");
}

function refreshHostExim($hostID)
{
	$GLOBALS["database"]->stdSet("infrastructureMailServer", array("hostID"=>$hostID), array("eximVersion"=>-1));
	updateHosts(array($hostID), "update-treva-exim --force");
}

function refreshHostBind($hostID)
{
	$GLOBALS["database"]->stdSet("infrastructureNameServer", array("hostID"=>$hostID), array("version"=>-1));
	updateHosts(array($hostID), "update-treva-bind --force");
}

function refreshFileSystemMount($fileSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureMount", array("fileSystemID"=>$fileSystemID), "hostID");
	$GLOBALS["database"]->stdSet("infrastructureMount", array("fileSystemID"=>$fileSystemID), array("version"=>-1));
	updateHosts($hosts, "update-treva-passwd --force");
}

function refreshFileSystemWebServer($fileSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureWebServer", array("fileSystemID"=>$fileSystemID), "hostID");
	$GLOBALS["database"]->stdSet("infrastructureWebServer", array("fileSystemID"=>$fileSystemID), array("version"=>-1));
	updateHosts($hosts, "update-treva-apache --force");
}

function refreshMailSystemDovecot($mailSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), "hostID");
	$GLOBALS["database"]->stdSet("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), array("dovecotVersion"=>-1));
	updateHosts($hosts, "update-treva-dovecot --force");
}

function refreshMailSystemExim($mailSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), "hostID");
	$GLOBALS["database"]->stdSet("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), array("eximVersion"=>-1));
	updateHosts($hosts, "update-treva-dovecot --force");
}

function refreshNameSystem($nameSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureNameServer", array("nameSystemID"=>$nameSystemID), "hostID");
	$GLOBALS["database"]->stdSet("infrastructureNameServer", array("nameSystemID"=>$nameSystemID), array("version"=>-1));
	updateHosts($hosts, "update-treva-bind --force");
}

?>