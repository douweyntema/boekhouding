<?php

require_once("common.php");

function main()
{
	$addressID = get("id");
	doMailAddress($addressID);
	
	$check = function($condition, $error) use($addressID) {
		if(!$condition) die(page(mailboxHeader($addressID) . editMailboxPasswordForm($addressID, $error, $_POST)));
	};
	
	$password = checkPassword($check, "password");
	$check(post("confirm") !== null, null);
	
	stdSet("mailAddress", array("addressID"=>$addressID), array("password"=>base64_encode($password)));
	
	updateMail(customerID());
	
	redirect("mail/mailbox.php?id=$addressID");
}

main();

?>