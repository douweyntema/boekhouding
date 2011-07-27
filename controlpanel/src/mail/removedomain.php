<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	$content = "<h1>Domain $domain</h1>\n";
	
	$content .= domainBreadcrumbs($domainID);
	
	if(count($GLOBALS["database"]->stdList("mailAddress", array("domainID"=>$domainID), "addressID")) > 0) {
		$content .= trivialActionForm("{$GLOBALS["root"]}mail/removedomain.php?id=$domainID", "Unable to remove this domain. There are still mailboxes connected to this domain. Remove them first if you want to remove this domain.", "Remove domain");
		die(page($content));
	}
	
	$aliasses = "";
	$info = "";
	foreach($GLOBALS["database"]->stdList("mailAlias", array("domainID"=>$domainID), "localpart") as $name) {
		$aliasses .= "<li>$name@$domain</li>\n";
	}
	if($aliasses != "") {
		$info = "<p>The following aliasses will also be removed:\n<ul>\n$aliasses</ul>\n</p>";
	}
	
	checkTrivialAction($content, "{$GLOBALS["root"]}mail/removedomain.php?id=$domainID", "Remove domain", "Are you sure you want to remove this domain, and all it's aliasses?", $info);
	
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdDel("mailAlias", array("domainID"=>$domainID));
	$GLOBALS["database"]->stdDel("mailDomain", array("domainID"=>$domainID));
	$GLOBALS["database"]->commitTransaction();
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/");
}

main();

?>