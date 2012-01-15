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
		
		$parentCustomerID = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$parentDomainID), "customerID");
		$nameSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "nameSystemID");
		
		$GLOBALS["database"]->stdNew("dnsDomain", array("customerID"=>$parentCustomerID, "parentDomainID"=>$parentDomainID, "name"=>$domainName, "addressType"=>"TREVA-DELEGATION", "trevaDelegationNameSystemID"=>$nameSystemID, "mailType"=>"NONE"));
		
		return true;
	}
	
	public function disableAutoRenew($domainID)
	{
		$tldID = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "domainTldID");
		$tldParentDomainID = $this->tldParentDomainID($tldID);
		$GLOBALS["database"]->stdDel("dnsDomain", array("parentDomainID"=>$tldParentDomainID, "name"=>$this->domainName($domainID)));
	}
	
	public function enableAutoRenew($domainID)
	{
		return true;
	}
	
	public function domainStatus($domainID)
	{
		$tldID = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "domainTldID");
		if($this->domainAvailable($this->domainName($domainID), $tldID)) {
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
		return !$GLOBALS["database"]->stdExists("dnsDomain", array("parentDomainID"=>$this->tldParentDomainID($tldID), "name"=>$domainName));
	}
	
	private function tldParentDomainID($tldID)
	{
		$tld = $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$tldID), "name");
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
			$domainTldID = $GLOBALS["database"]->stdGetTry("infrastructureDomainTld", array("name"=>$tld), "domainTldID", null);
			if($domainTldID === null) {
				continue;
			}
			for($j = $i + 1; $j < count($tldParts); $j++) {
				$parentDomainID = $GLOBALS["database"]->stdGetTry("dnsDomain", array("name"=>$tldParts[$j], "parentDomainID"=>$parentDomainID, "domainTldID"=>$domainTldID), "domainID", null);
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
	
	private function domainName($domainID)
	{
		return $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "name");
	}
}

class TrevaDnsError extends Exception
{
}

?>