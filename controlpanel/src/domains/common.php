<?php

require_once(dirname(__FILE__) . "/../common.php");
require_once(dirname(__FILE__) . "/../ticket/api.php");

function doDomains()
{
	useComponent("domains");
	$GLOBALS["menuComponent"] = "domains";
}

function doDomain($domainID)
{
	doDomains();
	useCustomer($GLOBALS["database"]->stdGetTry("dnsDomain", array("domainID"=>$domainID), "customerID", false));
	$rootDomainID = rootDomainID($domainID);
	if(domainsDomainStatus($rootDomainID) == "expired") {
		removeDomain($rootDomainID);
		useCustomer(false);
	}
}

function domainBreadcrumbs($domainID, $postfix = array())
{
	$parts = array();
	$nextDomainID = $domainID;
	while(true) {
		$domain = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$nextDomainID), array("name", "parentDomainID", "customerID", "domainTldID"));
		if($domain["parentDomainID"] === null) {
			$parts[] = array("id"=>$nextDomainID, "name"=>$domain["name"], "show"=>true);
			$tld = $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$domain["domainTldID"]), "name");
			$parts[] = array("id"=>0, "name"=>$tld, "show"=>false);
			break;
		} else if($domain["customerID"] != customerID()) {
			$parts[] = array("id"=>$nextDomainID, "name"=>domainsFormatDomainName($nextDomainID), "show"=>false);
			break;
		} else {
			$parts[] = array("id"=>$nextDomainID, "name"=>$domain["name"], "show"=>true);
			$nextDomainID = $domain["parentDomainID"];
		}
	}
	
	$parts = array_reverse($parts);
	$crumbs = array();
	$crumbs[] = array("name"=>"Domains", "url"=>"{$GLOBALS["root"]}domains/");
	$domainPostfix = "";
	while(count($parts) > 0) {
		$part = array_shift($parts);
		if($part["show"]) {
			$crumbs[] = array("name"=>$part["name"] . $domainPostfix, "url"=>"{$GLOBALS["root"]}domains/domain.php?id={$part["id"]}");
		}
		$domainPostfix = "." . $part["name"] . $domainPostfix;
	}
	return breadcrumbs(array_merge($crumbs, $postfix));
}

function domainsList()
{
	$output = "";
	$domainIDs = $GLOBALS["database"]->stdList("dnsDomain", array("customerID"=>customerID(), "parentDomainID"=>null), "domainID");
	
	$domains = array();
	foreach($domainIDs as $domainID) {
		if(domainsDomainStatus($domainID) == "expired") {
			removeDomain($domainID);
			continue;
		}
		$domainName = domainsFormatDomainName($domainID);
		$status = domainsDomainStatusDescription($domainID);
		$domains[$domainName] = array("domainID"=>$domainID, "name"=>$domainName, "status"=>$status);
	}
	ksort($domains);
	
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Domain</th><th>Status</th></tr>
</thead>
<tbody>
HTML;
	foreach($domains as $domain) {
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}domains/domain.php?id={$domain["domainID"]}\">{$domain["name"]}</a></td><td>{$domain["status"]}</td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function subDomainsList($parentDomainID)
{
	$output = "";
	
	$domains = array();
	foreach($GLOBALS["database"]->stdList("dnsDomain", array("customerID"=>customerID(), "parentDomainID"=>$parentDomainID), array("domainID", "parentDomainID", "name")) as $domain) {
		$domainName = domainsFormatDomainName($domain["domainID"]);
		$domains[$domainName] = array("domainID"=>$domain["domainID"], "name"=>$domainName);
	}
	ksort($domains);
	
	if(count($domains) == 0) {
		return "";
	}
	
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Subdomains</th></tr>
</thead>
<tbody>
HTML;
	foreach($domains as $domain) {
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}domains/domain.php?id={$domain["domainID"]}\">{$domain["name"]}</a></td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function domainDetail($domainID)
{
	$tldID = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "domainTldID");
	$domainName = domainsFormatDomainName($domainID);
	$domainNameHtml = htmlentities($domainName);
	
	$output  = "<div class=\"operation\">\n";
	$output .= "<h2>Domain $domainNameHtml</h2>";
	$output .= "<table>";
	$output .= "<tr><th>Name:</th><td class=\"stretch\">{$domainNameHtml}</td></tr>";
	if(!isSubDomain($domainID)) {
		$status = domainsDomainStatusDescription($domainID);
		$price = formatPrice(billingDomainPrice($tldID));
		$output .= "<tr><th>Status:</th><td class=\"stretch\">{$status}</td></tr>";
		$output .= "<tr><th>Price per year:</th><td class=\"stretch\">{$price}</td></tr>";
	}
	$output .= "</table>";
	$output .= "</div>";
	return $output;
}

function domainRemoval($domainID)
{
	$content = "";
	
	if(isSubDomain($domainID)) {
		$content .= trivialActionForm("{$GLOBALS["root"]}domains/removedomain.php?id=$domainID", "", "Remove subdomain", null, "<p>Remove this subdomain and all it's subdomains.</p>");
	} else {
		$status = domainsDomainStatus($domainID);
		if($status == "active") {
			$autorenew = domainsDomainAutorenew($domainID);
			$expiredate = domainsDomainExpiredate($domainID);
			if($autorenew === null) {
				// nothing
			} else if($autorenew) {
				$content .= trivialActionForm("{$GLOBALS["root"]}domains/expiredomain.php?id=$domainID&action=disable", "", "Withdraw domain", null, "<p>This will cause the domain to expire on $expiredate.</p>");
			} else {
				$content .= trivialActionForm("{$GLOBALS["root"]}domains/expiredomain.php?id=$domainID&action=enable", "", "Restore domain", null, "<p>This will retain the domain indefinitely.</p><p>If the domain is not restored, it will expire on $expiredate.</p>");
			}
		} else if($status == "activeforever") {
			$content .= trivialActionForm("{$GLOBALS["root"]}domains/expiredomain.php?id=$domainID&action=delete", "", "Delete domain", null, "<p>This will delete the domain immediately.</p>");
		}
	}
	
	return $content;
}

function addDomainForm($error = "", $tldID = null, $name = null)
{
	if($error === null) {
		$price = formatPrice(billingDomainPrice($tldID));
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$messageHtml .= "<p class=\"billing\">Registering this domain will cost $price per year</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = "readonly=\"readonly\"";
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
	}
	
	$tldHtml = "";
	if($readonly == "") {
		$tldHtml .= "<select name=\"tldID\">";
		$tlds = $GLOBALS["database"]->stdList("infrastructureDomainTld", array("active"=>1), array("domainTldID", "name"));
		foreach($tlds as $tld) {
			if($tldID == $tld["domainTldID"]) {
				$selected = "selected=\"selected\"";
			} else {
				$selected = "";
			}
			$tldHtml .= "<option value=\"{$tld["domainTldID"]}\" $selected>" . htmlentities($tld["name"]) . " (" . formatPrice(billingDomainPrice($tld["domainTldID"])) . " / year)</option>\n";
		}
		$tldHtml .= "</select>";
	} else {
		$tldName = $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$tldID), "name");
		$tldHtml .= "<input type=\"hidden\" name=\"tldID\" value=\"$tldID\" />{$tldName}";
	}
	
	$nameValue = inputValue($name);
	return <<<HTML
<div class="operation">
<h2>Register new domain</h2>
$messageHtml
<form action="adddomain.php" method="post">
$confirmHtml
<table>
<tr><th>Domain name:</th><td class="stretch"><input type="text" name="name" $nameValue $readonly /></td><td style="white-space: nowrap;">.$tldHtml</td></tr>
<tr class="submit"><td colspan="3"><input type="submit" value="Register domain"></td></tr>
</table>
</form>
</div>

HTML;
}

function addSubdomainForm($domainID, $error = "", $name = null)
{
	$addressType = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "addressType");
	if($addressType == "DELEGATION" || $addressType == "TREVA-DELEGATION") {
		return <<<HTML
<div class="operation">
<h2>Add subdomain</h2>
<table>
<tr><td>Adding subdomains is not available for this domain, because the domain address is configured as "Hosted externally".</td></tr>
</table>
</form>
</div>

HTML;
	}
	
	$parentName = domainsFormatDomainName($domainID);
	$parentNameHtml = htmlentities($parentName);
	
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = "readonly=\"readonly\"";
		$stub = false;
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
		$stub = false;
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
		$stub = false;
	}
	
	$nameValue = inputValue($name);
	return <<<HTML
<div class="operation">
<h2>Add subdomain</h2>
$messageHtml
<form action="addsubdomain.php?id=$domainID" method="post">
$confirmHtml
<table>
<tr><th>Subdomain name:</th><td class="stretch"><input type="text" name="name" $nameValue $readonly /></td><td style="white-space: nowrap;">.$parentNameHtml</td></tr>
<tr class="submit"><td colspan="3"><input type="submit" value="Add"></td></tr>
</table>
</form>
</div>

HTML;
}

function mailTypeSubformNone($confirm, $selected)
{
	$readonlyHtml = ($confirm ? "readonly=\"readonly\"" : "");
	$currentlySelectedHtml = $selected ? "Currently selected:" : "";
	$currentlySelectedClass = $selected ? "selected" : "";
	
	return <<<HTML
<div class="operation $currentlySelectedClass">
<h3>$currentlySelectedHtml Disable email</h3>
Disable email for this domain.
<table>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Disable email" /></td></tr>
</table>
</div>

HTML;
}

function mailTypeSubformTreva($confirm, $selected)
{
	$readonlyHtml = ($confirm ? "readonly=\"readonly\"" : "");
	$currentlySelectedHtml = $selected ? "Currently selected:" : "";
	$currentlySelectedClass = $selected ? "selected" : "";
	
	return <<<HTML
<div class="operation $currentlySelectedClass">
<h3>$currentlySelectedHtml Our email servers</h3>
Enable email for this domain, using our mailservers. If you are unsure and you want email, choose this option.
<table>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Use our email servers" /></td></tr>
</table>
</div>

HTML;
}

function mailTypeSubformCustom($confirm, $selected, $mailservers)
{
	$readonlyHtml = ($confirm ? "readonly=\"readonly\"" : "");
	$currentlySelectedHtml = $selected ? "Currently selected:" : "";
	$currentlySelectedClass = $selected ? "selected" : "";
	
	$count = 0;
	$mailserversHtml = "";
	if($mailservers == null) {
		$mailservers = array();
	}
	if($selected) {
		foreach($mailservers as $mailserver) {
			$value = inputValue($mailserver);
			$mailserversHtml .= "<tr><th>Mail server:</th><td><input class=\"customMailServerField\" type=\"text\" name=\"mailserver-$count\" $value $readonlyHtml /></td></tr>";
			$count++;
		}
	}
	if($count == 0) {
		$mailserversHtml .= "<tr><th>Mail server:</th><td><input class=\"customMailServerField\" type=\"text\" name=\"mailserver-$count\" $readonlyHtml /></td></tr>";
		$count++;
	}
	if(!$confirm) {
		$mailserversHtml .= "<tr><th>Mail server:</th><td><input class=\"customMailServerField\" type=\"text\" name=\"mailserver-$count\" /></td></tr>";
		$count++;
		while($count < 10) {
			$mailserversHtml .= "<tr class=\"jsDelete\"><th>Mail server:</th><td><input class=\"customMailServerField\" type=\"text\" name=\"mailserver-$count\" /></td></tr>";
			$count++;
		}
		$js = <<<JS
<script type="text/javascript">
$(document).ready(function() {
	$(".jsDelete").remove();
	$(".customMailServerField").change(addCustomMailServer);
});

function addCustomMailServer(event) {
	$("#customMailServerList").append('<tr><th>Mail server:</th><td><input class="customMailServerField" type="text" name="mailserver-' + $(".customMailServerField").length + '" id="latestMailServerField" /></td></tr>');
	$("#latestMailServerField").change(addCustomMailServer);
	$("#latestMailServerField").removeAttr("id");
	$(this).unbind(event);
}
</script>

JS;
	} else {
		$js = "";
	}
	return <<<HTML
$js
<div class="operation $currentlySelectedClass">
<h3>$currentlySelectedHtml Your email servers</h3>
Enable email for this domain, using your own mailservers. You have to make sure that these are configured correctly to accept email for this domain.
<table>
<tbody id="customMailServerList">
$mailserversHtml
</tbody>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Use your email servers" /></td></tr>
</table>
</div>

HTML;
}

function mailTypeSubform($confirm = false, $type = null, $mailservers = null)
{
	$output = "";
	
	$noneHtml = mailTypeSubformNone($confirm, $type == "NONE");
	if($type == "NONE") {
		$output = $noneHtml . "\n" . $output;
	} else if(!$confirm) {
		$output .= "\n" . $noneHtml;
	}
	
	$trevaHtml = mailTypeSubformTreva($confirm, $type == "TREVA");
	if($type == "TREVA") {
		$output = $trevaHtml . "\n" . $output;
	} else if(!$confirm) {
		$output .= "\n" . $trevaHtml;
	}
	
	$customHtml = mailTypeSubformCustom($confirm, $type == "CUSTOM", $mailservers);
	if($type == "CUSTOM") {
		$output = $customHtml . "\n" . $output;
	} else if(!$confirm) {
		$output .= "\n" . $customHtml;
	}
	
	return $output;
}

function editMailTypeForm($domainID, $error = "", $type = null, $mailservers = null)
{
	$addressType = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "addressType");
	if($addressType == "CNAME" || $addressType == "DELEGATION" || $addressType == "TREVA-DELEGATION") {
		$type = $addressType == "CNAME" ? "\"CNAME to another site\"" : "\"Hosted externally\"";
		return <<<HTML
<div class="operation">
<h2>Email configuration</h2>
<table>
<tr><td>Email is not available for this domain, because the domain address is configured as $type.</td></tr>
</table>
</form>
</div>

HTML;
	}
	
	$domainName = domainsFormatDomainName($domainID);
	$domainNameHtml = htmlentities($domainName);
	
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = "readonly=\"readonly\"";
		$stub = false;
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
		$stub = false;
	} else if($error == "STUB") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
		$stub = true;
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
		$stub = false;
	}
	if($type == null) {
		$type = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "mailType");
		if($type == "CUSTOM") {
			$mailservers = $GLOBALS["database"]->stdList("dnsMailServer", array("domainID"=>$domainID), "name", array("priority"=>"ASC"));
		}
	}
	
	if($stub) {
		if($type == "NONE") {
			return <<<HTML
<div class="operation">
<h2>Email configuration</h2>
<form action="editmail.php?id=$domainID" method="post">
<table>
<tr><th>Status:</th><td class="stretch">No email is configured for this domain.</td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		} else if($type == "TREVA") {
			return <<<HTML
<div class="operation">
<h2>Email configuration</h2>
<form action="editmail.php?id=$domainID" method="post">
<table>
<tr><th>Status:</th><td class="stretch">Hosted by Treva</td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		} else if($type == "CUSTOM") {
			$mailserversHtml = "";
			foreach($mailservers as $mailserver) {
				$mailserverHtml = htmlentities($mailserver);
				if($mailserversHtml != "") {
					$mailserversHtml .= "<tr>";
				}
				$mailserversHtml .= "<td class=\"stretch\">$mailserverHtml</td></tr>";
			}
			if($mailserversHtml == "") {
				$mailserversHtml = "<td>No servers configured.</td></tr>";
			}
			$rowspan = max(count($mailservers), 1);
			return <<<HTML
<div class="operation">
<h2>Email configuration</h2>
<form action="editmail.php?id=$domainID" method="post">
<table>
<tr><th>Status:</th><td class="stretch">Hosted externally</td></tr>
<tr><th rowspan="$rowspan">Mailservers:</th>$mailserversHtml
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		}
		$operationsHtml = "";
	} else {
		$operationsHtml = mailTypeSubform($readonly != "", $type, $mailservers);
	}
	
	return <<<HTML
<div class="operation">
<h2>Edit email configuration for $domainNameHtml</h2>
$messageHtml
<form action="editmail.php?id=$domainID" method="post">
$confirmHtml

$operationsHtml

</form>
</div>

HTML;
}

function mailTypeFromTitle($title)
{
	if($title == "Disable email") {
		return "NONE";
	} else if($title == "Use our email servers") {
		return "TREVA";
	} else if($title == "Use your email servers") {
		return "CUSTOM";
	} else {
		return null;
	}
}

function addressTypeSubformInherit($confirm, $selected)
{
	$readonlyHtml = ($confirm ? "readonly=\"readonly\"" : "");
	$currentlySelectedHtml = $selected ? "Currently selected:" : "";
	$currentlySelectedClass = $selected ? "selected" : "";
	
	return <<<HTML
<div class="operation $currentlySelectedClass">
<h3>$currentlySelectedHtml Inherit</h3>
Inherit from parent domain.
<table>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Inherit from parent" /></td></tr>
</table>
</div>

HTML;
}

function addressTypeSubformTrevaWeb($confirm, $selected)
{
	$readonlyHtml = ($confirm ? "readonly=\"readonly\"" : "");
	$currentlySelectedHtml = $selected ? "Currently selected:" : "";
	$currentlySelectedClass = $selected ? "selected" : "";
	
	return <<<HTML
<div class="operation $currentlySelectedClass">
<h3>$currentlySelectedHtml Our web servers</h3>
Use our web servers.
<table>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Use our web servers" /></td></tr>
</table>
</div>

HTML;
}

function addressTypeSubformIP($confirm, $selected, $ipv4, $ipv6)
{
	$readonlyHtml = ($confirm ? "readonly=\"readonly\"" : "");
	$currentlySelectedHtml = $selected ? "Currently selected:" : "";
	$currentlySelectedClass = $selected ? "selected" : "";
	
	$ipv4Value = inputValue($ipv4);
	$ipv6Value = inputValue($ipv6);
	
	return <<<HTML
<div class="operation $currentlySelectedClass">
<h3>$currentlySelectedHtml Custom IPs</h3>
Use these custom IPs.
<table>
<tr><th>ipv4-address:</th><td><input type="text" name="ipv4" $ipv4Value $readonlyHtml /></td></tr>
<tr><th>ipv6-address:</th><td><input type="text" name="ipv6" $ipv6Value $readonlyHtml /></td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Use these IPs" /></td></tr>
</table>
</div>

HTML;
}

function addressTypeSubformCname($confirm, $selected, $cname)
{
	$readonlyHtml = ($confirm ? "readonly=\"readonly\"" : "");
	$currentlySelectedHtml = $selected ? "Currently selected:" : "";
	$currentlySelectedClass = $selected ? "selected" : "";
	
	$cnameValue = inputValue($cname);
	
	return <<<HTML
<div class="operation $currentlySelectedClass">
<h3>$currentlySelectedHtml CNAME</h3>
Use this cname.
<table>
<tr><th>CNAME:</th><td><input type="text" name="cname" $cnameValue $readonlyHtml /></td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Use cname" /></td></tr>
</table>
</div>

HTML;
}


function addressTypeSubformDelegation($confirm, $selected, $delecationServers)
{
	$readonlyHtml = ($confirm ? "readonly=\"readonly\"" : "");
	$currentlySelectedHtml = $selected ? "Currently selected:" : "";
	$currentlySelectedClass = $selected ? "selected" : "";
	
	$count = 0;
	$delecationServersHtml = "";
	if($delecationServers == null) {
		$delecationServers = array();
	}
	if($selected) {
		foreach($delecationServers as $delegationServer) {
			$hostnameValue = inputValue($delegationServer["hostname"]);
			$ipv4Value = inputValue($delegationServer["ipv4Address"]);
			$ipv6Value = inputValue($delegationServer["ipv6Address"]);
			$delecationServersHtml .= "<tr><th>Delegation server:</th><td><input class=\"delegationServerField\" type=\"text\" name=\"delegationHostname-$count\" $hostnameValue $readonlyHtml /></td><td><input type=\"text\" name=\"delegationIpv4-$count\" $ipv4Value $readonlyHtml /></td><td><input type=\"text\" name=\"delegationIpv6-$count\" $ipv6Value $readonlyHtml /></td></tr>";
			$count++;
		}
	}
	if($count == 0) {
		$delecationServersHtml .= "<tr><th>Delegation server:</th><td><input class=\"delegationServerField\" type=\"text\" name=\"delegationHostname-$count\" $readonlyHtml /></td><td><input type=\"text\" name=\"delegationIpv4-$count\" $readonlyHtml /></td><td><input type=\"text\" name=\"delegationIpv6-$count\" $readonlyHtml /></td></tr>";
		$count++;
	}
	if(!$confirm) {
		$delecationServersHtml .= "<tr><th>Delegation server:</th><td><input class=\"delegationServerField\" type=\"text\" name=\"delegationHostname-$count\" /></td><td><input type=\"text\" name=\"delegationIpv4-$count\" /></td><td><input type=\"text\" name=\"delegationIpv6-$count\" /></td></tr>";
		$count++;
		while($count < 10) {
			$delecationServersHtml .= "<tr class=\"jsDelete\"><th>Delegation server:</th><td><input class=\"delegationServerField\" type=\"text\" name=\"delegationHostname-$count\" /></td><td><input type=\"text\" name=\"delegationIpv4-$count\" /></td><td><input type=\"text\" name=\"delegationIpv6-$count\" /></td></tr>";
			$count++;
		}
		$js = <<<JS
<script type="text/javascript">
$(document).ready(function() {
	$(".jsDelete").remove();
	$(".delegationServerField").change(addDelegationServer);
});

function addDelegationServer(event) {
	$("#DelegationServerList").append('<tr><th>Delegation server:</th><td><input id="latestDelegationServerField" class="delegationServerField" type="text" name="delegationHostname-' + $(".delegationServerField").length + '" /></td><td><input type="text" name="delegationIpv4-' + $(".delegationServerField").length + '" /></td><td><input type="text" name="delegationIpv6-' + $(".delegationServerField").length + '" /></td></tr>');
	$("#latestDelegationServerField").change(addDelegationServer);
	$("#latestDelegationServerField").removeAttr("id");
	$(this).unbind(event);
}
</script>

JS;
	} else {
		$js = "";
	}
	return <<<HTML
$js
<div class="operation $currentlySelectedClass">
<h3>$currentlySelectedHtml Delegation</h3>
Delegate to these servers.
<table>
<tr><td>&nbsp;</td><th>Hostname</th><th>IPv4 address</th><th>IPv6 address</th></tr>
<tbody id="DelegationServerList">
$delecationServersHtml
</tbody>
<tr class="submit"><td colspan="4"><input type="submit" name="type" value="Use delegation" /></td></tr>
</table>
</div>

HTML;
}

function addressTypeSubform($confirm = false, $type = null, $ipv4 = null, $ipv6 = null, $cname = null, $delecationServers = null)
{
	$output = "";
	
	$noneHtml = addressTypeSubformInherit($confirm, $type == "INHERIT");
	if($type == "INHERIT") {
		$output = $noneHtml . "\n" . $output;
	} else if(!$confirm) {
		$output .= "\n" . $noneHtml;
	}
	
	$trevaHtml = addressTypeSubformTrevaWeb($confirm, $type == "TREVA-WEB");
	if($type == "TREVA-WEB") {
		$output = $trevaHtml . "\n" . $output;
	} else if(!$confirm) {
		$output .= "\n" . $trevaHtml;
	}
	
	$customHtml = addressTypeSubformIP($confirm, $type == "IP", $ipv4, $ipv6);
	if($type == "IP") {
		$output = $customHtml . "\n" . $output;
	} else if(!$confirm) {
		$output .= "\n" . $customHtml;
	}
	
	$customHtml = addressTypeSubformCname($confirm, $type == "CNAME", $cname);
	if($type == "CNAME") {
		$output = $customHtml . "\n" . $output;
	} else if(!$confirm) {
		$output .= "\n" . $customHtml;
	}
	
	$customHtml = addressTypeSubformDelegation($confirm, $type == "DELEGATION", $delecationServers);
	if($type == "DELEGATION") {
		$output = $customHtml . "\n" . $output;
	} else if(!$confirm) {
		$output .= "\n" . $customHtml;
	}
	return $output;
}

function editAddressTypeForm($domainID, $error = "", $type = null, $ipv4 = null, $ipv6 = null, $cname = null, $delegationServers = null, $warning = null) // TODO: $warning na error zetten ofzo
{
	$domainName = domainsFormatDomainName($domainID);
	$domainNameHtml = htmlentities($domainName);
	
	if($warning === null) {
		$warningHtml = "";
	} else {
		$warningHtml = $warning;
	}
	
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
		$confirmHtml = "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n";
		$readonly = "readonly=\"readonly\"";
		$stub = false;
	} else if($error == "") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
		$stub = false;
	} else if($error == "STUB") {
		$messageHtml = "";
		$confirmHtml = "";
		$readonly = "";
		$stub = true;
	} else {
		$messageHtml = "<p class=\"error\">" . htmlentities($error) . "</p>\n";
		$confirmHtml = "";
		$readonly = "";
		$stub = false;
	}
	if($type == null) {
		$type = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "addressType");
		if($type == "IP") {
			$ipv4 = implode(" ", $GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID, "type"=>"A"), "value", array("recordID"=>"ASC")));
			$ipv6 = implode(" ", $GLOBALS["database"]->stdList("dnsRecord", array("domainID"=>$domainID, "type"=>"AAAA"), "value", array("recordID"=>"ASC")));
		} else if($type == "CNAME") {
			$cname = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "cnameTarget");
		} else if($type == "DELEGATION") {
			$delegationServers = $GLOBALS["database"]->stdList("dnsDelegatedNameServer", array("domainID"=>$domainID), array("hostname", "ipv4Address", "ipv6Address"), array("nameServerID"=>"ASC"));
		}
	}
	
	if($stub) {
		if($type == "NONE") {
			return <<<HTML
<div class="operation">
<h2>Address configuration</h2>
<form action="editaddress.php?id=$domainID" method="post">
<table>
<tr><th>Status:</th><td class="stretch">No address settings.</td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		} else if($type == "INHERIT") {
			return <<<HTML
<div class="operation">
<h2>Address configuration</h2>
<form action="editaddress.php?id=$domainID" method="post">
<table>
<tr><th>Status:</th><td class="stretch">Address settings are inherrited from the parent domain.</td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		} else if($type == "TREVA-WEB") {
			return <<<HTML
<div class="operation">
<h2>Address configuration</h2>
<form action="editaddress.php?id=$domainID" method="post">
<table>
<tr><th>Status:</th><td class="stretch">Hosted by the Treva webservers</td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		} else if($type == "IP") {
			if($ipv4 == "") {
				$ipv4 = "None configured";
			}
			if($ipv6 == "") {
				$ipv6 = "None configured";
			}
			return <<<HTML
<div class="operation">
<h2>Address configuration</h2>
<form action="editaddress.php?id=$domainID" method="post">
<table>
<tr><th>Status:</th><td class="stretch">Hosted on external IP addresses</td></tr>
<tr><th>IPv4</th><td class="stretch">$ipv4</td></tr>
<tr><th>IPv6</th><td class="stretch">$ipv6</td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		} else if($type == "CNAME") {
			return <<<HTML
<div class="operation">
<h2>Address configuration</h2>
<form action="editaddress.php?id=$domainID" method="post">
<table>
<tr><th>Status:</th><td class="stretch">CNAME to another site</td></tr>
<tr><th>CNAME:</th><td class="stretch">$cname</td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		} else if($type == "DELEGATION") {
			$delegationServersHtml = "";
			foreach($delegationServers as $delegationServer) {
				$hostnameHtml = htmlentities($delegationServer["hostname"]);
				$ipv4Html = htmlentities($delegationServer["ipv4Address"]);
				$ipv6Html = htmlentities($delegationServer["ipv6Address"]);
				$delegationServersHtml .= "<tr><td>&nbsp;</td><td class=\"stretch\">$hostnameHtml</td><td>$ipv4Html</td><td>$ipv6Html</td></tr>";
			}
			if($delegationServersHtml == "") {
				$delegationServersHtml = "<td colspan=\"4\">No servers configured.</td></tr>";
			}
			
			return <<<HTML
<div class="operation">
<h2>Address configuration</h2>
<form action="editaddress.php?id=$domainID" method="post">
<table>
<tr><th>Status:</th><td class="stretch" colspan="3">Hosted externally</td></tr>
<tr><th>Delegation servers:</th><th>Hostname</th><th>IPv4</th><th>IPv6</th></tr>
$delegationServersHtml
<tr class="submit"><td colspan="4"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		} else if($type == "TREVA-DELEGATION") {
			return <<<HTML
<div class="operation">
<h2>Address configuration</h2>
<form action="editaddress.php?id=$domainID" method="post">
<table>
<tr><th>Status:</th><td class="stretch">Internal delegation</td></tr>
<tr class="submit"><td colspan="2"><input type="submit" name="type" value="Edit" /></td></tr>
</table>
</form>
</div>

HTML;
		}
		$operationsHtml = "";
	} else {
		$operationsHtml = addressTypeSubform($readonly != "", $type, $ipv4, $ipv6, $cname, $delegationServers);
	}
	
	return <<<HTML
<div class="operation">
<h2>Edit address configuration for $domainNameHtml</h2>
$messageHtml
$warningHtml
<form action="editaddress.php?id=$domainID" method="post">
$confirmHtml

$operationsHtml

</form>
</div>

HTML;
}

function subdomains($domainID) {
	$subDomains = $GLOBALS["database"]->stdList("dnsDomain", array("parentDomainID"=>$domainID), "domainID");
	$subSubDomains = array();
	foreach($subDomains as $subDomainID) {
		$subSubDomains = array_merge($subSubDomains, subdomains($subDomainID));
	}
	return array_merge($subDomains, $subSubDomains);
}

function addressTypeFromTitle($title)
{
	if($title == "Inherit from parent") {
		return "INHERIT";
	} else if($title == "Use our web servers") {
		return "TREVA-WEB";
	} else if($title == "Use these IPs") {
		return "IP";
	} else if($title == "Use cname") {
		return "CNAME";
	} else if($title == "Use delegation") {
		return "DELEGATION";
	} else {
		return null;
	}
}

function removeDomain($domainID)
{
	foreach($GLOBALS["database"]->stdList("dnsDomain", array("parentDomainID"=>$domainID), "domainID") as $subDomainID) {
		removeDomain($subDomainID);
	}
	$GLOBALS["database"]->stdDel("dnsDelegatedNameServer", array("domainID"=>$domainID));
	$GLOBALS["database"]->stdDel("dnsMailServer", array("domainID"=>$domainID));
	$GLOBALS["database"]->stdDel("dnsRecord", array("domainID"=>$domainID));
	$GLOBALS["database"]->stdDel("dnsDomain", array("domainID"=>$domainID));
}

function rootDomainID($domainID)
{
	$parentDomainID = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "parentDomainID");
	if($parentDomainID == null) {
		return $domainID;
	} else {
		return rootDomainID($parentDomainID);
	}
}

function isSubDomain($domainID)
{
	return $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "parentDomainID") != null;
}

function validDomainPart($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	if(preg_match('/^[_-a-zA-Z0-9]*$/', $name) != 1) {
		return false;
	}
	return true;
}

function validDomain($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	if(preg_match('/^[_-a-zA-Z0-9]+(\\.[_-a-zA-Z0-9]+)*$/', $name) != 1) {
		return false;
	}
	return true;
}

function validIPv4($ip)
{
	if(count(explode(".", $ip)) != 4) {
		return false;
	}
	foreach(explode(".", $ip) as $part) {
		if(!ctype_digit($part)) {
			return false;
		}
		if($part < 0 || $part > 255) {
			return false;
		}
	}
	return true;
}

function validIPv6($ip) // TODO
{
	$parts = explode(":", $ip);
	if(count($parts) > 8) {
		return false;
	}
	$emptyFound = false;
	for($i = 1; $i < count($parts) - 1; $i++) {
		if($parts[$i] == "") {
			if($emptyFound) {
				return false;
			} else {
				$emptyFound = true;
			}
		}
	}
	if(!$emptyFound && count($parts) != 8) {
		return false;
	}
	foreach($parts as $part) {
		if(strlen($part) > 4) {
			return false;
		}
		for($i = 0; $i < strlen($part); $i++) {
			if(strpos($part[$i], "1234567890abcdefABCDEF") === false) {
				return false;
			}
		}
	}
	return true;
}

?>