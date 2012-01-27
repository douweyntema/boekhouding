<?php

class mijndomeinresellerapi
{
	private $cachedDomainDetails = array();
	private $apiUrl = "https://manager.mijndomeinreseller.nl/api/";
	private $username;
	private $password;
	private $adminID;
	private $techID;
	private $billingID;
	
	public function __construct($parameters)
	{
		foreach(explode("\n", $parameters) as $parameter) {
			$pos = strpos($parameter, "=");
			if($pos === false) {
				continue;
			}
			$name = trim(substr($parameter, 0, $pos));
			$value = trim(substr($parameter, $pos + 1));
			$this->$name = $value;
		}
		
		$this->password = base64_decode($this->password);
	}
	
	public function registerDomain($customerID, $domainName, $tldID)
	{
		$registrantID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "mijnDomeinResellerContactID");
		if($registrantID === null) {
			$this->updateContactInfo();
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
		
		$verloopdatum = $this->domain_register($domainName, $this->tld($tldID), $registrantID, $this->adminID, $this->techID, $this->billingID, true, true, false, 1, $nsID);
		return $verloopdatum !== null;
	}
	
	public function disableAutoRenew($domainID)
	{
		return $this->domain_set_autorenew($this->domainName($domainID), $this->domainTld($domainID), true, true);
	}
	
	public function enableAutoRenew($domainID)
	{
		return $this->domain_set_autorenew($this->domainName($domainID), $this->domainTld($domainID), false, true);
	}
	
	private function doDomainDetails($domainID)
	{
		return $this->domain_get_details($this->domainName($domainID), $this->domainTld($domainID));
	}
	
	private function domainDetails($domainID)
	{
		if(!isset($this->cachedDomainDetails[$domainID])) {
			$this->cachedDomainDetails[$domainID] = $this->doDomainDetails($domainID);
		}
		return $this->cachedDomainDetails[$domainID];
	}
	
	public function domainStatus($domainID)
	{
		try {
			$details = $this->domainDetails($domainID);
			return $details["status"];
		} catch(DomainResellerError $e) {
			return "Unavailable";
		}
	}
	
	public function domainExpiredate($domainID)
	{
		try {
			$details = $this->domainDetails($domainID);
			return $details["verloopdatum"];
		} catch(DomainResellerError $e) {
			return "Unavailable";
		}
	}
	
	public function domainAutorenew($domainID)
	{
		try {
			$details = $this->domainDetails($domainID);
			return $details["autorenew"];
		} catch(DomainResellerError $e) {
			return null;
		}
	}
	
	public function domainAvailable($domainName, $tldID)
	{
		$status = $this->whois_bulk($domainName . "." . $this->tld($tldID));
		return $status[0]["status"] == 1;
	}
	
	public function updateContactInfo()
	{
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
				
				$contactID = $this->contact_add($contact["companyName"], null, null, $contact["initials"], null, $contact["lastName"], $straat, $huisnummer, $huisnummerToevoeging, $postcode, $city, $contact["countryCode"], $contact["email"], $telefoonnummer);
				
				$GLOBALS["database"]->stdSet("adminCustomer", array("customerID"=>$customerID), array("mijnDomeinResellerContactID"=>$contactID));
				
				$nameServerID = $GLOBALS["database"]->stdGet("infrastructureNameSystem", array("nameSystemID"=>$contact["nameSystemID"]), "mijnDomeinResellerNameServerSetID");
				
				foreach($GLOBALS["database"]->query("SELECT dnsDomain.domainID, dnsDomain.name, infrastructureDomainTld.domainTldID, infrastructureDomainTld.name AS tld FROM dnsDomain INNER JOIN infrastructureDomainTld USING(domainTldID) INNER JOIN infrastructureDomainRegistrar USING(domainRegistrarID) WHERE dnsDomain.customerID = $customerID AND dnsDomain.syncContactInfo = 1 AND infrastructureDomainRegistrar.identifier = 'mijndomeinreseller'")->fetchList() as $domain) {
					if($domain["tld"] == "nl" || $domain["tld"] == "eu") {
						$this->domain_trade($domain["name"], $domain["tld"], $contactID, $this->adminID, $this->techID, null, $nameServerID);
					} else if($domain["tld"] == "be") {
						ticketNewThread(null, getRootUser(), "Gegevens {$domain["name"]}.{$domain["tld"]} gewijzigd", "De gegevens van het domein {$domain["name"]}.{$domain["tld"]} van klant {$contact["name"]} zijn gewijzigd.\nOm die aan te passen bij MijnDomeinReseller is een autorisatiekey nodig.");
					} else {
						$this->domain_modify_contacts($domain["name"], $domain["tld"], $contactID, $this->adminID, $this->techID, $this->billingID);
					}
				}
			} catch(DomainResellerError $e) {}
		}
	}
	
	private function tld($tldID)
	{
		return $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$tldID), "name");
	}
	
	private function domainName($domainID)
	{
		return $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "name");
	}
	
	private function domainTld($domainID)
	{
		return $this->tld($GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "domainTldID"));
	}
	
	private function contact_add($bedrijfsnaam, $rechtsvorm, $regnummer, $voorletter, $tussenvoegsel, $achternaam, $straat, $huisnr, $huisnrtoev, $postcode, $plaats, $land, $email, $tel)
	{
		$ret = $this->request("contact_add", array("bedrijfsnaam"=>$bedrijfsnaam, "rechtsvorm"=>$rechtsvorm, "regnummer"=>$regnummer, "voorletter"=>$voorletter, "tussenvoegsel"=>$tussenvoegsel, "achternaam"=>$achternaam, "straat"=>$straat, "huisnr"=>$huisnr, "huisnrtoev"=>$huisnrtoev, "postcode"=>$postcode, "plaats"=>$plaats, "land"=>strtolower($land), "email"=>$email, "tel"=>$tel));
		return $ret["contact_id"];
	}
	
	private function contact_list($sort = null, $order = null)
	{
		$ret = $this->request("contact_list", array("sort"=>$sort, "order"=>$order));
		$contacts = array();
		for($i = 0; $i < $ret["contactcount"]; $i++) {
			$contact = array();
			foreach(array("contact_id", "contact_bedrijfsnaam", "contact_voorletter", "contact_tussenvoegsel", "contact_achternaam", "contact_straat", "contact_huisnr", "contact_huisnrtoev", "contact_postcode", "contact_plaats", "contact_land", "contact_email", "contact_tel") as $key) {
				$contact[$key] = $ret[$key . "[" . $i . "]"];
			}
			$contacts[] = $contact;
		}
		return $contacts;
	}
	
	private function domain_auth_info($domein, $tld)
	{
		$ret = $this->request("domain_auth_info", array("domein"=>$domein, "tld"=>$tld));
		return true;
	}
	
	private function domain_delete($domein, $tld)
	{
		$ret = $this->request("domain_delete", array("domein"=>$domein, "tld"=>$tld));
		return true;
	}
	
	private function domain_get_details($domein, $tld)
	{
		$ret = $this->request("domain_get_details", array("domein"=>$domein, "tld"=>$tld));
		return $ret;
	}
	
	private function domain_list($tld = null, $sort = null, $order = null, $begin = null)
	{
		die("domain_list() not tested");
		$ret = $this->request("domain_list", array("tld"=>$tld, "sort"=>$sort, "order"=>$order, "begin"=>$begin));
		$domains = array();
		for($i = 0; $i < $ret["domeincount"]; $i++) {
			$domain = array();
			foreach(array("domain", "registrant", "registrant_id", "admin", "admin_id", "tech", "tech_id", "verloopdatum", "status") as $key) {
				$domain[$key] = $ret[$key . "[" . $i . "]"];
			}
			$domains[] = $domain;
		}
		return $domains;
	}
	
	private function domain_modify_contacts($domein, $tld, $registrant_id, $admin_id, $tech_id, $bill_id)
	{
		$ret = $this->request("domain_modify_contacts", array("domein"=>$domein, "tld"=>$tld, "registrant_id"=>$registrant_id,  "admin_id"=>$admin_id, "tech_id"=>$tech_id, "bill_id"=>$bill_id));
		return true;
	}
	
	private function domain_modify_ns($domein, $tld, $ns_id, $ns1 = null, $ns1_ip = null, $ns2 = null, $ns2_ip = null, $ns3 = null, $ns3_ip = null, $gebruik_dns = null, $dns_template = null)
	{
		$ret = $this->request("domain_modify_contacts", array("domein"=>$domein, "tld"=>$tld, "ns_id"=>$ns_id, "ns1"=>$ns1, "ns1_ip"=>$ns1_ip, "ns2"=>$ns2, "ns2_ip"=>$ns2_ip, "ns3"=>$ns3, "ns3_ip"=>$ns3_ip, "gebruik_dns"=>$gebruik_dns, "dns_template"=>$dns_template));
		return true;
	}
	
	private function domain_register($domein, $tld, $registrant_id, $admin_id, $tech_id, $bill_id, $lock, $autorenew, $idprotect, $duur, $ns_id, $ns1 = null, $ns1_ip = null, $ns2 = null, $ns2_ip = null, $ns3 = null, $ns3_ip = null, $gebruik_dns = null, $dns_template = null, $promo = null)
	{
		$ret = $this->request("domain_register", array("domein"=>$domein, "tld"=>$tld, "registrant_id"=>$registrant_id,  "admin_id"=>$admin_id, "tech_id"=>$tech_id, "bill_id"=>$bill_id, "lock"=>$lock, "autorenew"=>$autorenew, "idprotect"=>$idprotect, "duur"=>$duur, "ns_id"=>$ns_id, "ns1"=>$ns1, "ns1_ip"=>$ns1_ip, "ns2"=>$ns2, "ns2_ip"=>$ns2_ip, "ns3"=>$ns3, "ns3_ip"=>$ns3_ip, "gebruik_dns"=>$gebruik_dns, "dns_template"=>$dns_template, "promo"=>$promo));
		return $ret["verloopdatum"];
	}
	
	private function domain_set_autorenew($domein, $tld, $autorenew, $registrant_approve)
	{
		$ret = $this->request("domain_set_autorenew", array("domein"=>$domein, "tld"=>$tld, "autorenew"=>$autorenew, "registrant_approve"=>$registrant_approve));
		return true;
	}
	
	private function domain_set_lock($domein, $tld, $set_lock)
	{
		$ret = $this->request("domain_set_autorenew", array("domein"=>$domein, "tld"=>$tld, "set_lock"=>$set_lock));
		return true;
	}
	
	private function domain_trade($domein, $tld, $registrant_id, $admin_id, $tech_id, $authkey, $ns_id, $ns1 = null, $ns1_ip = null, $ns2 = null, $ns2_ip = null, $ns3 = null, $ns3_ip = null, $gebruik_dns = null, $dns_template = null)
	{
		$ret = $this->request("domain_trade", array("domein"=>$domein, "tld"=>$tld, "registrant_id"=>$registrant_id, "admin_id"=>$admin_id, "tech_id"=>$tech_id, "ns_id"=>$ns_id, "ns1"=>$ns1, "ns1_ip"=>$ns1_ip, "ns2"=>$ns2, "ns2_ip"=>$ns2_ip, "ns3"=>$ns3, "ns3_ip"=>$ns3_ip, "gebruik_dns"=>$gebruik_dns, "dns_template"=>$dns_template, "authkey"=>$authkey));
		return true;
	}
	
	private function domain_transfer($domein, $tld, $admin_id, $tech_id, $authkey, $ns_id, $ns1 = null, $ns1_ip = null, $ns2 = null, $ns2_ip = null, $ns3 = null, $ns3_ip = null, $gebruik_dns = null, $dns_template = null, $lock = null, $autorenew = null, $notify = null, $notify_email = null, $promo = null)
	{
		$ret = $this->request("domain_transfer", array("domein"=>$domein, "tld"=>$tld, "admin_id"=>$admin_id, "tech_id"=>$tech_id, "ns_id"=>$ns_id, "ns1"=>$ns1, "ns1_ip"=>$ns1_ip, "ns2"=>$ns2, "ns2_ip"=>$ns2_ip, "ns3"=>$ns3, "ns3_ip"=>$ns3_ip, "gebruik_dns"=>$gebruik_dns, "dns_template"=>$dns_template, "authkey"=>$authkey, "lock"=>$lock, "autorenew"=>$autorenew, "notify"=>$notify, "notify_email"=>$notify_email, "promo"=>$promo));
		return true;
	}
	
	private function nameserver_add($auto, $ns1, $ns2, $ns3 = null, $ns1_ip = null, $ns2_ip = null, $ns3_ip = null)
	{
		$ret = $this->request("nameserver_add", array("auto"=>$auto, "ns1"=>$ns1, "ns1_ip"=>$ns1_ip, "ns2"=>$ns2, "ns2_ip"=>$ns2_ip, "ns3"=>$ns3, "ns3_ip"=>$ns3_ip));
		return $ret["ns_id"];
	}
	
	private function nameserver_list($sort = null, $order = null)
	{
		$ret = $this->request("nameserver_list", array("sort"=>$sort, "order"=>$order));
		$nameservers = array();
		for($i = 0; $i < $ret["nscount"]; $i++) {
			$nameserver = array();
			foreach(array("ns_id", "ns_ns1", "ns_ns1_ip", "ns_ns2", "ns_ns2_ip", "ns_ns3", "ns_ns3_ip") as $key) {
				$nameserver[$key] = $ret[$key . "[" . $i . "]"];
			}
			$nameservers[] = $nameserver;
		}
		return $nameservers;
	}
	
	private function transfer_details($transfer_id)
	{
		$ret = $this->request("transfer_details", array("transfer_id"=>$transfer_id));
		return $ret;
	}
	
	private function transfer_list($status = null, $domein = null, $tld = null)
	{
		$ret = $this->request("transfer_list", array("status"=>$status, "domein"=>$domein, "tld"=>$tld));
		$transfers = array();
		for($i = 0; $i < $ret["transfercount"]; $i++) {
			$transfer = array();
			foreach(array("transfer_id", "domein", "status", "status_melding", "datum_invoer", "datum_update") as $key) {
				$transfer[$key] = $ret[$key . "[" . $i . "]"];
			}
			$transfers[] = $transfer;
		}
		return $transfers;
	}
	
	private function whois($domein)
	{
		$ret = $this->request("whois", array("type"=>"uitgebreid", "domein"=>$domein));
		return urldecode($ret["result"]);
	}
	
	private function whois_intern($domein)
	{
		$ret = $this->request("whois", array("type"=>"intern", "domein"=>$domein));
		return $ret;
	}
	
	private function whois_bulk($domeinen)
	{
		if(is_array($domeinen)) {
			$domeinen = implode(";", $domeinen);
		}
		$ret = $this->request("whois", array("type"=>"bulk", "domeinen"=>$domeinen));
		$domeinen = array();
		for($i = 1; $i <= $ret["domeincount"]; $i++) {
			$domein = array();
			foreach(array("domein", "status") as $key) {
				$domein[$key] = $ret[$key . "[" . $i . "]"];
			}
			$domeinen[] = $domein;
		}
		return $domeinen;
	}
	
	private function request($type, $params)
	{
		$url = $this->apiUrl . "?user=" . $this->username . "&pass=" . $this->password . "&authtype=plain";
		
		$url .= "&command=$type";
		
		foreach($params as $key=>$value) {
			if($value !== null) {
				if($value === true) {
					$url .= "&$key=true";
				} else if($value === false) {
					$url .= "&$key=false";
				} else {
					$url .= "&$key=" . urlencode($value);
				}
			}
		}
		$context = stream_context_create(array("http"=>array("timeout"=>5)));
		$ret = file_get_contents($url, false, $context);
		
		if($ret === false) {
			throw new DomainResellerError("Unable to connect to the api-server");
		}
		
		$values = array();
		$lines = explode("\n", $ret);
		foreach($lines as $line) {
			if(trim($line) == "") {
				continue;
			}
			$parts = explode("=", $line);
			if(count($parts) < 2) {
				continue;
			}
			$values[trim($parts[0])] = trim($parts[1]);
		}
		if(count($values) == 0) {
			throw new DomainResellerError("Got no results from the api-server");
		}
		if($values["errcount"] != 0) {
			throw new DomainResellerError($values["errnotxt1"], $values["errno1"], $type, $params, $values);
		}
		return $values;
	}
}

class DomainResellerError extends Exception {
	public $text;
	public $code;
	public $type;
	public $request;
	public $response;
	
	public function __construct($text, $code = null, $type = null, $request = null, $response = null)
	{
		$this->text = $text;
		$this->code = $code;
		$this->type = $type;
		$this->request = $request;
		$this->response = $response;
	}
}

?>