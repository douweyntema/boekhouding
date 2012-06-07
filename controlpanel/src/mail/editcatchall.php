<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(domainHeader($domainID) . editCatchAllFrom($domainID, $error, $_POST)));
	};
	
	$check(($type = searchKey($_POST, "autonomous", "alias")) !== null, "");
	
	
	if($type == "autonomous") {
		$check(post("catchAllType") == "NONE" || post("catchAllType") == "ADDRESS", "");
		if(post("catchAllType") == "NONE") {
			unset($_POST["domain"]);
			unset($_POST["address"]);
			$catchAllType = "NONE";
			$catchAllTarget = null;
		} else if(post("catchAllType") == "ADDRESS") {
			unset($_POST["domain"]);
			$check(validEmail(post("address")), "Invalid address.");
			$catchAllType = "ADDRESS";
			$catchAllTarget = post("address");
		}
	} else if($type == "alias") {
		unset($_POST["address"]);
		$check(validDomain(post("domain")), "Invalid domain.");
		$check(domainName($domainID) != post("domain"), "Please select a non-loop redirect target.");
		$catchAllType = "DOMAIN";
		$catchAllTarget = post("domain");
	}
	
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdSet("mailDomain", array("domainID"=>$domainID), array("catchAllType"=>$catchAllType, "catchAllTarget"=>$catchAllTarget));
	
	updateMail(customerID());
	
	redirect("mail/domain.php?id=$domainID");
}

main();

?>