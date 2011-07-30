<?php

require_once("mijndomeinresellerapi.php");

function registerDomain($customerID, $domain, $tld)
{
}

function disableAutoRenew($domain)
{
}

function enableAutoRenew($domain)
{
}

function domainDetails($domain)
{
}

function updateContactInfo()
{
	global $mijnDomainResellerAdminID, $mijnDomainResellerTechID, $mijnDomainResellerBillID;
	foreach($GLOBALS["database"]->stdList("adminCustomer", array("mijnDomeinResellerContactID"=>null), array("customerID", "nameSystemID", "name", "bedrijfsnaam", "voorletters", "tussenvoegsel", "achternaam", "straat", "huisnummer", "postcode", "plaats", "land", "email", "telefoon")) as $contact) {
		$customerID = $contact["customerID"];
		preg_match("/^([0-9]*)([^0-9].*)?\$/", trim($contact["huisnummer"]), $regex);
		$huisnummer = $regex[1];
		$huisnummerToevoeging = $regex[2];
		
		$contactID = contact_add($contact["bedrijfsnaam"], null, null, $contact["voorletters"], $contact["tussenvoegsel"], $contact["achternaam"], $contact["straat"], $huisnummer, $huisnummerToevoeging, $contact["postcode"], $contact["plaats"], $contact["land"], $contact["email"], $contact["telefoon"]);
		
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
	}
}

?>