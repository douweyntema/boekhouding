<?php

require_once("common.php");

function main()
{
	$addressID = get("id");
	doMailAddress($addressID);
	
	$check = function($condition, $error) use($addressID) {
		if(!$condition) die(page(mailboxHeader($addressID) . editMailboxPasswordForm($addressID, $error, $_POST)));
	};
	
	if(post("confirm") === null) {
		$check(post("password-1") == post("password-2"), "The entered passwords do not match");
		$check(post("password-1") != "", "Passwords must be at least one character long");
		$check(false, null);
	}
	$password = decryptPassword(post("encrypted-password"));
	$check($password !== null, "Internal error: invalid encrypted password. Please enter password again.");
	
	$GLOBALS["database"]->stdSet("mailAddress", array("addressID"=>$addressID), array("password"=>base64_encode($password)));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/mailbox.php?id=$addressID");
}

main();

?>