<?php

require_once("common.php");

function main()
{
	$aliasID = get("id");
	doMailAlias($aliasID);
	
	$alias = $GLOBALS["database"]->stdGetTry("mailAlias", array("aliasID"=>$aliasID), array("domainID", "localpart", "targetAddress"), false);
	
	if($alias === false) {
		aliasNotFound($aliasID);
	}
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$alias["domainID"]), "name");
	
	$content = "<h1>Alias {$alias["localpart"]}@$domain</h1>\n";
	
	$content .= domainBreadcrumbs($alias["domainID"], array(array("name"=>"Alias {$alias["localpart"]}@{$domain}", "url"=>"{$GLOBALS["root"]}mail/alias.php?id=$aliasID")));
	
	$content .= editMailAliasForm($aliasID, "", $alias["targetAddress"]);
	$content .= removeMailAliasForm($aliasID, "");
	
	echo page($content);
}

main();

?>