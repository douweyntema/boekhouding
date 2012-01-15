<?php

$domainsTitle = "Domains";
$domainsDescription = "Domains";
$domainsTarget = "customer";

function domainsRegisterDomain($customerID, $domainName, $tldID)
{
	return getApi($tldID)->registerDomain($customerID, $domainName, $tldID);
}

function domainsDisableAutoRenew($domainID)
{
	return getApi(getTldID($domainID))->disableAutoRenew($domainID);
}

function domainsEnableAutoRenew($domainID)
{
	return getApi(getTldID($domainID))->enableAutoRenew($domainID);
}

function domainsDomainStatus($domainID)
{
	try {
		return getApi(getTldID($domainID))->domainStatus($domainID);
	} catch(DomainsNoApiException $e) {
		return "Externally hosted";
	}
}

function domainsDomainExpiredate($domainID)
{
	try {
		return getApi(getTldID($domainID))->domainExpiredate($domainID);
	} catch(DomainsNoApiException $e) {
		return "Unknown";
	}
}

function domainsDomainAutorenew($domainID)
{
	try {
		return getApi(getTldID($domainID))->domainAutorenew($domainID);
	} catch(DomainsNoApiException $e) {
		return null;
	}
}

function domainsDomainAvailable($domainName, $tldID)
{
	return getApi($tldID)->domainAvailable($domainName, $tldID);
}

function domainsUpdateContactInfo()
{
	getApiName("mijndomeinreseller")->updateContactInfo();
}

function domainsDomainAutorenewDescription($domainID)
{
	$autorenew = domainsDomainAutorenew($domainID);
	if($autorenew === null) {
		return "Unknown";
	} else if($autorenew) {
		return "Enabled";
	} else {
		return "Disabled";
	}
}

function domainsDomainStatusDescription($domainID)
{
	$status = domainsDomainStatus($domainID);
	if($status == "active") {
		if(domainsDomainAutorenew($domainID)) {
			return "Active, renewing at " . domainsDomainExpiredate($domainID);
		} else {
			return "Expiring at " . domainsDomainExpiredate($domainID);
		}
	} else if($status == "requested") {
		return "Requested";
	} else if($status == "expired" || $status == "quarantaine") {
		return "Expired";
	} else if($status == "activeforever") {
		return "Active";
	} else {
		return "Unknown";
	}
}

function updateDomains($customerID)
{
	// TODO
}

$GLOBAL["domainsCachedApis"] = array();

function getApiName($identifier)
{
	if(!isset($GLOBALS["domainsCachedApis"][$identifier])) {
		$parameters = $GLOBALS["database"]->stdGet("infrastructureDomainRegistrar", array("identifier"=>$identifier), "parameters");
		
		$name = $identifier . "api";
		require_once(dirname(__FILE__) . "/" . $name . ".php");
		$GLOBALS["domainsCachedApis"][$identifier] = new $name($parameters);
	}
	return $GLOBALS["domainsCachedApis"][$identifier];
}

function getApi($tldID)
{
	if($tldID === null) {
		throw new DomainsNoApiException();
	}
	$registrar = $GLOBALS["database"]->query("SELECT identifier, parameters FROM infrastructureDomainTld INNER JOIN infrastructureDomainRegistrar USING(domainRegistrarID) WHERE domainTldID=" . $GLOBALS["database"]->addSlashes($tldID) . ";")->fetchArray();
	if(!isset($GLOBALS["domainsCachedApis"][$registrar["identifier"]])) {
		$name = $registrar["identifier"] . "api";
		require_once(dirname(__FILE__) . "/" . $name . ".php");
		$GLOBALS["domainsCachedApis"][$registrar["identifier"]] = new $name($registrar["parameters"]);
	}
	return $GLOBALS["domainsCachedApis"][$registrar["identifier"]];
}

function getTldID($domainID)
{
	return $GLOBALS["database"]->stdGetTry("dnsDomain", array("domainID"=>$domainID), "domainTldID", null);
}

class DomainsNoApiException extends Exception
{
}

?>