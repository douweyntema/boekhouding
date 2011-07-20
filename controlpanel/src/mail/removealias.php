<?php

require_once("common.php");

function main()
{
	$aliasID = get("id");
	doMailAlias($aliasID);
	
	$alias = $GLOBALS["database"]->stdGetTry("mailAlias", array("aliasID"=>$aliasID), array("domainID", "localpart", "targetAddress"), false);
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$alias["domainID"]), "name");
	
	$content = "<h1>Alias {$alias["localpart"]}@$domain</h1>\n";
	
	$content .= domainBreadcrumbs($alias["domainID"], array(array("name"=>"Alias {$alias["localpart"]}@{$domain}", "url"=>"{$GLOBALS["root"]}mail/alias.php?id=$aliasID")));
	
	if(post("confirm") === null) {
		$content .= removeMailAliasForm($aliasID, null);
		die(page($content));
	}
	
	$GLOBALS["database"]->stdDel("mailAlias", array("aliasID"=>$aliasID));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id={$alias["domainID"]}");
}

main();

?>