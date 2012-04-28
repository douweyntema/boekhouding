<?php

$mysqlTitle = "Databases";
$mysqlDescription = "Database access";
$mysqlTarget = "customer";

function mysqlDatabaseExists($database, $db = null)
{
	if($db === null) {
		$db = mysqlDatabaseConnection();
	}
	return $db->query("SHOW DATABASES LIKE '$database'")->numrows() == 1;
}

function mysqlCreateDatabase($database, $db = null)
{
	if($db === null) {
		$db = mysqlDatabaseConnection();
	}
	foreach($db->query("SELECT DISTINCT GRANTEE FROM SCHEMA_PRIVILEGES WHERE TABLE_SCHEMA='$database';")->fetchList("GRANTEE") as $revokeUser) {
		$db->setQuery("REVOKE ALL ON `$database`.* FROM $revokeUser");
	}
	$db->setQuery("CREATE DATABASE `$database`");
	foreach($GLOBALS["database"]->stdList("adminUser", array("customerID"=>customerID()), array("userID", "username")) as $user) {
		if(canUserAccessComponent($user["userID"], "mysql")) {
			$db->setQuery("GRANT ALL ON `$database`.* TO '{$user["username"]}'@'%'");
		} else {
			$db->setQuery("GRANT ALL ON `$database`.* TO '{$user["username"]}'@'0.0.0.0'");
		}
	}
}

function mysqlRevokeRights($database, $db = null)
{
	if($db === null) {
		$db = mysqlDatabaseConnection();
	}
	foreach($GLOBALS["database"]->stdList("adminUser", array("customerID"=>customerID()), array("userID", "username")) as $user) {
		$host = (canUserAccessComponent($user["userID"], "mysql")) ? "%" : "0.0.0.0";
		$db->setQuery("REVOKE ALL ON `$database`.* FROM '{$user["username"]}'@'$host'");
	}
}

function mysqlListDatabases($db = null)
{
	if($db === null) {
		$db = mysqlDatabaseConnection();
	}
	$mysqlRightID = $GLOBALS["database"]->stdGetTry("adminCustomerRight", array("customerID"=>customerID(), "right"=>"mysql"), "customerRightID", null);
	if($mysqlRightID === null) {
		return array();
	}
	$result = $GLOBALS["database"]->query("SELECT username FROM adminUser LEFT JOIN adminUserRight USING(userID) WHERE (customerRightID IS NULL OR customerRightID = $mysqlRightID) AND customerID=" . customerID() . " LIMIT 1")->fetchArray();
	$user = $result["username"];
	
	$databases = array();
	foreach($db->query("SELECT DISTINCT TABLE_SCHEMA AS name FROM SCHEMA_PRIVILEGES WHERE GRANTEE='\'$user\'@\'%\'';")->fetchList("name") as $database) {
		if(!mysqlDatabaseExists($database, $db)) {
			mysqlRevokeRights($database, $db);
		} else {
			$databases[] = $database;
		}
	}
	return $databases;
}

function mysqlCreateUser($user, $password, $enabled = true, $db = null)
{
	if($db === null) {
		$db = mysqlDatabaseConnection();
	}
	$host = $enabled ? "'%'" : "'0.0.0.0'";
	$db->setQuery("CREATE USER '" . $db->addSlashes($user) . "'@$host IDENTIFIED BY '" . $db->addSlashes($password) . "'");
	$result = $GLOBALS["database"]->query("SELECT username FROM adminUser LEFT JOIN adminUserRight USING(userID) WHERE  customerID=" . customerID() . " AND username <> '" . $db->addSlashes($user) . "' LIMIT 1")->fetchArray();
	if($result === null) {
		return;
	}
	$oldUser = $result["username"];
	if($db->stdExists("mysql`.`user", array("User"=>$oldUser, "Host"=>"%"))) {
		$oldHost = "%";
	} else if($db->stdExists("mysql`.`user", array("User"=>$oldUser, "Host"=>"0.0.0.0"))) {
		$oldHost = "0.0.0.0";
	}

	foreach($db->query("SELECT DISTINCT TABLE_SCHEMA AS name FROM SCHEMA_PRIVILEGES WHERE GRANTEE='\'$oldUser\'@\'$oldHost\'';")->fetchList("name") as $database) {
		$db->setQuery("GRANT ALL ON `$database`.* TO '$user'@$host");
	}
}

function mysqlSetPassword($user, $password, $db = null)
{
	if($db === null) {
		$db = mysqlDatabaseConnection();
	}
	if($db->stdExists("mysql`.`user", array("User"=>$user, "Host"=>"%"))) {
		$db->setQuery("SET PASSWORD FOR '" . $db->addSlashes($user) . "'@'%' = PASSWORD('" . $db->addSlashes($password) . "')");
	} else if($db->stdExists("mysql`.`user", array("User"=>$user, "Host"=>"0.0.0.0"))) {
		$db->setQuery("SET PASSWORD FOR '" . $db->addSlashes($user) . "'@'0.0.0.0' = PASSWORD('" . $db->addSlashes($password) . "')");
	}
}

function mysqlEnableAccount($user, $db = null)
{
	if($db === null) {
		$db = mysqlDatabaseConnection();
	}
	if(!$db->stdExists("mysql`.`user", array("User"=>$user, "Host"=>"0.0.0.0"))) {
		return;
	}
	$db->setQuery("RENAME USER '" . $db->addSlashes($user) . "'@'0.0.0.0' TO '" . $db->addSlashes($user) . "'@'%'");
}

function mysqlDisableAccount($user, $db = null)
{
	if($db === null) {
		$db = mysqlDatabaseConnection();
	}
	if(!$db->stdExists("mysql`.`user", array("User"=>$user, "Host"=>"%"))) {
		return;
	}
	$db->setQuery("RENAME USER '" . $db->addSlashes($user) . "'@'%' TO '" . $db->addSlashes($user) . "'@'0.0.0.0'");
}

function mysqlRemoveAccount($user, $db = null)
{
	if($db === null) {
		$db = mysqlDatabaseConnection();
	}
	if($db->stdExists("mysql`.`user", array("User"=>$user, "Host"=>"%"))) {
		$db->setQuery("DROP USER '" . $db->addSlashes($user) . "'@'%'");
	} else if($db->stdExists("mysql`.`user", array("User"=>$user, "Host"=>"0.0.0.0"))) {
		$db->setQuery("DROP USER '" . $db->addSlashes($user) . "'@'0.0.0.0'");
	}
}

function mysqlDatabaseConnection()
{
	global $mysql_management_username, $mysql_management_password;
	
	$hostname = array_shift($GLOBALS["database"]->query("SELECT hostname FROM infrastructureHost LEFT JOIN infrastructureWebServer USING(hostID) LEFT JOIN adminCustomer USING(fileSystemID) WHERE customerID='" . $GLOBALS["database"]->addSlashes(customerID()) . "' LIMIT 1")->fetchArray());
	
	$db = new MysqlConnection();
	$db->open($hostname, $mysql_management_username, $mysql_management_password, "information_schema");
	
	return $db;
}

?>