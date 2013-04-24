<?php

$infrastructureTitle = "Infrastructure";
$infrastructureTarget = "admin";

function infrastructureOverview()
{
	if(customerID() == 0) {
		return;
	}
	$customer = stdGet("adminCustomer", array("customerID"=>customerID()), array("fileSystemID", "mailSystemID", "webmail"));
	$mailsystem = stdGet("infrastructureMailSystem", array("mailSystemID"=>$customer["mailSystemID"]), array("incomingServer", "outgoingServer"));
	$filesystem = stdGet("infrastructureFileSystem", array("fileSystemID"=>$customer["fileSystemID"]), array("ftpServer", "databaseServer"));
	
	$mailcount = stdCount("mailDomain", array("customerID"=>customerID()));
	
	return summaryTable("Server information", array(
		$mailcount == 0 || $customer["webmail"] === null || $customer["webmail"] == "" ? null : "Webmail"=>array("html"=>$customer["webmail"], "url"=>"http://" . $customer["webmail"]),
		$mailcount == 0 ? null : "Incoming mailserver"=>$mailsystem["incomingServer"],
		$mailcount == 0 ? null : "Outgoing mailserver"=>$mailsystem["outgoingServer"],
		"FTP server"=>$filesystem["ftpServer"],
		"Database server"=>$filesystem["databaseServer"],
		"Controlpanel"=>array("html"=>"controlpanel.treva.nl", "url"=>"http://controlpanel.treva.nl")
	));
}

function infrastructureRefreshHostMount($hostID)
{
	stdSet("infrastructureMount", array("hostID"=>$hostID), array("version"=>-1));
	updateHosts(array($hostID), "update-treva-passwd --force");
}

function infrastructureRefreshHostWebServer($hostID)
{
	stdSet("infrastructureWebServer", array("hostID"=>$hostID), array("version"=>-1));
	updateHosts(array($hostID), "update-treva-apache --force");
}

function infrastructureRefreshHostDovecot($hostID)
{
	stdSet("infrastructureMailServer", array("hostID"=>$hostID), array("dovecotVersion"=>-1));
	updateHosts(array($hostID), "update-treva-dovecot --force");
}

function infrastructureRefreshHostExim($hostID)
{
	stdSet("infrastructureMailServer", array("hostID"=>$hostID), array("eximVersion"=>-1));
	updateHosts(array($hostID), "update-treva-exim --force");
}

function infrastructureRefreshHostBind($hostID)
{
	stdSet("infrastructureNameServer", array("hostID"=>$hostID), array("version"=>-1));
	updateHosts(array($hostID), "update-treva-bind --force");
}

function infrastructureRefreshFileSystemMount($fileSystemID)
{
	$hosts = stdList("infrastructureMount", array("fileSystemID"=>$fileSystemID), "hostID");
	stdSet("infrastructureMount", array("fileSystemID"=>$fileSystemID), array("version"=>-1));
	updateHosts($hosts, "update-treva-passwd --force");
}

function infrastructureRefreshFileSystemWebServer($fileSystemID)
{
	$hosts = stdList("infrastructureWebServer", array("fileSystemID"=>$fileSystemID), "hostID");
	stdSet("infrastructureWebServer", array("fileSystemID"=>$fileSystemID), array("version"=>-1));
	updateHosts($hosts, "update-treva-apache --force");
}

function infrastructureRefreshMailSystemDovecot($mailSystemID)
{
	$hosts = stdList("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), "hostID");
	stdSet("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), array("dovecotVersion"=>-1));
	updateHosts($hosts, "update-treva-dovecot --force");
}

function infrastructureRefreshMailSystemExim($mailSystemID)
{
	$hosts = stdList("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), "hostID");
	stdSet("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), array("eximVersion"=>-1));
	updateHosts($hosts, "update-treva-exim --force");
}

function infrastructureRefreshNameSystem($nameSystemID)
{
	$hosts = stdList("infrastructureNameServer", array("nameSystemID"=>$nameSystemID), "hostID");
	stdSet("infrastructureNameServer", array("nameSystemID"=>$nameSystemID), array("version"=>-1));
	updateHosts($hosts, "update-treva-bind --force");
}
?>