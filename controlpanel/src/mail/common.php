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

function doMailList($listID)
{
	$domainID = $GLOBALS["database"]->stdGetTry("mailList", array("listID"=>$listID), "domainID", false);
	doMailDomain($domainID);
}

function doMailAddress($addressID)
{
	$domainID = $GLOBALS["database"]->stdGetTry("mailAddress", array("addressID"=>$addressID), "domainID", false);
	doMailDomain($domainID);
}

function crumb($name, $filename)
{
	return array("name"=>$name, "url"=>"{$GLOBALS["root"]}mail/$filename");
}

function crumbs($name, $filename)
{
	return array(crumb($name, $filename));
}

function mailBreadcrumbs()
{
	return crumbs("Email", "");
}

function domainBreadcrumbs($domainID)
{
	return array_merge(mailBreadcrumbs(), crumbs("Domain " . $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name"), "domain.php?id=$domainID"));
}

function domainHeader($domainID, $title = null, $url = null)
{
	if($title === null) {
		return makeHeader("Domain " . $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name"), domainBreadcrumbs($domainID));
	} else {
		return makeHeader($title, domainBreadcrumbs($domainID), crumbs($title, $url));
	}
}

function aliasHeader($aliasID)
{
	$alias = $GLOBALS["database"]->stdGet("mailAlias", array("aliasID"=>$aliasID), array("domainID", "localpart"));
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$alias["domainID"]), "name");
	$address = $alias["localpart"] . "@" . $domain;
	return makeHeader("Alias $address", domainBreadcrumbs($alias["domainID"]), crumbs("Alias $address", "alias.php?id=$aliasID"));
}

function listHeader($listID)
{
	$list = $GLOBALS["database"]->stdGet("mailList", array("listID"=>$listID), array("domainID", "localpart"));
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$list["domainID"]), "name");
	$address = $list["localpart"] . "@" . $domain;
	return makeHeader("Mailinglist $address", domainBreadcrumbs($list["domainID"]), crumbs("Mailinglist $address", "list.php?id=$listID"));
}

function mailboxHeader($addressID)
{
	$mailbox = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$addressID), array("domainID", "localpart"));
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$mailbox["domainID"]), "name");
	$address = $mailbox["localpart"] . "@" . $domain;
	return makeHeader("Mailbox $address", domainBreadcrumbs($mailbox["domainID"]), crumbs("Mailbox $address", "mailbox.php?id=$addressID"));
}

function mailDomainsList()
{
	$rows = array();
	foreach($GLOBALS["database"]->stdList("mailDomain", array("customerID"=>customerID()), array("domainID", "name"), array("name"=>"asc")) as $domain) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}mail/domain.php?id={$domain["domainID"]}", "text"=>$domain["name"]),
			$GLOBALS["database"]->stdCount("mailAddress", array("domainID"=>$domain["domainID"])),
			$GLOBALS["database"]->stdCount("mailAlias", array("domainID"=>$domain["domainID"]))
		);
	}
	return listTable(array("Domain", "# mailboxes", "# aliasses"), $rows, "sortable list");
}

function mailboxList($domainID)
{
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	$rows = array();
	foreach($GLOBALS["database"]->stdList("mailAddress", array("domainID"=>$domainID), array("addressID", "localpart", "quota"), array("localpart"=>"asc")) as $mailbox) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}mail/mailbox.php?id={$mailbox["addressID"]}", "text"=>"{$mailbox["localpart"]}@$domain"),
			"{$mailbox["quota"]} MiB"
		);
	}
	return listTable(array(array("text"=>"Mailbox", "class"=>"entityalign"), "Quota"), $rows, "sortable list");
}

function mailAliasList($domainID)
{
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	$rows = array();
	foreach($GLOBALS["database"]->stdList("mailAlias", array("domainID"=>$domainID), array("aliasID", "localpart", "targetAddress"), array("localpart"=>"asc")) as $alias) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}mail/alias.php?id={$alias["aliasID"]}", "text"=>"{$alias["localpart"]}@$domain"),
			"{$alias["targetAddress"]}"
		);
	}
	return listTable(array(array("text"=>"Alias", "class"=>"entityalign"), "Forward to"), $rows, "sortable list");
}

function mailListList($domainID)
{
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	$rows = array();
	foreach($GLOBALS["database"]->stdList("mailList", array("domainID"=>$domainID), array("listID", "localpart"), array("localpart"=>"asc")) as $list) {
		$count = $GLOBALS["database"]->stdCount("mailListMember", array("listID"=>$list["listID"]));
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}mail/list.php?id={$list["listID"]}", "text"=>"{$list["localpart"]}@$domain"),
			"$count members"
		);
	}
	return listTable(array(array("text"=>"Mailinglist", "class"=>"entityalign"), "Members"), $rows, "sortable list");
}

function mailListMemberList($listID)
{
	$id = getHtmlID();
	$rows = array();
	foreach($GLOBALS["database"]->stdList("mailListMember", array("listID"=>$listID), array("memberID", "targetAddress"), array("targetAddress"=>"asc")) as $member) {
		$targetHtml = htmlentities($member["targetAddress"]);
		$rows[] = array(
			array("html"=>"<label>$targetHtml <input type=\"checkbox\" name=\"member-{$member["memberID"]}\" class=\"rightalign selectall-$id\" value=\"1\"></label>")
		);
	}
	/// TODO: fixen
	$footer = array(
		array("html"=>"<table class=\"inline\" style=\"width: 100%\"><tr><td style=\"width: 100%; text-align: center; margin: 10px -6px -6px -6px; padding: 10px 27px 6px 27px; background-color: #ffffff;\"><input type=\"submit\" value=\"Delete selected members\" style=\"width: 100%\" /></td><td style=\"white-space: nowrap; background-color: #ffffff; vertical-align: middle;\"><a href=\"#\" class=\"rightalign selectall\" id=\"$id\" style=\"margin-right: 2px;\">Select all</a></td></tr></table>")
	);
	return listTable(array("Member"), $rows, array("divclass"=>"sortable list", "formtarget"=>"removemember.php?id=$listID", "footer"=>$footer, "footerclass"=>"selectallsubmit"));
}

function mailboxSummary($addressID)
{
	$mailbox = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$addressID), array("domainID", "localpart", "spambox", "virusbox", "quota", "spamQuota", "virusQuota", "canUseSmtp", "canUseImap"));
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$mailbox["domainID"]), "name");
	
	if($mailbox["spambox"] === null) {
		$spambox = "No spambox";
	} else if($mailbox["spambox"] == "") {
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
	} else if($mailbox["virusbox"] == "") {
		$virusbox = "inbox";
	} else {
		if($mailbox["virusQuota"] === null) {
			$virusbox = $mailbox["virusbox"] . " (no quota)";
		} else {
			$virusbox = $mailbox["virusbox"] . " (quota " . $mailbox["virusQuota"] . " MiB)";
		}
	}
	
	return summaryTable("Mailbox {$mailbox["localpart"]}@$domain", array(
		"Quota"=>"{$mailbox["quota"]} MiB",
		"Spambox"=>$spambox,
		"Virusbox"=>$virusbox,
		"SMTP"=>($mailbox["canUseSmtp"] == 0 ? "Disabled" : "Enabled"),
		"IMAP"=>($mailbox["canUseImap"] == 0 ? "Disabled" : "Enabled")
		));
}

function addMailDomainForm($error = "", $values = null)
{
	return operationForm("adddomain.php", $error, "Add domain", "Save",
		array(
			array("title"=>"Domain name", "type"=>"text", "name"=>"domainName")
		),
		$values);
}

function removeMailDomainForm($domainID, $error = "", $values = null)
{
	return operationForm("removedomain.php?id=$domainID", $error, "Remove domain", "Remove Domain", array(), $values);
}

function addMailAliasForm($domainID, $error = "", $values = null)
{
	$domainName = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	return operationForm("addalias.php?id=$domainID", $error, "Add alias", "Save",
		array(
			array("title"=>"Alias", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"localpart", "fill"=>true),
				array("type"=>"html", "cellclass"=>"nowrap", "html"=>"@$domainName")
			)),
			array("title"=>"Target address", "type"=>"text", "name"=>"targetAddress")
		),
		$values);
}

function editMailAliasForm($aliasID, $error = "", $values = null)
{
	$alias = $GLOBALS["database"]->stdGet("mailAlias", array("aliasID"=>$aliasID), array("domainID", "localpart", "targetAddress"));
	$domainName = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$alias["domainID"]), "name");
	
	if($values === null) {
		$values = array("targetAddress"=>$alias["targetAddress"]);
	}
	
	return operationForm("editalias.php?id=$aliasID", $error, "Change alias", "Save",
		array(
			array("title"=>"Alias", "type"=>"html", "cellclass"=>"nowrap", "html"=>"{$alias["localpart"]}@$domainName"),
			array("title"=>"Target address", "type"=>"text", "name"=>"targetAddress")
		),
		$values);
}

function removeMailAliasForm($aliasID, $error = "", $values = null)
{
	return operationForm("removealias.php?id=$aliasID", $error, "Remove alias", "Remove Alias", array(), $values, array("confirmdelete"=>"Are you sure you want to remove this alias?"));
}

function addMailListForm($domainID, $error = "", $values = null)
{
	$domainName = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	return operationForm("addlist.php?id=$domainID", $error, "Add mailinglist", "Save",
		array(
			array("title"=>"Mailinglist", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"localpart", "fill"=>true),
				array("type"=>"html", "cellclass"=>"nowrap", "html"=>"@$domainName")
			)),
			array("title"=>"Members", "type"=>"textarea", "name"=>"members")
		),
		$values);
}

function editMailListForm($listID, $error = "", $values = null)
{
	$list = $GLOBALS["database"]->stdGet("mailList", array("listID"=>$listID), array("domainID", "localpart"));
	$domainName = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$list["domainID"]), "name");
	
	if($values === null) {
		$values = array("localpart"=>$list["localpart"]);
	}
	
	return operationForm("editlist.php?id=$listID", $error, "Change mailinglist address", "Save",
		array(
			array("title"=>"Mailinglist", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"localpart", "fill"=>true),
				array("type"=>"html", "cellclass"=>"nowrap", "html"=>"@$domainName")
			))
		),
		$values);
}

function removeMailListForm($listID, $error = "", $values = null)
{
	return operationForm("removelist.php?id=$listID", $error, "Remove mailing list", "Remove Mailing List", array(), $values, array("confirmdelete"=>"Are you sure you want to remove this mailing list?"));
}

function addMailListMemberForm($listID, $error = "", $values = null)
{
	$domainID = $GLOBALS["database"]->stdGet("mailList", array("listID"=>$listID), "domainID");
	$domainName = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	return operationForm("addlistmember.php?id=$listID", $error, "Add members", "Save",
		array(
			array("title"=>"Members", "type"=>"textarea", "name"=>"members")
		),
		$values);
}

function editMailListMemberForm($listID, $error = "", $values = null)
{
	$domainID = $GLOBALS["database"]->stdGet("mailList", array("listID"=>$listID), "domainID");
	$domainName = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	if($error == "STUB") {
		return operationForm("editlistmember.php?id=$listID", "", "Edit members", "Edit members", array(), array());
	}
	
	if($values === null || !isset($values["members"])) {
		$members = $GLOBALS["database"]->stdList("mailListMember", array("listID"=>$listID), "targetAddress");
		$values = array("members"=>(implode("\n", $members) . "\n"));
	}
	
	return operationForm("editlistmember.php?id=$listID", $error, "Edit members", "Save",
		array(
			array("title"=>"Members", "type"=>"textarea", "name"=>"members"),
		),
		$values);
}

function removeMailListMemberForm($listID, $error = "", $values = null)
{
	if($error === null) {
		$fields = array();
		$memberList = "<ul>\n";
		foreach($GLOBALS["database"]->stdList("mailListMember", array("listID"=>$listID), array("memberID", "targetAddress")) as $member) {
			if(isset($values["member-{$member["memberID"]}"])) {
				$memberList .= "<li>" . htmlentities($member["targetAddress"]) . "</li>\n";
				$fields[] = array("type"=>"hidden", "name"=>"member-{$member["memberID"]}");
			}
		}
		$memberList .= "</ul>\n";
		$messages = array("confirmdelete"=>"Are you sure you want to remove these members?", "custom"=>$memberList);
	} else {
		$messages = null;
		$fields = array();
	}
	return operationForm("removemember.php?id=$listID", $error, "Remove mailinglist members", "Remove Members", $fields, $values, $messages);
}

function addMailboxForm($domainID, $error = "", $values = null)
{
	$domainName = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	if($values === null) {
		$values = array(
			"spambox"=>"inbox",
			"spamQuota"=>100,
			"spambox-folder"=>"spam",
			"virusbox"=>"inbox",
			"virusQuota"=>100,
			"virusbox-folder"=>"virus"
		);
	}
	
	return operationForm("addmailbox.php?id=$domainID", $error, "Add mailbox", "Create mailbox",
		array(
			array("title"=>"Mailbox", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"localpart", "fill"=>true),
				array("type"=>"html", "cellclass"=>"nowrap", "html"=>"@$domainName")
			)),
			array("title"=>"Password", "type"=>"password", "name"=>"password", "confirmtitle"=>"Confirm password"),
			array("title"=>"Quota", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"quota", "fill"=>true),
				array("type"=>"html", "cellclass"=>"nowrap", "html"=>"MiB")
			)),
			array("title"=>"Spambox", "type"=>"subformchooser", "name"=>"spambox", "subforms"=>array(
				array("value"=>"none", "label"=>"No spambox", "subform"=>array()),
				array("value"=>"inbox", "label"=>"Spam in inbox", "subform"=>array()),
				array("value"=>"folder", "label"=>"Place spam in the specified folder", "subform"=>array(
					array("title"=>"Spam folder", "type"=>"text", "name"=>"spambox-folder"),
					array("title"=>"Spambox quota", "type"=>"colspan", "columns"=>array(
						array("type"=>"text", "name"=>"spamquota", "fill"=>true),
						array("type"=>"html", "cellclass"=>"nowrap", "html"=>"MiB")
					)),
				))
			)),
			array("title"=>"Virusbox", "type"=>"subformchooser", "name"=>"virusbox", "subforms"=>array(
				array("value"=>"none", "label"=>"No virusbox", "subform"=>array()),
				array("value"=>"inbox", "label"=>"Virus in inbox", "subform"=>array()),
				array("value"=>"folder", "label"=>"Place virus mails in the specified folder", "subform"=>array(
					array("title"=>"Virus folder", "type"=>"text", "name"=>"virusbox-folder"),
					array("title"=>"Virusbox quota", "type"=>"colspan", "columns"=>array(
						array("type"=>"text", "name"=>"virusquota", "fill"=>true),
						array("type"=>"html", "cellclass"=>"nowrap", "html"=>"MiB")
					)),
				))
			))
		),
		$values);
}

function editMailboxForm($addressID, $error = "", $values = null)
{
	if($values === null) {
		$mailbox = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$addressID), array("domainID", "localpart", "quota", "spambox", "spamQuota", "virusbox", "virusQuota"));
		$values = array(
			"quota"=>($mailbox["quota"] === null ? "" : $mailbox["quota"]),
			"spambox"=>($mailbox["spambox"] === null ? "none" : ($mailbox["spambox"] === "" ? "inbox" : "folder")),
			"spambox-folder"=>(($mailbox["spambox"] === null || $mailbox["spambox"] === "") ? "" : $mailbox["spambox"]),
			"spamquota"=>$mailbox["spamQuota"],
			"virusbox"=>($mailbox["virusbox"] === null ? "none" : ($mailbox["virusbox"] === "" ? "inbox" : "folder")),
			"virusbox-folder"=>(($mailbox["virusbox"] === null || $mailbox["virusbox"] === "") ? "" : $mailbox["virusbox"]),
			"virusquota"=>$mailbox["virusQuota"]
			);
	}
	
	
	return operationForm("editmailbox.php?id=$addressID", $error, "Edit mailbox", "Save",
		array(
			array("title"=>"Quota", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"quota", "fill"=>true),
				array("type"=>"html", "cellclass"=>"nowrap", "html"=>"MiB")
			)),
			array("title"=>"Spambox", "type"=>"subformchooser", "name"=>"spambox", "subforms"=>array(
				array("value"=>"none", "label"=>"No spambox", "subform"=>array()),
				array("value"=>"inbox", "label"=>"Spam in inbox", "subform"=>array()),
				array("value"=>"folder", "label"=>"Place spam in the specified folder", "subform"=>array(
					array("title"=>"Spam folder", "type"=>"text", "name"=>"spambox-folder"),
					array("title"=>"Spambox quota", "type"=>"colspan", "columns"=>array(
						array("type"=>"text", "name"=>"spamquota", "fill"=>true),
						array("type"=>"html", "cellclass"=>"nowrap", "html"=>"MiB")
					)),
				))
			)),
			array("title"=>"Virusbox", "type"=>"subformchooser", "name"=>"virusbox", "subforms"=>array(
				array("value"=>"none", "label"=>"No virusbox", "subform"=>array()),
				array("value"=>"inbox", "label"=>"Virus in inbox", "subform"=>array()),
				array("value"=>"folder", "label"=>"Place virus mails in the specified folder", "subform"=>array(
					array("title"=>"Virus folder", "type"=>"text", "name"=>"virusbox-folder"),
					array("title"=>"Virusbox quota", "type"=>"colspan", "columns"=>array(
						array("type"=>"text", "name"=>"virusquota", "fill"=>true),
						array("type"=>"html", "cellclass"=>"nowrap", "html"=>"MiB")
					)),
				))
			))
		),
		$values);
}

function editMailboxPasswordForm($addressID, $error = "", $values = null)
{
	return operationForm("editmailboxpassword.php?id=$addressID", $error, "Change password", "Change Password",
		array(
			array("title"=>"Password", "type"=>"password", "name"=>"password", "confirmtitle"=>"Confirm password")
		),
		$values);
}

function removeMailboxForm($addressID, $error = "", $values = null)
{
	return operationForm("removemailbox.php?id=$addressID", $error, "Remove mailbox", "Yes, delete the mail", array(), $values, array("confirmdelete"=>"Are you sure you want to remove this mailbox? This will permanently delete all mail stored in it."));
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