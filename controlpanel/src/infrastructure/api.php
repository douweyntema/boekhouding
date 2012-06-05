<?php

$infrastructureTitle = "Infrastructure";
$infrastructureTarget = "admin";

function infrastructureOverview()
{
	if(customerID() == 0) {
		return;
	}
	$customer = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>customerID()), array("fileSystemID", "mailSystemID", "webmail"));
	$mailsystem = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$customer["mailSystemID"]), array("incomingServer", "outgoingServer"));
	$filesystem = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$customer["fileSystemID"]), array("ftpServer", "databaseServer"));
	
	$mailcount = $GLOBALS["database"]->stdCount("mailDomain", array("customerID"=>customerID()));
	
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
	$GLOBALS["database"]->stdSet("infrastructureMount", array("hostID"=>$hostID), array("version"=>-1));
	updateHosts(array($hostID), "update-treva-passwd --force");
}

function infrastructureRefreshHostWebServer($hostID)
{
	$GLOBALS["database"]->stdSet("infrastructureWebServer", array("hostID"=>$hostID), array("version"=>-1));
	updateHosts(array($hostID), "update-treva-apache --force");
}

function infrastructureRefreshHostDovecot($hostID)
{
	$GLOBALS["database"]->stdSet("infrastructureMailServer", array("hostID"=>$hostID), array("dovecotVersion"=>-1));
	updateHosts(array($hostID), "update-treva-dovecot --force");
}

function infrastructureRefreshHostExim($hostID)
{
	$GLOBALS["database"]->stdSet("infrastructureMailServer", array("hostID"=>$hostID), array("eximVersion"=>-1));
	updateHosts(array($hostID), "update-treva-exim --force");
}

function infrastructureRefreshHostBind($hostID)
{
	$GLOBALS["database"]->stdSet("infrastructureNameServer", array("hostID"=>$hostID), array("version"=>-1));
	updateHosts(array($hostID), "update-treva-bind --force");
}

function infrastructureRefreshFileSystemMount($fileSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureMount", array("fileSystemID"=>$fileSystemID), "hostID");
	$GLOBALS["database"]->stdSet("infrastructureMount", array("fileSystemID"=>$fileSystemID), array("version"=>-1));
	updateHosts($hosts, "update-treva-passwd --force");
}

function infrastructureRefreshFileSystemWebServer($fileSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureWebServer", array("fileSystemID"=>$fileSystemID), "hostID");
	$GLOBALS["database"]->stdSet("infrastructureWebServer", array("fileSystemID"=>$fileSystemID), array("version"=>-1));
	updateHosts($hosts, "update-treva-apache --force");
}

function infrastructureRefreshMailSystemDovecot($mailSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), "hostID");
	$GLOBALS["database"]->stdSet("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), array("dovecotVersion"=>-1));
	updateHosts($hosts, "update-treva-dovecot --force");
}

function infrastructureRefreshMailSystemExim($mailSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), "hostID");
	$GLOBALS["database"]->stdSet("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), array("eximVersion"=>-1));
	updateHosts($hosts, "update-treva-dovecot --force");
}

function infrastructureRefreshNameSystem($nameSystemID)
{
	$hosts = $GLOBALS["database"]->stdList("infrastructureNameServer", array("nameSystemID"=>$nameSystemID), "hostID");
	$GLOBALS["database"]->stdSet("infrastructureNameServer", array("nameSystemID"=>$nameSystemID), array("version"=>-1));
	updateHosts($hosts, "update-treva-bind --force");
}
?>