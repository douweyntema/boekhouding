<?php

require_once("common.php");

function main()
{
	doDomains();
	
// 	$content .= breadcrumbs($domainID, array(array("name"=>"Add alias", "url"=>"{$GLOBALS["root"]}mail/addalias.php?id=$domainID")));
	
	$domainName = post("name");
	$tldID = post("rootDomainID");
	$tld = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$tldID), "name");
	
	$content = "<h1>Register new domain - $domainName.$tld</h1>\n";
	
	if(!validDomainPart($domainName)) {
		$content .= addDomainForm("Invalid domain name.", $tldID, $domainName);
		die(page($content));
	}
	
	if($GLOBALS["database"]->stdExists("dnsDomain", array("parentDomainID"=>$tldID, "name"=>$domainName))) {
		$content .= addDomainForm("The chosen domain name is already registered.", $tldID, $domainName);
		die(page($content));
	}
	
	if(!domainsDomainAvailable($domainName . "." . $tld)) {
		$content .= addDomainForm("The chosen domain name is already registered.", $tldID, $domainName);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= addDomainForm(null, $tldID, $domainName);
		die(page($content));
	}
	
	$GLOBALS["database"]->startTransaction();
	$domainID = $GLOBALS["database"]->stdNew("dnsDomain", array("customerID"=>customerID(), "parentDomainID"=>$tldID, "name"=>$domainName));
	
	domainsUpdate(customerID());
	
	$ok = domainsRegisterDomain(customerID(), $domainName, $tld);
	if(!$ok) {
		$content .= "<p class=\"error\">An error occured while registering this domain. Please try again later or <a href=\"{$GLOBALS["root"]}ticket/addthread.php\">contact us</a>.</p>";
		$GLOBALS["database"]->rollbackTransaction();
		die(page($content));
	}
	$GLOBALS["database"]->commitTransaction();
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id={$domainID}");
}

main();

?>