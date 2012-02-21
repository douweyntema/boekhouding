<?php

require_once("common.php");

function main()
{
	$listID = get("id");
	doMailList($listID);
	
	$list = $GLOBALS["database"]->stdGetTry("mailList", array("listID"=>$listID), array("domainID", "localpart"), false);
	
	if($list === false) {
		listNotFound($listID);
	}
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$list["domainID"]), "name");
	
	$content = "<h1>Mailinglist {$list["localpart"]}@$domain</h1>\n";
	
	$content .= domainBreadcrumbs($list["domainID"], array(array("name"=>"Mailinglist {$list["localpart"]}@{$domain}", "url"=>"{$GLOBALS["root"]}mail/list.php?id=$listID")));
	
	checkTrivialAction($content, "{$GLOBALS["root"]}mail/removelist.php?id=$listID", "Remove mailinglist", "Are you sure you want to remove this mailinglist?");
	
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdDel("mailListMember", array("listID"=>$listID));
	$GLOBALS["database"]->stdDel("mailList", array("listID"=>$listID));
	$GLOBALS["database"]->commitTransaction();
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id={$list["domainID"]}");
}

main();

?>