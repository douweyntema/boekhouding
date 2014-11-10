<?php

class trevadnsapi
{
	public function __construct($parameters)
	{
	}
	
	public function registerDomain($customerID, $domainName, $tldID)
	{
		if(!$this->domainAvailable($domainName, $tldID)) {
			return false;
		}
		
		$parentDomainID = $this->tldParentDomainID($tldID);
		
		$parentCustomerID = stdGet("dnsDomain", array("domainID"=>$parentDomainID), "customerID");
		$nameSystemID = stdGet("adminCustomer", array("customerID"=>$customerID), "nameSystemID");
		
		stdNew("dnsDomain", array("customerID"=>$parentCustomerID, "parentDomainID"=>$parentDomainID, "name"=>$domainName, "addressType"=>"TREVA-DELEGATION", "trevaDelegationNameSystemID"=>$nameSystemID, "mailType"=>"NONE"));
		
		return true;
	}
	
	public function disableAutoRenew($domainID)
	{
		$tldID = stdGet("dnsDomain", array("domainID"=>$domainID), "domainTldID");
		$tldParentDomainID = $this->tldParentDomainID($tldID);
		stdDel("dnsDomain", array("parentDomainID"=>$tldParentDomainID, "name"=>$this->domainsFormatDomainName($domainID)));
	}
	
	public function enableAutoRenew($domainID)
	{
		return true;
	}
	
	public function domainStatus($domainID)
	{
		$tldID = stdGet("dnsDomain", array("domainID"=>$domainID), "domainTldID");
		if($this->domainAvailable($this->domainsFormatDomainName($domainID), $tldID)) {
			return "expired";
		} else {
			return "activeforever";
		}
	}
	
	public function domainExpiredate($domainID)
	{
		return "never";
	}
	
	public function domainAutorenew($domainID)
	{
		return false;
	}
	
	public function domainAvailable($domainName, $tldID)
	{
		return !stdExists("dnsDomain", array("parentDomainID"=>$this->tldParentDomainID($tldID), "name"=>$domainName));
	}
	
	private function tldParentDomainID($tldID)
	{
		$tld = stdGet("infrastructureDomainTld", array("domainTldID"=>$tldID), "name");
		$tldParts = explode(".", $tld);
		$tldParts = array_reverse($tldParts);
		$tld = "";
		$parentDomainID = null;
		for($i = 0; $i < count($tldParts); $i++) {
			if($i == 0) {
				$tld = $tldParts[$i];
			} else {
				$tld = $tldParts[$i] . "." . $tld;
			}
			$domainTldID = stdGetTry("infrastructureDomainTld", array("name"=>$tld), "domainTldID", null);
			if($domainTldID === null) {
				continue;
			}
			for($j = $i + 1; $j < count($tldParts); $j++) {
				$parentDomainID = stdGetTry("dnsDomain", array("name"=>$tldParts[$j], "parentDomainID"=>$parentDomainID, "domainTldID"=>$domainTldID), "domainID", null);
				$domainTldID = null;
				if($parentDomainID == null) {
					continue 2;
				}
			}
			if($parentDomainID != null) {
				break;
			}
		}
		if($parentDomainID == null) {
			throw new TrevaDnsError();
		}
		return $parentDomainID;
	}
	
	private function domainsFormatDomainName($domainID)
	{
		return stdGet("dnsDomain", array("domainID"=>$domainID), "name");
	}
}

class TrevaDnsError extends Exception
{
}

?>