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
	
	$localpart = post("localpart");
	
	if(!validLocalPart($localpart)) {
		$content .= editMailListForm($listID, "Invalid mailinglist address", $localpart);
		die(page($content));
	}
	
	if($GLOBALS["database"]->stdGetTry("mailAddress", array("domainID"=>$list["domainID"], "localpart"=>$localpart), "addressID", null) !== null) {
		$content .= editMailListForm($listID, "A mailbox with the same name already exists", $localpart);
		die(page($content));
	}
	
	if($GLOBALS["database"]->stdGetTry("mailList", array("domainID"=>$list["domainID"], "localpart"=>$localpart), "listID", null) !== null) {
		$content .= editMailListForm($listID, "A mailinglist with the same name already exists", $localpart);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= editMailListForm($listID, null, $localpart);
		die(page($content));
	}
	
	$GLOBALS["database"]->stdSet("mailList", array("listID"=>$listID), array("localpart"=>$localpart));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id={$list["domainID"]}");
}

main();

?>