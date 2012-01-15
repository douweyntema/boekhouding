<?php

require_once("common.php");

function main()
{
	doDomains();
	
	$domainName = post("name");
	$tldID = post("tldID");
	
	if($tldID == null || $domainName == null) {
		$content = "<h1>Register new domain</h1>\n";
		
		$content .= breadcrumbs(array(array("name"=>"Domains", "url"=>"{$GLOBALS["root"]}domains/"), array("name"=>"Register domain", "url"=>"{$GLOBALS["root"]}domains/adddomain.php")));
		$content .= addDomainForm();
		die(page($content));
	}
	
	$tldName = $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$tldID), "name");
	
	$content = "<h1>Register new domain - $domainName.$tldName</h1>\n";
	
	$content .= breadcrumbs(array(array("name"=>"Domains", "url"=>"{$GLOBALS["root"]}domains/"), array("name"=>"Register domain $domainName.$tldName", "url"=>"{$GLOBALS["root"]}domains/adddomain.php")));
	
	if(!validDomainPart($domainName)) {
		$content .= addDomainForm("Invalid domain name.", $tldID, $domainName);
		die(page($content));
	}
	
	if($GLOBALS["database"]->stdExists("dnsDomain", array("domainTldID"=>$tldID, "name"=>$domainName))) {
		$content .= addDomainForm("The chosen domain name is already registered.", $tldID, $domainName);
		die(page($content));
	}
	
	if(!domainsDomainAvailable($domainName, $tldID)) {
		$content .= addDomainForm("The chosen domain name is already registered.", $tldID, $domainName);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= addDomainForm(null, $tldID, $domainName);
		die(page($content));
	}
	
	$GLOBALS["database"]->startTransaction();
	$domainID = $GLOBALS["database"]->stdNew("dnsDomain", array("customerID"=>customerID(), "domainTldID"=>$tldID, "name"=>$domainName, "addressType"=>"NONE", "mailType"=>"NONE"));
	
	$ok = domainsRegisterDomain(customerID(), $domainName, $tldID);
	if(!$ok) {
		$content .= "<p class=\"error\">An error occured while registering this domain. Please try again later or <a href=\"{$GLOBALS["root"]}ticket/addthread.php\">contact us</a>.</p>";
		$GLOBALS["database"]->rollbackTransaction();
		die(page($content));
	}
	$GLOBALS["database"]->commitTransaction();
	
	updateDomains(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id={$domainID}");
}

main();

?>