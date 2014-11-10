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
	foreach(stdList("infrastructureFileSystem", array(), array("fileSystemID", "name", "description"), array("name"=>"ASC")) as $fileSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$fileSystem["fileSystemID"]}", "text"=>$fileSystem["name"]),
			$fileSystem["description"]
		);
	}
	return listTable(array("Name", "Description"), $rows, "Filesystems", true, "sortable list");
}

function mailSystemList()
{
	$rows = array();
	foreach(stdList("infrastructureMailSystem", array(), array("mailSystemID", "name", "description"), array("name"=>"ASC")) as $mailSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id={$mailSystem["mailSystemID"]}", "text"=>$mailSystem["name"]),
			$mailSystem["description"]
		);
	}
	return listTable(array("Name", "Description"), $rows, "Mailsystems", true, "sortable list");
}

function nameSystemList()
{
	$rows = array();
	foreach(stdList("infrastructureNameSystem", array(), array("nameSystemID", "name", "description"), array("name"=>"ASC")) as $nameSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/namesystem.php?id={$nameSystem["nameSystemID"]}", "text"=>$nameSystem["name"]),
			$nameSystem["description"]
		);
	}
	return listTable(array("Name", "Description"), $rows, "Namesystems", true, "sortable list");
}

function hostList()
{
	$rows = array();
	foreach(stdList("infrastructureHost", array(), array("hostID", "hostname", "description"), array("hostname"=>"ASC")) as $host) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$host["hostID"]}", "text"=>$host["hostname"]),
			$host["description"]
		);
	}
	return listTable(array("Name", "Description"), $rows, "Hosts", true, "sortable list");
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
	return listTable(array("Nickname", "Name", "Email"), $rows, "Customers using $entityName", true, "sortable list");
}

function fileSystemDetail($fileSystemID)
{
	$fileSystem = stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), array("name", "description"));
	return summaryTable("Filesystem {$fileSystem["name"]}", array(
		"Name"=>$fileSystem["name"],
		"Description"=>$fileSystem["description"]
		));
}

function fileSystemCustomersList($fileSystemID)
{
	$fileSystemName = stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "name");
	$customers = stdList("adminCustomer", array("fileSystemID"=>$fileSystemID), array("customerID", "name", "initials", "lastName", "email"), array("name"=>"ASC"));
	return customerList("filesystem $fileSystemName", $customers);
}

function fileSystemHostList($fileSystemID)
{
	$fileSystemName = stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "name");
	$rows = array();
	foreach(hostFileSystems(null, $fileSystemID) as $hostFileSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$hostFileSystem["hostID"]}", "text"=>$hostFileSystem["hostname"]),
			($hostFileSystem["allowCustomerLogin"] == 1 ? "Yes" : "No"),
			($hostFileSystem["mountVersion"] === null ? "-" : ($hostFileSystem["mountVersion"] == $hostFileSystem["fileSystemVersion"] ? "OK" : "Out of date")),
			($hostFileSystem["webserverVersion"] === null ? "-" : ($hostFileSystem["webserverVersion"] == $hostFileSystem["httpVersion"] ? "OK" : "Out of date"))
		);
	}
	return listTable(array("Name", "Login", "Mount", "Webhosting"), $rows, "Hosts in filesystem $fileSystemName", true, "sortable list");
}

function mailSystemDetail($mailSystemID)
{
	$mailSystem = stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), array("name", "description"));
	return summaryTable("Mailsystem {$mailSystem["name"]}", array(
		"Name"=>$mailSystem["name"],
		"Description"=>$mailSystem["description"]
		));
}

function mailSystemCustomersList($mailSystemID)
{
	$mailSystemName = stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "name");
	$customers = stdList("adminCustomer", array("mailSystemID"=>$mailSystemID), array("customerID", "name", "initials", "lastName", "email"), array("name"=>"ASC"));
	return customerList("mailsystem $mailSystemName", $customers);
}

function mailSystemHostList($mailSystemID)
{
	$mailSystemName = stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "name");
	$rows = array();
	foreach(hostMailSystems(null, $mailSystemID) as $hostMailSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$hostMailSystem["hostID"]}", "text"=>$hostMailSystem["hostname"]),
			($hostMailSystem["primary"] == 1 ? "Yes" : "No"),
			($hostMailSystem["dovecotVersion"] === null ? "-" : ($hostMailSystem["dovecotVersion"] == $hostMailSystem["systemVersion"] ? "OK" : "Out of date")),
			($hostMailSystem["eximVersion"] === null ? "-" : ($hostMailSystem["eximVersion"] == $hostMailSystem["systemVersion"] ? "OK" : "Out of date"))
		);
	}
	return listTable(array("Hostname", "Primary", "Dovecot", "Exim"), $rows, "Hosts in mailsystem $mailSystemName", true, "sortable list");
}

function nameSystemDetail($nameSystemID)
{
	$nameSystem = stdGet("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), array("name", "description"));
	return summaryTable("Namesystem {$nameSystem["name"]}", array(
		"Name"=>$nameSystem["name"],
		"Description"=>$nameSystem["description"]
		));
}

function nameSystemCustomersList($nameSystemID)
{
	$nameSystemName = stdGet("infrastructureMailSystem", array("mailSystemID"=>$nameSystemID), "name");
	$customers = stdList("adminCustomer", array("nameSystemID"=>$nameSystemID), array("customerID", "name", "initials", "lastName", "email"), array("name"=>"ASC"));
	return customerList("namesystem $nameSystemName", $customers);
}

function nameSystemHostList($nameSystemID)
{
	$nameSystemName = stdGet("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), "name");
	$rows = array();
	foreach(hostNameSystems(null, $nameSystemID) as $hostNameSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/host.php?id={$hostNameSystem["hostID"]}", "text"=>$hostNameSystem["hostname"]),
			($hostNameSystem["hostVersion"] === null ? "-" : ($hostNameSystem["hostVersion"] == $hostNameSystem["systemVersion"] ? "OK" : "Out of date"))
		);
	}
	return listTable(array("Hostname", "Bind"), $rows, "Hosts in namesystem $nameSystemName", true, "sortable list");
}

function hostDetail($hostID)
{
	$host = stdGet("infrastructureHost", array("hostID"=>$hostID), array("hostname", "sshPort", "description"));
	return summaryTable("Host {$host["hostname"]}", array(
		"Hostname"=>$host["hostname"],
		"SSH port"=>$host["sshPort"],
		"Description"=>$host["description"]));
}

function hostFileSystemList($hostID)
{
	$hostname = stdGet("infrastructureHost", array("hostID"=>$hostID), "hostname");
	$rows = array();
	foreach(hostFileSystems($hostID, null) as $hostFileSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/filesystem.php?id={$hostFileSystem["fileSystemID"]}", "text"=>$hostFileSystem["name"]),
			($hostFileSystem["allowCustomerLogin"] == 1 ? "Yes" : "No"),
			($hostFileSystem["mountVersion"] === null ? "-" : ($hostFileSystem["mountVersion"] == $hostFileSystem["fileSystemVersion"] ? "OK" : "Out of date")),
			($hostFileSystem["webserverVersion"] === null ? "-" : ($hostFileSystem["webserverVersion"] == $hostFileSystem["httpVersion"] ? "OK" : "Out of date"))
		);
	}
	return listTable(array("Name", "Login", "Mount", "Webhosting"), $rows, "Filesystems used by host $hostname", true, "sortable list");
}

function hostMailSystemList($hostID)
{
	$hostname = stdGet("infrastructureHost", array("hostID"=>$hostID), "hostname");
	$rows = array();
	foreach(hostMailSystems($hostID, null) as $hostMailSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/mailsystem.php?id={$hostMailSystem["mailSystemID"]}", "text"=>$hostMailSystem["name"]),
			($hostMailSystem["primary"] == 1 ? "Yes" : "No"),
			($hostMailSystem["dovecotVersion"] === null ? "-" : ($hostMailSystem["dovecotVersion"] == $hostMailSystem["systemVersion"] ? "OK" : "Out of date")),
			($hostMailSystem["eximVersion"] === null ? "-" : ($hostMailSystem["eximVersion"] == $hostMailSystem["systemVersion"] ? "OK" : "Out of date"))
		);
	}
	return listTable(array("Name", "Primary", "Dovecot", "Exim"), $rows, "Mailsystems used by host $hostname", true, "sortable list");
}

function hostNameSystemList($hostID)
{
	$hostname = stdGet("infrastructureHost", array("hostID"=>$hostID), "hostname");
	$rows = array();
	foreach(hostNameSystems($hostID, null) as $hostNameSystem) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}infrastructure/namesystem.php?id={$hostNameSystem["nameSystemID"]}", "text"=>$hostNameSystem["name"]),
			($hostNameSystem["hostVersion"] === null ? "-" : ($hostNameSystem["hostVersion"] == $hostNameSystem["systemVersion"] ? "OK" : "Out of date"))
		);
	}
	return listTable(array("Name", "Bind"), $rows, "Namesystems used by host $hostname", true, "sortable list");
}

function fileSystemRefreshForm($fileSystemID)
{
	$fileSystemName = stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "name");
	return operationForm("filesystem.php?id=$fileSystemID", "", "Refresh filesystem $fileSystemName", null, array(
		array("type"=>"submit", "name"=>"refreshall", "label"=>"Refresh everything"),
		array("type"=>"submit", "name"=>"refreshmount", "label"=>"Refresh mounts"),
		array("type"=>"submit", "name"=>"refreshwebserver", "label"=>"Refresh webservers")
	), null);
}

function mailSystemRefreshForm($mailSystemID)
{
	$mailSystemName = stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "name");
	return operationForm("mailsystem.php?id=$mailSystemID", "", "Refresh mailsystem $mailSystemName", null, array(
		array("type"=>"submit", "name"=>"refreshall", "label"=>"Refresh everything"),
		array("type"=>"submit", "name"=>"refreshdovecot", "label"=>"Refresh dovecot"),
		array("type"=>"submit", "name"=>"refreshexim", "label"=>"Refresh exim")
	), null);
}

function nameSystemRefreshForm($nameSystemID)
{
	$nameSystemName = stdGet("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), "name");
	return operationForm("namesystem.php?id=$nameSystemID", "", "Refresh namesystem $nameSystemName", null, array(
		array("type"=>"submit", "name"=>"refreshall", "label"=>"Refresh everything"),
		array("type"=>"submit", "name"=>"refreshbind", "label"=>"Refresh bind")
	), null);
}

function hostRefreshForm($hostID)
{
	$hostname = stdGet("infrastructureHost", array("hostID"=>$hostID), "hostname");
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
	return query("SELECT host.hostID, host.hostname, fileSystem.fileSystemID, fileSystem.name, fileSystem.description, fileSystem.fileSystemVersion, fileSystem.httpVersion, mount.version AS mountVersion, mount.allowCustomerLogin, webServer.version AS webserverVersion 
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
	return query("SELECT host.hostID, host.hostname, mailSystem.mailSystemID, mailSystem.name, mailSystem.description, mailSystem.version AS systemVersion, mailServer.dovecotVersion, mailServer.eximVersion, mailServer.primary
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
	return query("SELECT host.hostID, host.hostname, nameSystem.nameSystemID, nameSystem.name, nameSystem.description, nameSystem.version AS systemVersion, nameServer.version AS hostVersion
		FROM infrastructureNameSystem AS nameSystem 
		LEFT JOIN infrastructureNameServer AS nameServer USING(nameSystemID)
		LEFT JOIN infrastructureHost AS host USING(hostID)"
		. ($hostID === null ? "" : " WHERE host.hostID = $hostID")
		. ($nameSystemID === null ? "" : " WHERE nameSystem.nameSystemID = $nameSystemID")
		. " ORDER BY host.hostname"
	)->fetchList();
}

?>