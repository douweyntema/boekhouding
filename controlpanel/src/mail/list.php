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
	
	$content .= mailListMemberList($listID);
	$content .= addMailListMemberForm($listID, "", null);
	$content .= editMailListForm($listID, "", $list["localpart"]);
	$content .= editMailListMemberForm($listID, "STUB");
	$content .= trivialActionForm("{$GLOBALS["root"]}mail/removelist.php?id=$listID", "", "Remove mailinglist");
	
	echo page($content);
}

main();

?>