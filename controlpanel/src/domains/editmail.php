<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomain($domainID);
	
	$content = "<h1>Domain " . domainsFormatDomainName($domainID) . "</h1>\n";
	
	$content .= domainBreadcrumbs($domainID, array(array("name"=>"Edit email", "url"=>"{$GLOBALS["root"]}domains/editpath.php?id=$domainID")));
	
	$type = mailTypeFromTitle(post("type"));
	
	if($type == "CUSTOM") {
		$mailservers = array();
		foreach($_POST as $key=>$value) {
			if(strlen(trim($value)) == 0) {
				continue;
			}
			if(substr($key, 0, strlen("mailserver-")) == "mailserver-") {
				$number = substr($key, strlen("mailserver-"));
				$mailservers[$number] = $value;
			}
		}
		ksort($mailservers);
	} else {
		$mailservers = null;
	}
	
	if($type === null) {
		$content .= editMailTypeForm($domainID, "");
		die(page($content));
	}
	
	if($type == "NONE") {
		if(post("confirm") === null) {
			$content .= editMailTypeForm($domainID, null, $type, $mailservers);
			die(page($content));
		}
		
		$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), array("mailType"=>"NONE"));
		$GLOBALS["database"]->stdDel("dnsMailServer", array("domainID"=>$domainID));
	} else if($type == "TREVA") {
		if(post("confirm") === null) {
			$content .= editMailTypeForm($domainID, null, $type, $mailservers);
			die(page($content));
		}
		
		$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), array("mailType"=>"TREVA"));
		$GLOBALS["database"]->stdDel("dnsMailServer", array("domainID"=>$domainID));
	} else if($type == "CUSTOM") {
		$error = "";
		foreach($mailservers as $priority=>$name) {
			if(!validDomain($name)) {
				$error .= "Invalid mailserver: $name\n";
			}
			if(!is_int($priority)) {
				$error .= "Internal error!\n";
			}
		}
		if($error != "") {
			$content .= editMailTypeForm($domainID, $error, $type, $mailservers);
			die(page($content));
		}
		
		if(post("confirm") === null) {
			$content .= editMailTypeForm($domainID, null, $type, $mailservers);
			die(page($content));
		}
		
		$GLOBALS["database"]->startTransaction();
		$GLOBALS["database"]->stdSet("dnsDomain", array("domainID"=>$domainID), array("mailType"=>"CUSTOM"));
		$GLOBALS["database"]->stdDel("dnsMailServer", array("domainID"=>$domainID));
		foreach($mailservers as $priority=>$name) {
			$GLOBALS["database"]->stdNew("dnsMailServer", array("domainID"=>$domainID, "name"=>$name, "priority"=>($priority + 1) * 10));
		}
		$GLOBALS["database"]->commitTransaction();
	} else {
		die("Internal error");
	}
	
	updateDomains(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id=$domainID");
}

main();

?>