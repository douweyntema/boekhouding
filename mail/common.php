<?php

require_once(dirname(__FILE__) . "/../common.php");

function menuMail()
{
	if($GLOBALS["menuComponent"] == "mail") {
		return <<<HTML
<li>
<a href="{$GLOBALS["rootHtml"]}mail/">Email</a>
<ul>
<li><a href="{$GLOBALS["rootHtml"]}mail/functie1.php">Functie 1</a></li>
<li><a href="{$GLOBALS["rootHtml"]}mail/functie2.php">Functie 2</a></li>
</ul>
</li>

HTML;
	} else {
		return "<li><a href=\"{$GLOBALS["rootHtml"]}mail/\">Email</a></li>\n";
	}
}

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

function mailDomainsList()
{
	$output = "";
	$output .= <<<HTML
<div class="list">
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
<div class="list">
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
<div class="list">
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

function mailboxForm($addressID)
{
}

function mailAliasForm($aliasID)
{
	$output = "";
	$output .= <<<HTML
<div class="list">
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

?>