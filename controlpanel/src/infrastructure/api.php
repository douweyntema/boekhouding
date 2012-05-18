<?php

$infrastructureTitle = "Infrastructure";
$infrastructureTarget = "admin";

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