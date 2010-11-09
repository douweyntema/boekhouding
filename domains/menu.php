<?php

require_once(dirname(__FILE__) . "/common.php");

if(canAccessComponent("domains", true)) {
	addMenu("menuDomains");
} else if(canAccessComponent("domains")) {
	addAdminMenu("menuDomains");
}

?>