<?php

require_once("common.php");

function main()
{
	$listID = get("id");
	$localpart = $GLOBALS["database"]->stdGet("mailList", array("listID"=>$listID), "localpart");
	$domainID = $GLOBALS["database"]->stdGet("mailList", array("listID"=>$listID), "domainID");
	
	doMailDomain($domainID);
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	$content = "<h1>Mailinglist {$localpart}@$domain</h1>\n";
	
	$content .= domainBreadcrumbs($domainID, array(array("name"=>"Mailinglist {$localpart}@{$domain}", "url"=>"{$GLOBALS["root"]}mail/list.php?id=$listID")));
	
	if(post("members") === null) {
		$content .= editMailListMemberForm($listID);
		die(page($content));
	}
	
	$members = explode("\n", post("members"));
	
	foreach($members as $member) {
		if(trim($member) == "") {
			continue;
		}
		if(!validEmail($member)) {
			$content .= editMailListMemberForm($listID, "Invalid member address ($member)", $members);
			die(page($content));
		}
	}
	
	if(post("confirm") === null) {
		$content .= editMailListMemberForm($listID, null, $members);
		die(page($content));
	}
	
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdDel("mailListMember", array("listID"=>$listID));
	foreach($members as $member) {
		if(trim($member) == "") {
			continue;
		}
		if($GLOBALS["database"]->stdExists("mailListMember", array("listID"=>$listID, "targetAddress"=>$member))) {
			continue;
		}
		$GLOBALS["database"]->stdNew("mailListMember", array("listID"=>$listID, "targetAddress"=>$member));
	}
	$GLOBALS["database"]->commitTransaction();
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/list.php?id={$listID}");
}

main();

?>