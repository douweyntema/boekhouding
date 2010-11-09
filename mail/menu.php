<?php

require_once(dirname(__FILE__) . "/common.php");

if(canAccessComponent("mail", true)) {
	addMenu("menuMail");
} else if(canAccessComponent("mail")) {
	addAdminMenu("menuMail");
}

?>