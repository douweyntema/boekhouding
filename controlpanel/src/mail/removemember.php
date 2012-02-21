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
	
	$deleteMembers = array();
	$postData = array();
	$deleteMembersList = "<ul>\n";
	foreach($_POST as $key=>$value) {
		if(substr($key, 0, strlen("member-")) == "member-" && $value == "delete") {
			$memberID = substr($key, strlen("member-"));
			$deleteMembers[] = $memberID;
			$postData[$key] = $value;
			$email = $GLOBALS["database"]->stdGet("mailListMember", array("memberID"=>$memberID), "targetAddress");
			$deleteMembersList .= "<li>$email</li>\n";
		}
	}
	$deleteMembersList .= "</ul>";
	
	checkTrivialAction($content, "{$GLOBALS["root"]}mail/removemember.php?id=$listID", "Remove mailinglist members", "Are you sure you want to remove these members?", $deleteMembersList, null, $postData);
	
	$GLOBALS["database"]->startTransaction();
	foreach($deleteMembers as $memberID) {
		$GLOBALS["database"]->stdDel("mailListMember", array("memberID"=>$memberID));
	}
	$GLOBALS["database"]->commitTransaction();
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/list.php?id=$listID");
}

main();

?>