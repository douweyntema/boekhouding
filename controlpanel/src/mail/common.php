<?php

require_once(dirname(__FILE__) . "/../common.php");

function doMail()
{
	useComponent("mail");
	$GLOBALS["menuComponent"] = "mail";
}

function doMailDomain($domainID)
{
	doMail();
	useCustomer($GLOBALS["database"]->stdGetTry("mailDomain", array("domainID"=>$domainID), "customerID", false));
}

function doMailAlias($aliasID)
{
	$domainID = $GLOBALS["database"]->stdGetTry("mailAlias", array("aliasID"=>$aliasID), "domainID", false);
	doMailDomain($domainID);
}

function doMailAddress($addressID)
{
	$domainID = $GLOBALS["database"]->stdGetTry("mailAddress", array("addressID"=>$addressID), "domainID", false);
	doMailDomain($domainID);
}

function mailBreadcrumbs($postfix = array())
{
	return breadcrumbs(array_merge(array(array("name"=>"Email", "url"=>"{$GLOBALS["root"]}mail/")), $postfix));
}

function domainBreadcrumbs($domainID, $postfix = array())
{
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	return mailBreadcrumbs(array_merge(array(array("name"=>$domain, "url"=>"{$GLOBALS["root"]}mail/domain.php?id=$domainID")), $postfix));
}

function updateMail($customerID)
{
	$mailSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "mailSystemID");
	$GLOBALS["database"]->stdIncrement("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "version", 1000000000);
	
	$hosts = $GLOBALS["database"]->stdList("infrastructureMailServer", array("mailSystemID"=>$mailSystemID), "hostID");
	updateHosts($hosts, "update-treva-dovecot");
	updateHosts($hosts, "update-treva-exim");
}

function aliasNotFound($aliasID)
{
	header("HTTP/1.1 404 Not Found");
	
	die("Mail alias #$aliasID not found");
}

function mailDomainsList()
{
	$output = "";
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Domain</th><th># mailboxes</th><th># aliasses</th></tr>
</thead>
<tbody>
HTML;
	foreach($GLOBALS["database"]->stdList("mailDomain", array("customerID"=>customerID()), array("domainID", "name"), array("name"=>"asc")) as $domain) {
		$numMailboxes = count($GLOBALS["database"]->stdList("mailAddress", array("domainID"=>$domain["domainID"]), "addressID"));
		$numAliasses = count($GLOBALS["database"]->stdList("mailAlias", array("domainID"=>$domain["domainID"]), "aliasID"));
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}mail/domain.php?id={$domain["domainID"]}\">{$domain["name"]}</a></td><td>$numMailboxes</td><td>$numAliasses</td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function mailboxList($domainID)
{
	$output = "";
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Mailbox</th><th>Quota</th></tr>
</thead>
<tbody>
HTML;
	foreach($GLOBALS["database"]->stdList("mailAddress", array("domainID"=>$domainID), array("addressID", "localpart", "quota"), array("localpart"=>"asc")) as $mailbox) {
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}mail/mailbox.php?id={$mailbox["addressID"]}\">{$mailbox["localpart"]}@$domain</a></td><td>{$mailbox["quota"]} MB</td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function mailAliasList($domainID)
{
	$output = "";
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Alias</th><th>Forward to</th></tr>
</thead>
<tbody>
HTML;
	foreach($GLOBALS["database"]->stdList("mailAlias", array("domainID"=>$domainID), array("aliasID", "localpart", "targetAddress"), array("localpart"=>"asc")) as $alias) {
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}mail/alias.php?id={$alias["aliasID"]}\">{$alias["localpart"]}@$domain</a></td><td>{$alias["targetAddress"]}</td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function editMailAliasForm($aliasID, $error, $targetAddress)
{
	$targetAddressValue = inputValue($targetAddress);
	$domainID = $GLOBALS["database"]->stdGet("mailAlias", array("aliasID"=>$aliasID), "domainID");
	$alias = $GLOBALS["database"]->stdGet("mailAlias", array("aliasID"=>$aliasID), "localpart");
	$domainName = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
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
	
	return <<<HTML
<div class="operation">
<h2>Change alias</h2>
$messageHtml
<form action="editalias.php?id=$aliasID" method="post">
$confirmHtml
<table>
<tr>
<th>Alias:</th>
<td class="nowrap">$alias@$domainName</td>
</tr>
<tr>
<th>Target address:</th>
<td class="stretch" colspan="2"><input type="text" name="targetAddress" $readonly $targetAddressValue /></td>
</tr>
<tr class="submit"><td colspan="3"><input type="submit" value="Save" /></td></tr>
</table>
</form>
</div>

HTML;
}

function addMailAliasForm($domainID, $error, $alias, $targetAddress)
{
	$aliasValue = inputValue($alias);
	$targetAddressValue = inputValue($targetAddress);
	$domainName = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
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
	
	return <<<HTML
<div class="operation">
<h2>Add alias</h2>
$messageHtml
<form action="addalias.php?id=$domainID" method="post">
$confirmHtml
<table>
<tr>
<th>Alias:</th>
<td class="stretch"><input type="text" name="localpart" $readonly $aliasValue /></td>
<td class="nowrap">@{$domainName}</td>
</tr>
<tr>
<th>Target address:</th>
<td colspan="2" class="stretch"><input type="text" name="targetAddress" $readonly $targetAddressValue /></td>
</tr>
<tr class="submit"><td colspan="3"><input type="submit" value="Save" /></td></tr>
</table>
</form>
</div>

HTML;
}

function addMailDomainForm($error, $domainName)
{
	$domainNameValue = inputValue($domainName);
	
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
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
	
	return <<<HTML
<div class="operation">
<h2>Add domain</h2>
$messageHtml
<form action="adddomain.php" method="post">
$confirmHtml
<table>
<tr>
<th>Domain name:</th>
<td class="stretch"><input type="text" name="domainName" $readonly $domainNameValue /></td>
</tr>
<tr class="submit"><td colspan="3"><input type="submit" value="Save" /></td></tr>
</table>
</form>
</div>

HTML;
}

function addMailboxForm($domainID, $error, $localpart, $password, $quota, $spamQuota, $virusQuota, $spambox, $virusbox)
{
	$localpartValue = inputValue($localpart);
	$quotaValue = inputValue($quota);
	$spamQuotaValue = $spamQuota === null ? inputValue(100) : inputValue($spamQuota);
	$virusQuotaValue = $virusQuota === null ? inputValue(100) : inputValue($virusQuota);
	$domainName = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
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
	
	if($readonly == "") {
		$passwordHtml = <<<HTML
<tr>
<th>Password:</th>
<td colspan="2"><input type="password" name="password1" /></td>
</tr>
<tr>
<th>Confirm password:</th>
<td colspan="2"><input type="password" name="password2" /></td>
</tr>

HTML;
	} else {
		$encryptedPassword = encryptPassword($password);
		$masked = str_repeat("*", strlen($password));
		$passwordHtml = <<<HTML
<tr>
<th>Password:</th>
<td colspan="2"><input type="password" value="$masked" readonly="readonly" /><input type="hidden" name="encryptedPassword" value="$encryptedPassword" /></td>
</tr>

HTML;
	}
	
	$spamboxNospamSelected = "";
	$spamboxInboxSelected = "";
	$spamboxfolderSelected = "";
	$spamboxFolderValue = inputValue("spam");
	if($spambox == "none" || $spambox == null) {
		$spamboxNospamSelected = "checked=\"checked\"";
	} else if($spambox == "inbox") {
		$spamboxInboxSelected = "checked=\"checked\"";
	} else {
		$spamboxfolderSelected = "checked=\"checked\"";
		$spamboxFolderValue = inputValue($spambox);
	}
	
	$virusboxNospamSelected = "";
	$virusboxInboxSelected = "";
	$virusboxfolderSelected = "";
	$virusboxFolderValue = inputValue("virus");
	if($virusbox == "none" || $virusbox == null) {
		$virusboxNospamSelected = "checked=\"checked\"";
	} else if($virusbox == "inbox") {
		$virusboxInboxSelected = "checked=\"checked\"";
	} else {
		$virusboxfolderSelected = "checked=\"checked\"";
		$virusboxFolderValue = inputValue($virusbox);
	}
	
	return <<<HTML
<div class="operation">
<h2>Add mailbox</h2>
$messageHtml
<form action="addmailbox.php?id=$domainID" method="post">
$confirmHtml
<table>
<tr>
<th>Mailbox:</th>
<td class="nowrap"><input type="text" name="localpart" $readonly $localpartValue /></td>
<td>@{$domainName}</td>
</tr>
$passwordHtml
<tr>
<th>Quota:</th>
<td><input type="text" name="quota" $readonly $quotaValue /></td><td>MB</td>
</tr>
<tr>
<th>Spambox:</th>
<td colspan="2">
<label><input type="radio" name="spambox" value="none" $spamboxNospamSelected />No spambox</label><br />
<label><input type="radio" name="spambox" value="inbox" $spamboxInboxSelected />Spam in inbox</label><br />
<label><input type="radio" name="spambox" value="folder" $spamboxfolderSelected />Place spam in the specified folder:</label><br />
<input type="text" name="spambox-folder" id="spambox-folder" $spamboxFolderValue /></td>
</tr>
<tr id="spambox-quota">
<th>Spambox quota:</th>
<td><input type="text" name="spamquota" $readonly $spamQuotaValue /></td><td>MB</td>
</tr>
<tr>
<tr>
<th>Virusbox:</th>
<td colspan="2">
<label><input type="radio" name="virusbox" value="none" $virusboxNospamSelected />No virusbox</label><br />
<label><input type="radio" name="virusbox" value="inbox" $virusboxInboxSelected />Virus in inbox</label><br />
<label><input type="radio" name="virusbox" value="folder" $virusboxfolderSelected />Place virus mails in the specified folder:</label><br />
<input type="text" name="virusbox-folder" id="virusbox-folder" $virusboxFolderValue /></td>
</tr>
<tr id="virusbox-quota">
<th>Spambox quota:</th>
<td><input type="text" name="virusquota" $readonly $virusQuotaValue /></td><td>MB</td>
</tr>
<tr>
<tr class="submit"><td colspan="3"><input type="submit" value="Create mailbox" /></td></tr>
</table>
<script type="text/javascript">
$(document).ready(function(){
	$("input:radio[name=spambox]").change(updateSpambox);
	$("input:radio[name=virusbox]").change(updateVirusbox);
	updateSpambox();
	updateVirusbox();
});

function updateSpambox()
{
	if($("input:radio[name=spambox]:checked").val() == "none") {
		$("#spambox-folder").hide();
		$("#spambox-quota").hide();
	} else if($("input:radio[name=spambox]:checked").val() == "inbox") {
		$("#spambox-folder").hide();
		$("#spambox-quota").hide();
	} else {
		$("#spambox-folder").show();
		$("#spambox-quota").show();
	}
}
function updateVirusbox()
{
	if($("input:radio[name=virusbox]:checked").val() == "none") {
		$("#virusbox-folder").hide();
		$("#virusbox-quota").hide();
	} else if($("input:radio[name=virusbox]:checked").val() == "inbox") {
		$("#virusbox-folder").hide();
		$("#virusbox-quota").hide();
	} else {
		$("#virusbox-folder").show();
		$("#virusbox-quota").show();
	}
}

</script>
</form>
</div>

HTML;
}

function mailboxSummary($mailboxID)
{
	$mailbox = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$mailboxID), array("domainID", "localpart", "spambox", "virusbox", "quota", "spamQuota", "virusQuota", "canUseSmtp", "canUseImap"));
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$mailbox["domainID"]), "name");
	
	if($mailbox["spambox"] === null) {
		$spambox = "No spambox";
	} else if($mailbox["spambox"] == "inbox") {
		$spambox = "inbox";
	} else { 
		if($mailbox["spamQuota"] === null) {
			$spambox = $mailbox["spambox"] . " (no quota)";
		} else {
			$spambox = $mailbox["spambox"] . " (quota " . $mailbox["spamQuota"] . " MiB)";
		}
	}
	
	if($mailbox["virusbox"] === null) {
		$virusbox = "No virusbox";
	} else if($mailbox["virusbox"] == "inbox") {
		$virusbox = "inbox";
	} else {
		if($mailbox["virusQuota"] === null) {
			$virusbox = $mailbox["virusbox"] . " (no quota)";
		} else {
			$virusbox = $mailbox["virusbox"] . " (quota " . $mailbox["virusQuota"] . " MiB)";
		}
	}
	
	$smtp = $mailbox["canUseSmtp"] == 0 ? "Disabled" : "Enabled";
	$imap = $mailbox["canUseImap"] == 0 ? "Disabled" : "Enabled";
	
	return <<<HTML
<div class="operation">
<h2>Mailbox {$mailbox["localpart"]}@$domain</h2>
<table>
<tr><th>Quota</th><td>{$mailbox["quota"]} MB</td></tr>
<tr><th>Spambox</th><td>$spambox</td></tr>
<tr><th>Virusbox</th><td>$virusbox</td></tr>
<tr><th>SMTP</th><td>$smtp</td></tr>
<tr><th>IMAP</th><td>$imap</td></tr>
</table>
</div>
HTML;
}

function editMailboxForm($addressID, $error, $quota, $spamQuota, $virusQuota, $spambox, $virusbox)
{
	$quotaValue = inputValue($quota);
	$spamQuotaValue = $spamQuota === null ? inputValue(100) : inputValue($spamQuota);
	$virusQuotaValue = $virusQuota === null ? inputValue(100) : inputValue($virusQuota);
	$domainID = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$addressID), "domainID");
	$domainName = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n";
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
	
	$spamboxNospamSelected = "";
	$spamboxInboxSelected = "";
	$spamboxfolderSelected = "";
	$spamboxFolderValue = inputValue("spam");
	if($spambox == "none" || $spambox == null) {
		$spamboxNospamSelected = "checked=\"checked\"";
	} else if($spambox == "inbox") {
		$spamboxInboxSelected = "checked=\"checked\"";
	} else {
		$spamboxfolderSelected = "checked=\"checked\"";
		$spamboxFolderValue = inputValue($spambox);
	}
	
	$virusboxNospamSelected = "";
	$virusboxInboxSelected = "";
	$virusboxfolderSelected = "";
	$virusboxFolderValue = inputValue("virus");
	if($virusbox == "none" || $virusbox == null) {
		$virusboxNospamSelected = "checked=\"checked\"";
	} else if($virusbox == "inbox") {
		$virusboxInboxSelected = "checked=\"checked\"";
	} else {
		$virusboxfolderSelected = "checked=\"checked\"";
		$virusboxFolderValue = inputValue($virusbox);
	}
	
	return <<<HTML
<div class="operation">
<h2>Edit mailbox</h2>
$messageHtml
<form action="editmailbox.php?id=$addressID" method="post">
$confirmHtml
<table>
<tr>
<th>Quota:</th>
<td><input type="text" name="quota" $readonly $quotaValue /></td><td>MB</td>
</tr>
<tr>
<th>Spambox:</th>
<td colspan="2">
<label><input type="radio" name="spambox" value="none" $spamboxNospamSelected />No spambox</label><br />
<label><input type="radio" name="spambox" value="inbox" $spamboxInboxSelected />Spam in inbox</label><br />
<label><input type="radio" name="spambox" value="folder" $spamboxfolderSelected />Place spam in the specified folder:</label><br />
<input type="text" name="spambox-folder" id="spambox-folder" $spamboxFolderValue /></td>
</tr>
<tr id="spambox-quota">
<th>Spambox quota:</th>
<td><input type="text" name="spamquota" $readonly $spamQuotaValue /></td><td>MB</td>
</tr>
<tr>
<tr>
<th>Virusbox:</th>
<td colspan="2">
<label><input type="radio" name="virusbox" value="none" $virusboxNospamSelected />No virusbox</label><br />
<label><input type="radio" name="virusbox" value="inbox" $virusboxInboxSelected />Virus in inbox</label><br />
<label><input type="radio" name="virusbox" value="folder" $virusboxfolderSelected />Place virus mails in the specified folder:</label><br />
<input type="text" name="virusbox-folder" id="virusbox-folder" $virusboxFolderValue /></td>
</tr>
<tr id="virusbox-quota">
<th>Spambox quota:</th>
<td><input type="text" name="virusquota" $readonly $virusQuotaValue /></td><td>MB</td>
</tr>
<tr>
<tr class="submit"><td colspan="3"><input type="submit" value="Edit mailbox" /></td></tr>
</table>
<script type="text/javascript">
$(document).ready(function(){
	$("input:radio[name=spambox]").change(updateSpambox);
	$("input:radio[name=virusbox]").change(updateVirusbox);
	updateSpambox();
	updateVirusbox();
});

function updateSpambox()
{
	if($("input:radio[name=spambox]:checked").val() == "none") {
		$("#spambox-folder").hide();
		$("#spambox-quota").hide();
	} else if($("input:radio[name=spambox]:checked").val() == "inbox") {
		$("#spambox-folder").hide();
		$("#spambox-quota").hide();
	} else {
		$("#spambox-folder").show();
		$("#spambox-quota").show();
	}
}
function updateVirusbox()
{
	if($("input:radio[name=virusbox]:checked").val() == "none") {
		$("#virusbox-folder").hide();
		$("#virusbox-quota").hide();
	} else if($("input:radio[name=virusbox]:checked").val() == "inbox") {
		$("#virusbox-folder").hide();
		$("#virusbox-quota").hide();
	} else {
		$("#virusbox-folder").show();
		$("#virusbox-quota").show();
	}
}

</script>
</form>
</div>

HTML;
}

function validDomain($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	if(preg_match('/^[-a-zA-Z0-9_.]*$/', $name) != 1) {
		return false;
	}
	return true;
}

function validLocalPart($localpart)
{
	if(strlen($localpart) == 0) {
		return false;
	}
	if(strlen($localpart) > 255) {
		return false;
	}
	if(substr($localpart, 0, 1) == ".") {
		return false;
	}
	
	if(trim($localpart, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ12345678900-_.^*={}") != "") {
		return false;
	}
	return true;
}

function validDirectory($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	if(preg_match('/^[-a-zA-Z0-9_.]*$/', $name) != 1) {
		return false;
	}
	return true;
}

function validEmail($email)
{
	if(strlen($email) == 0) {
		return false;
	}
	if(strlen($email) > 255) {
		return false;
	}
	$atpos = strpos($email, "@");
	if($atpos === false || $atpos == 0 || $atpos == strlen($email) - 1) {
		return false;
	}
	return true;
}

?>