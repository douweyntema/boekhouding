<?php

$domainsTitle = "Domains";
$domainsDescription = "Domain registrations";
$domainsTarget = "customer";

function updateDomains($customerID)
{
	$nameSystemID = stdGet("adminCustomer", array("customerID"=>$customerID), "nameSystemID");
	stdIncrement("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), "version", 1000000000);
	
	$hosts = stdList("infrastructureNameServer", array("nameSystemID"=>$nameSystemID), "hostID");
	updateHosts($hosts, "update-treva-bind");
}

function domainsRegisterDomain($customerID, $domainName, $tldID)
{
	if(!domainsGetApi($tldID)->registerDomain($customerID, $domainName, $tldID)) {
		return false;
	}
	return true;
}

function domainsDisableAutoRenew($domainID)
{
	$subscriptionID = stdGet("dnsDomain", array("domainID"=>$domainID), "subscriptionID");
	if($subscriptionID !== null) {
		$expiredate = domainsDomainExpiredate($domainID);
		if(($date = parseDate($expiredate)) === null) {
			return false;
		}
		billingEndSubscription($subscriptionID, $date);
	}
	return domainsGetApi(domainsGetTldID($domainID))->disableAutoRenew($domainID);
}

function domainsEnableAutoRenew($domainID)
{
	$subscriptionID = stdGet("dnsDomain", array("domainID"=>$domainID), "subscriptionID");
	if($subscriptionID !== null) {
		billingEndSubscription($subscriptionID, null);
	}
	return domainsGetApi(domainsGetTldID($domainID))->enableAutoRenew($domainID);
}

function domainsDomainStatus($domainID)
{
	try {
		return domainsGetApi(domainsGetTldID($domainID))->domainStatus($domainID);
	} catch(DomainsNoApiException $e) {
		return "Externally hosted";
	}
}

function domainsDomainExpiredate($domainID)
{
	try {
		return domainsGetApi(domainsGetTldID($domainID))->domainExpiredate($domainID);
	} catch(DomainsNoApiException $e) {
		return "Unknown";
	}
}

function domainsDomainAutorenew($domainID)
{
	try {
		return domainsGetApi(domainsGetTldID($domainID))->domainAutorenew($domainID);
	} catch(DomainsNoApiException $e) {
		return null;
	}
}

function domainsDomainAvailable($domainName, $tldID)
{
	return domainsGetApi($tldID)->domainAvailable($domainName, $tldID);
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

function domainsUpdateContactInfo($customerID)
{
	domainsGetApiName("mijndomeinreseller")->updateContactInfo($customerID);
}

$GLOBAL["domainsCachedApis"] = array();

function domainsGetApiName($identifier)
{
	if(!isset($GLOBALS["domainsCachedApis"][$identifier])) {
		$parameters = stdGet("infrastructureDomainRegistrar", array("identifier"=>$identifier), "parameters");
		
		$name = $identifier . "api";
		require_once(dirname(__FILE__) . "/" . $name . ".php");
		$GLOBALS["domainsCachedApis"][$identifier] = new $name($parameters);
	}
	return $GLOBALS["domainsCachedApis"][$identifier];
}

function domainsGetApi($tldID)
{
	if($tldID === null) {
		throw new DomainsNoApiException();
	}
	$registrar = query("SELECT identifier, parameters FROM infrastructureDomainTld INNER JOIN infrastructureDomainRegistrar USING(domainRegistrarID) WHERE domainTldID=" . dbAddSlashes($tldID) . ";")->fetchArray();
	if(!isset($GLOBALS["domainsCachedApis"][$registrar["identifier"]])) {
		$name = $registrar["identifier"] . "api";
		require_once(dirname(__FILE__) . "/" . $name . ".php");
		$GLOBALS["domainsCachedApis"][$registrar["identifier"]] = new $name($registrar["parameters"]);
	}
	return $GLOBALS["domainsCachedApis"][$registrar["identifier"]];
}

function domainsGetTldID($domainID)
{
	return stdGetTry("dnsDomain", array("domainID"=>$domainID), "domainTldID", null);
}

function domainsFormatDomainName($domainID)
{
	$name = "";
	$separator = "";
	while(true) {
		$domain = stdGet("dnsDomain", array("domainID"=>$domainID), array("parentDomainID", "domainTldID", "name"));
		$name .= $separator . $domain["name"];
		$separator = ".";
		if($domain["domainTldID"] != null) {
			$tld = stdGet("infrastructureDomainTld", array("domainTldID"=>$domain["domainTldID"]), "name");
			$name .= $separator . $tld;
			break;
		}
		if($domain["parentDomainID"] == null) {
			break;
		}
		$domainID = $domain["parentDomainID"];
	}
	return $name;
}

class DomainsNoApiException extends Exception
{
}

function domainsCustomerUnpaidDomainsLimit($customerID)
{
	billingUpdateSubscriptionLines($customerID);
	$date = time() - (86400 * 366);
	$total = 0;
	foreach(billingInvoiceStatusList($customerID) as $invoice) {
		if($invoice["date"] > $date && $invoice["remainingAmount"] == 0) {
			foreach(stdList("accountingTransactionLine", array("transactionID"=>$invoice["transactionID"], "accountID"=>$GLOBALS["domainsRevenueAccountID"]), "amount") as $price) {
				$total -= $price;
			}
		}
	}
	
	$customer = stdGet("adminCustomer", array("customerID"=>$customerID), array("unpaidDomainPriceBase", "unpaidDomainPriceHistoryPercentage"));
	return (int)($total * $customer["unpaidDomainPriceHistoryPercentage"] / 100) + $customer["unpaidDomainPriceBase"];
}

function domainsCustomerUnpaidDomainsPrice($customerID)
{
	billingUpdateSubscriptionLines($customerID);
	$total = 0;
	foreach(billingInvoiceStatusList($customerID) as $invoice) {
		if($invoice["remainingAmount"] > 0) {
			foreach(stdList("accountingTransactionLine", array("transactionID"=>$invoice["transactionID"], "accountID"=>$GLOBALS["domainsRevenueAccountID"]), "amount") as $price) {
				$total -= $price;
			}
		}
	}
	foreach(stdList("billingSubscriptionLine", array("customerID"=>$customerID, "revenueAccountID"=>$GLOBALS["domainsRevenueAccountID"]), array("price", "discount")) as $line) {
		$total += round(($line["price"] - $line["discount"]) / (1 + $GLOBALS["taxRate"]));
	}
	return $total;
}

function domainsRemoveDomain($domainID)
{
	foreach(stdList("dnsDomain", array("parentDomainID"=>$domainID), "domainID") as $subDomainID) {
		domainsRemoveDomain($subDomainID);
	}
	stdDel("dnsDelegatedNameServer", array("domainID"=>$domainID));
	stdDel("dnsMailServer", array("domainID"=>$domainID));
	stdDel("dnsRecord", array("domainID"=>$domainID));
	stdDel("dnsDomain", array("domainID"=>$domainID));
}

function domainsRootDomainID($domainID)
{
	$parentDomainID = stdGet("dnsDomain", array("domainID"=>$domainID), "parentDomainID");
	if($parentDomainID == null) {
		return $domainID;
	} else {
		return domainsRootDomainID($parentDomainID);
	}
}

function domainsIsSubDomain($domainID)
{
	return stdGet("dnsDomain", array("domainID"=>$domainID), "parentDomainID") != null;
}

?>