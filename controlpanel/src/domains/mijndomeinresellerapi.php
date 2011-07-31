<?php

function contact_add($bedrijfsnaam, $rechtsvorm, $regnummer, $voorletter, $tussenvoegsel, $achternaam, $straat, $huisnr, $huisnrtoev, $postcode, $plaats, $land, $email, $tel)
{
	$ret = request("contact_add", array("bedrijfsnaam"=>$bedrijfsnaam, "rechtsvorm"=>$rechtsvorm, "regnummer"=>$regnummer, "voorletter"=>$voorletter, "tussenvoegsel"=>$tussenvoegsel, "achternaam"=>$achternaam, "straat"=>$straat, "huisnr"=>$huisnr, "huisnrtoev"=>$huisnrtoev, "postcode"=>$postcode, "plaats"=>$plaats, "land"=>strtolower($land), "email"=>$email, "tel"=>$tel));
	return $ret["contact_id"];
}

function contact_list($sort = null, $order = null)
{
	$ret = request("contact_list", array("sort"=>$sort, "order"=>$order));
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

function domain_auth_info($domein, $tld)
{
	$ret = request("domain_auth_info", array("domein"=>$domein, "tld"=>$tld));
	return true;
}

function domain_delete($domein, $tld)
{
	$ret = request("domain_delete", array("domein"=>$domein, "tld"=>$tld));
	return true;
}

function domain_get_details($domein, $tld)
{
	$ret = request("domain_get_details", array("domein"=>$domein, "tld"=>$tld));
	return $ret;
}

function domain_list($tld = null, $sort = null, $order = null, $begin = null)
{
	$ret = request("domain_list", array("tld"=>$tld, "sort"=>$sort, "order"=>$order, "begin"=>$begin));
	$domains = array();
	var_dump($ret);
	die();
	for($i = 0; $i < $ret["domeincount"]; $i++) {
		$domain = array();
		foreach(array("domain", "registrant", "registrant_id", "admin", "admin_id", "tech", "tech_id", "verloopdatum", "status") as $key) {
			$domain[$key] = $ret[$key . "[" . $i . "]"];
		}
		$domains[] = $domain;
	}
	return $domains;
}

function domain_modify_contacts($domein, $tld, $registrant_id, $admin_id, $tech_id, $bill_id)
{
	$ret = request("domain_modify_contacts", array("domein"=>$domein, "tld"=>$tld, "registrant_id"=>$registrant_id,  "admin_id"=>$admin_id, "tech_id"=>$tech_id, "bill_id"=>$bill_id));
	return true;
}

function domain_modify_ns($domein, $tld, $ns_id, $ns1 = null, $ns1_ip = null, $ns2 = null, $ns2_ip = null, $ns3 = null, $ns3_ip = null, $gebruik_dns = null, $dns_template = null)
{
	$ret = request("domain_modify_contacts", array("domein"=>$domein, "tld"=>$tld, "ns_id"=>$ns_id, "ns1"=>$ns1, "ns1_ip"=>$ns1_ip, "ns2"=>$ns2, "ns2_ip"=>$ns2_ip, "ns3"=>$ns3, "ns3_ip"=>$ns3_ip, "gebruik_dns"=>$gebruik_dns, "dns_template"=>$dns_template));
	return true;
}

function domain_register($domein, $tld, $registrant_id, $admin_id, $tech_id, $bill_id, $lock, $autorenew, $idprotect, $duur, $ns_id, $ns1 = null, $ns1_ip = null, $ns2 = null, $ns2_ip = null, $ns3 = null, $ns3_ip = null, $gebruik_dns = null, $dns_template = null, $promo = null)
{
	$ret = request("domain_register", array("domein"=>$domein, "tld"=>$tld, "registrant_id"=>$registrant_id,  "admin_id"=>$admin_id, "tech_id"=>$tech_id, "bill_id"=>$bill_id, "lock"=>$lock, "autorenew"=>$autorenew, "idprotect"=>$idprotect, "duur"=>$duur, "ns_id"=>$ns_id, "ns1"=>$ns1, "ns1_ip"=>$ns1_ip, "ns2"=>$ns2, "ns2_ip"=>$ns2_ip, "ns3"=>$ns3, "ns3_ip"=>$ns3_ip, "gebruik_dns"=>$gebruik_dns, "dns_template"=>$dns_template, "promo"=>$promo));
	return $ret["verloopdatum"];
}

function domain_set_autorenew($domein, $tld, $autorenew, $registrant_approve)
{
	$ret = request("domain_set_autorenew", array("domein"=>$domein, "tld"=>$tld, "autorenew"=>$autorenew, "registrant_approve"=>$registrant_approve));
	return true;
}

function domain_set_lock($domein, $tld, $set_lock)
{
	$ret = request("domain_set_autorenew", array("domein"=>$domein, "tld"=>$tld, "set_lock"=>$set_lock));
	return true;
}

function domain_trade($domein, $tld, $registrant_id, $admin_id, $tech_id, $authkey, $ns_id, $ns1 = null, $ns1_ip = null, $ns2 = null, $ns2_ip = null, $ns3 = null, $ns3_ip = null, $gebruik_dns = null, $dns_template = null)
{
	$ret = request("domain_trade", array("domein"=>$domein, "tld"=>$tld, "registrant_id"=>$registrant_id, "admin_id"=>$admin_id, "tech_id"=>$tech_id, "ns_id"=>$ns_id, "ns1"=>$ns1, "ns1_ip"=>$ns1_ip, "ns2"=>$ns2, "ns2_ip"=>$ns2_ip, "ns3"=>$ns3, "ns3_ip"=>$ns3_ip, "gebruik_dns"=>$gebruik_dns, "dns_template"=>$dns_template, "authkey"=>$authkey));
	return true;
}

function domain_transfer($domein, $tld, $admin_id, $tech_id, $authkey, $ns_id, $ns1 = null, $ns1_ip = null, $ns2 = null, $ns2_ip = null, $ns3 = null, $ns3_ip = null, $gebruik_dns = null, $dns_template = null, $lock = null, $autorenew = null, $notify = null, $notify_email = null, $promo = null)
{
	$ret = request("domain_transfer", array("domein"=>$domein, "tld"=>$tld, "admin_id"=>$admin_id, "tech_id"=>$tech_id, "ns_id"=>$ns_id, "ns1"=>$ns1, "ns1_ip"=>$ns1_ip, "ns2"=>$ns2, "ns2_ip"=>$ns2_ip, "ns3"=>$ns3, "ns3_ip"=>$ns3_ip, "gebruik_dns"=>$gebruik_dns, "dns_template"=>$dns_template, "authkey"=>$authkey, "lock"=>$lock, "autorenew"=>$autorenew, "notify"=>$notify, "notify_email"=>$notify_email, "promo"=>$promo));
	return true;
}

function nameserver_add($auto, $ns1, $ns2, $ns3 = null, $ns1_ip = null, $ns2_ip = null, $ns3_ip = null)
{
	$ret = request("nameserver_add", array("auto"=>$auto, "ns1"=>$ns1, "ns1_ip"=>$ns1_ip, "ns2"=>$ns2, "ns2_ip"=>$ns2_ip, "ns3"=>$ns3, "ns3_ip"=>$ns3_ip));
	return $ret["ns_id"];
}

function nameserver_list($sort = null, $order = null)
{
	$ret = request("nameserver_list", array("sort"=>$sort, "order"=>$order));
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

function transfer_details($transfer_id)
{
	$ret = request("transfer_details", array("transfer_id"=>$transfer_id));
	return $ret;
}

function transfer_list($status = null, $domein = null, $tld = null)
{
	$ret = request("transfer_list", array("status"=>$status, "domein"=>$domein, "tld"=>$tld));
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

function whois($domein)
{
	$ret = request("whois", array("type"=>"uitgebreid", "domein"=>$domein));
	return urldecode($ret["result"]);
}

function whois_intern($domein)
{
	$ret = request("whois", array("type"=>"intern", "domein"=>$domein));
	return $ret;
}

function whois_bulk($domeinen)
{
	if(is_array($domeinen)) {
		$domeinen = implode(";", $domeinen);
	}
	$ret = request("whois", array("type"=>"bulk", "domeinen"=>$domeinen));
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

function request($type, $params)
{
	global $domainResellerApiUrl;
	$url = $domainResellerApiUrl;
	
	$url .= "&command=$type";
	
	foreach($params as $key=>$value) {
		if($value !== null) {
			$url .= "&$key=" . urlencode($value);
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