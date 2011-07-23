<?php

require_once("common.php");

function main()
{
	doMail();
	
	$content = "<h1>New domain</h1>\n";
	
	$content .= mailBreadcrumbs(array(array("name"=>"Add domain", "url"=>"{$GLOBALS["root"]}mail/adddomain.php")));
	
	$domainName = post("domainName");
	
	if(!validDomain($domainName)) {
		$content .= addMailDomainForm("Invalid domain name", $domainName);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= addMailDomainForm(null, $domainName);
		die(page($content));
	}
	
	$domainID = $GLOBALS["database"]->stdNew("mailDomain", array("customerID"=>customerID(), "name"=>$domainName));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id=$domainID");
}

main();

?>