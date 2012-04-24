<?php

require_once("common.php");

function main()
{
	$addressID = get("id");
	doMailAddress($addressID);
	
	$content = mailboxHeader($addressID);
	$content .= mailboxSummary($addressID);
	$content .= editMailboxPasswordForm($addressID);
	$content .= editMailboxForm($addressID);
	$content .= removeMailboxForm($addressID);
	echo page($content);
}

main();

?>