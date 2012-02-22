<?php

require_once("common.php");

function main()
{
	$listID = get("id");
	doMailList($listID);
	
	$content = listHeader($listID);
	$content .= mailListMemberList($listID);
	$content .= addMailListMemberForm($listID);
	$content .= editMailListForm($listID);
	$content .= editMailListMemberForm($listID, "STUB");
	$content .= trivialActionForm("{$GLOBALS["root"]}mail/removelist.php?id=$listID", "", "Remove mailinglist");
	
	echo page($content);
}

main();

?>