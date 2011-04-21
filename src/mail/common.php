<?php

require_once(dirname(__FILE__) . "/../common.php");

function doMail()
{
}

function doMailDomain($domainID)
{
}

function doMailAlias($aliasID)
{
}

function doMailbox($mailboxID)
{
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
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Mailbox</th><th>Quota</th></tr>
</thead>
<tbody>
HTML;
	foreach($GLOBALS["database"]->stdList("mailAddress", array("domainID"=>$domainID), array("addressID", "localpart", "quota"), array("localpart"=>"asc")) as $mailbox) {
		$quota = round($mailbox["quota"] / 1024 / 1024, 2);
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}mail/mailbox.php?id={$mailbox["addressID"]}\">{$mailbox["localpart"]}</a></td><td>$quota MB</td></tr>\n";
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
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Alias</th><th>Forward to</th></tr>
</thead>
<tbody>
HTML;
	foreach($GLOBALS["database"]->stdList("mailAlias", array("domainID"=>$domainID), array("aliasID", "localpart", "targetAddress"), array("localpart"=>"asc")) as $alias) {
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}mail/alias.php?id={$alias["aliasID"]}\">{$alias["localpart"]}</a></td><td>{$alias["targetAddress"]}</td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function editMailAliasForm($aliasID, $error, $alias, $targetAddress)
{
	$aliasValue = inputValue($alias);
	$targetAddressValue = inputValue($targetAddress);
	$domainID = $GLOBALS["database"]->stdGet("mailAlias", array("aliasID"=>$aliasID), "domainID");
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
<td class="stretch"><input type="text" name="localpart" $readonly $aliasValue /></td>
<td>@{$domainName}</td>
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
<h2>Add an alias</h2>
$messageHtml
<form action="addalias.php?id=$domainID" method="post">
$confirmHtml
<table>
<tr>
<th>Alias:</th>
<td class="stretch"><input type="text" name="localpart" $readonly $aliasValue /></td>
<td>@{$domainName}</td>
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

function removeMailAliasForm($aliasID, $error)
{
	if($error === null) {
		$messageHtml = "<p class=\"confirm\">Confirm your input</p>\n<p class=\"confirmdelete\">Are you sure you want to remove this alias?</p>\n";
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
<h2>Remove alias</h2>
$messageHtml
<form action="removealias.php?id=$aliasID" method="post">
$confirmHtml
<table><tr class="submit"><td>
<input type="submit" value="Remove Alias" />
</td></tr></table>
</form>
</div>

HTML;
}

?>