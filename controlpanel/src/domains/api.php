<?php

$domainsTitle = "Domains";
$domainsDescription = "Domains";
$domainsTarget = "customer";

require_once("mijndomeinresellerapi.php");

function domainsUpdate($customerID)
{
	
}

function domainsRegisterDomain($customerID, $domain, $tld)
{
	global $mijnDomainResellerAdminID, $mijnDomainResellerTechID, $mijnDomainResellerBillID;
	$registrantID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "mijnDomeinResellerContactID");
	if($registrantID === null) {
		domainsUpdateContactInfo();
		$registrantID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "mijnDomeinResellerContactID");
		if($registrantID === null) {
			return false;
		}
	}
	$nameSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "nameSystemID");
	$nsID = $GLOBALS["database"]->stdGet("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), "mijnDomeinResellerNameServerSetID");
	if($nsID === null) {
		return false;
	}
	
	$verloopdatum = domain_register($domain, $tld, $registrantID, $mijnDomainResellerAdminID, $mijnDomainResellerTechID, $mijnDomainResellerBillID, true, true, false, 1, $nsID);
	return $verloopdatum !== null;
}

function domainsDisableAutoRenew($domain)
{
	$domainParts = explode(".", $domain);
	if(count($domainParts) != 2) {
		return false;
	}
	
	return domain_set_autorenew($domainParts[0], $domainParts[1], true, true);
}

function domainsEnableAutoRenew($domain)
{
	$domainParts = explode(".", $domain);
	if(count($domainParts) != 2) {
		return false;
	}
	
	return domain_set_autorenew($domainParts[0], $domainParts[1], false, true);
}

function domainsDomainDetails($domain)
{
	$domainParts = explode(".", $domain);
	if(count($domainParts) != 2) {
		return false;
	}
	
	return domain_get_details($domainParts[0], $domainParts[1]);
}

function domainsDomainAvailable($domain)
{
	$status = whois_bulk($domain);
	return $status[0]["status"] == 1;
}

function domainsUpdateContactInfo()
{
	global $mijnDomainResellerAdminID, $mijnDomainResellerTechID, $mijnDomainResellerBillID;
	foreach($GLOBALS["database"]->stdList("adminCustomer", array("mijnDomeinResellerContactID"=>null), array("customerID", "nameSystemID", "name", "companyName", "initials", "lastName", "address", "postalCode", "city", "countryCode", "email", "phoneNumber")) as $contact) {
		try {
			$customerID = $contact["customerID"];
			
			preg_match("/^(.*) ([0-9]+)([^0-9 ][^ ]*)?\$/", trim($contact["address"]), $regex);
			if(count($regex) >= 3) {
				$straat = $regex[1];
				$huisnummer = $regex[2];
				if(isset($regex[3])) {
					$huisnummerToevoeging = $regex[3];
				} else {
					$huisnummerToevoeging = null;
				}
			} else {
				if($contact["address"] == "") {
					$straat = "-";
				} else {
					$straat = $contact["address"];
				}
				$huisnummer = "0";
				$huisnummerToevoeging = null;
			}
			
			if($contact["postalCode"] === null || trim($contact["postalCode"]) == "") {
				$postcode = "0000 AA";
			} else {
				$postcode = $contact["postalCode"];
			}
			
			if($contact["city"] == "") {
				$city = "-";
			} else {
				$city = $contact["city"];
			}
			
			if(!ctype_digit($contact["countryCode"])) {
				$telefoonnummer = "0000000000";
			} else if($contact["countryCode"] == "nl" && strlen($contact["phoneNumber"]) != 10) {
				$telefoonnummer = "0000000000";
			} else if(strlen($contact["phoneNumber"]) < 2 || $contact["phoneNumber"] > 12) {
				$telefoonnummer = "0000000000";
			} else {
				$telefoonnummer = $contact["phoneNumber"];
			}
			
			$contactID = contact_add($contact["companyName"], null, null, $contact["initials"], null, $contact["lastName"], $straat, $huisnummer, $huisnummerToevoeging, $postcode, $city, $contact["countryCode"], $contact["email"], $telefoonnummer);
			
			$GLOBALS["database"]->stdSet("adminCustomer", array("customerID"=>$customerID), array("mijnDomeinResellerContactID"=>$contactID));
			
			$nameServerID = $GLOBALS["database"]->stdGet("infrastructureNameSystem", array("nameSystemID"=>$contact["nameSystemID"]), "mijnDomeinResellerNameServerSetID");
			
			foreach($GLOBALS["database"]->query("SELECT domain.domainID, domain.name AS domain, tld.name AS tld FROM dnsDomain AS domain INNER JOIN dnsDomain AS tld ON domain.parentDomainID = tld.domainID WHERE domain.customerID = $customerID AND tld.customerID IS NULL")->fetchList() as $domain) {
				if($domain["tld"] == "nl" || $domain["tld"] == "eu") {
					domain_trade($domain["domain"], $domain["tld"], $contactID, $mijnDomainResellerAdminID, $mijnDomainResellerTechID, null, $nameServerID);
				} else if($domain["tld"] == "be") {
					ticketNewThread(null, getRootUser(), "Gegevens {$domain["domain"]}.{$domain["tld"]} gewijzigd", "De gegevens van het domein {$domain["domain"]}.{$domain["tld"]} van klant {$contact["name"]} zijn gewijzigd.\nOm die aan te passen bij MijnDomeinReseller is een autorisatiekey nodig.");
				} else {
					domain_modify_contacts($domain["domain"], $domain["tld"], $contactID, $mijnDomainResellerAdminID, $mijnDomainResellerTechID, $mijnDomainResellerBillID);
				}
			}
		} catch(DomainResellerError $e) {}
	}
}

?>