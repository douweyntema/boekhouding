<?php

require_once("common.php");

function main()
{
	$aliasID = get("id");
	doMailAlias($aliasID);
	
	$content = aliasHeader($aliasID);
	$content .= editMailAliasForm($aliasID);
	$content .= removeMailAliasForm($aliasID);
	echo page($content);
}

main();

?>