<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomain($domainID);
	$domainName = domainsFormatDomainName($domainID);
	
	$content = "<h1>Domain " . $domainName . "</h1>\n";
	
// 	$content .= domainBreadcrumbs($domainID);
	
	$expiredate = domainsDomainExpiredate($domainID);
	if(get("action") == "disable") {
		checkTrivialAction($content, "{$GLOBALS["root"]}domains/expiredomain.php?id=$domainID&action=disable", "Withdraw domain", null, "<p>This will cause the domain to expire on $expiredate.</p>");
		domainsDisableAutoRenew($domainID);
	} else if(get("action") == "enable") {
		checkTrivialAction($content, "{$GLOBALS["root"]}domains/expiredomain.php?id=$domainID&action=enable", "Restore domain", null, "<p>This will retain the domain indefinitely.</p></p><p>If the domain is not restored, it will expire on $expiredate.</p>");
		domainsEnableAutoRenew($domainID);
	} else if(get("action") == "delete") {
		checkTrivialAction($content, "{$GLOBALS["root"]}domains/expiredomain.php?id=$domainID&action=delete", "Delete domain", null, "<p>This will delete the domain immediately.</p>");
		domainsDisableAutoRenew($domainID);
	}
	
	header("HTTP/1.1 303 See Other");
	if(get("action") == "delete") {
		header("Location: {$GLOBALS["root"]}domains/");
	} else {
		header("Location: {$GLOBALS["root"]}domains/domain.php?id=$domainID");
	}
}

main();

?>