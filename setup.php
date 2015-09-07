<?php

require_once("/usr/lib/phpdatabase/database.php");
require_once("src/util.php");
require_once("src/accounting/api.php");
require_once("src/accounts/api.php");

if(file_exists("src/config.php")) {
	echo "Configuratie bestaat al in src/config.php\n";
	exit(1);
}

$config["htmlTitle"] = question("Bedrijfsnaam?");
$config["adminMail"] = question("Admin email adres?");
$config["adminMailName"] = question("Admin naam?");

$config["controlpanelDisabled"] = false;
$config["controlpanelDisabledNotice"] = "<p>De boekhouding is tijdelijk buiten gebruik voor onderhoud.</p>";

$config["controlpanelEnableAssetDepreciation"] = true;
$config["controlpanelEnableCustomerEmail"] = true;

$config["controlpanelUrl"] = question("URL van de boekhouding?");
$succes = false;
while(!$succes) {
	$config["database_hostname"] = question("Database server?", "localhost");
	$config["database_username"] = question("Database gebruikesnaam?", "root");
	$config["database_password"] = question("Database wachtwoord?");
	$config["database_name"] = question("Database naam?");
	
	$succes = true;
	$GLOBALS["database"] = new MysqlConnection();
	try {
		@$GLOBALS["database"]->open($config["database_hostname"], $config["database_username"], $config["database_password"], $config["database_name"]);
	} catch(DatabaseException $e) {
		echo "Database gegevens ongeldig, probeer opnieuw.\n";
		$succes = false;
	}
	
	$tables = query('SHOW TABLES')->fetchList();
	if(count($tables) > 0) {
		$verder = "";
		while($verder != "ja" && $verder != "nee") {
			$verder = question("Database bevat al gegevens, verder gaan (ja / nee)?", "nee");
		}
		$succes = ($verder == "ja");
	}
}

$sql = file_get_contents("system.sql");
$mysqli = new mysqli($config["database_hostname"], $config["database_username"], $config["database_password"], $config["database_name"]);
if ($mysqli->connect_errno) {
	printf("Connect failed: %s\n", $mysqli->connect_error);
	exit();
}
$status = $mysqli->multi_query($sql);
$mysqli->close();

sleep(1);

$GLOBALS["database"]->close();
$GLOBALS["database"]->open($config["database_hostname"], $config["database_username"], $config["database_password"], $config["database_name"]);

$config["crypto_key"] = randomString(20);

$euro = stdNew("accountingCurrency", array("name"=>"EUR", "symbol"=>'&euro;', "order"=>1));
$dollar = stdNew("accountingCurrency", array("name"=>"USD", "symbol"=>'$', "order"=>2));
$km = stdNew("accountingCurrency", array("name"=>"km", "symbol"=>'km', "order"=>99));

$config["taxRate"] = 0.21;
$defaultCurrency = "";
while($defaultCurrency != "EURO" && $defaultCurrency != "USD") {
	$defaultCurrency = question("Standaard valuta (EURO / USD)?", "EURO");
}
if($defaultCurrency == "EURO") {
	$config["defaultCurrencyID"] = $euro;
} else {
	$config["defaultCurrencyID"] = $dollar;
}

$config["assetsDirectoryAccountID"] = accountingAddAccount(null, $config["defaultCurrencyID"], "Activa", "", 1);
$config["liabilitiesDirectoryAccountID"] = accountingAddAccount(null, $config["defaultCurrencyID"], "Passiva", "", 1);
$config["revenueDirectoryAccountID"] = accountingAddAccount(null, $config["defaultCurrencyID"], "Inkomsten", "", 1);
$config["expensesDirectoryAccountID"] = accountingAddAccount(null, $config["defaultCurrencyID"], "Onkosten", "", 1);
$config["travelExpencesAccountID"] = accountingAddAccount(null, $km, "Kilometer registratie", "", 1);

$config["customersDirectoryAccountID"] = accountingAddAccount($config["assetsDirectoryAccountID"], $config["defaultCurrencyID"], "Debiteuren", "", 1);
$config["suppliersDirectoryAccountID"] = accountingAddAccount($config["liabilitiesDirectoryAccountID"], $config["defaultCurrencyID"], "Crediteuren", "", 1);

$config["bankDirectoryAccountID"] = accountingAddAccount($config["assetsDirectoryAccountID"], $config["defaultCurrencyID"], "Bankrekeningen", "", 1);
$config["bankDefaultAccountID"] = accountingAddAccount($config["bankDirectoryAccountID"], $config["defaultCurrencyID"], "Bedrijfsrekening", "", 0);

$config["fixedAssetValueDirectoryAccountID"] = accountingAddAccount($config["assetsDirectoryAccountID"], $config["defaultCurrencyID"], "Vaste activa", "", 1);
$config["fixedAssetDepreciationDirectoryAccountID"] = accountingAddAccount($config["expensesDirectoryAccountID"], $config["defaultCurrencyID"], "Afschijvingen", "", 1);
$config["fixedAssetExpenseDirectoryAccountID"] = accountingAddAccount($config["expensesDirectoryAccountID"], $config["defaultCurrencyID"], "Onderhoud", "", 1);

$config["taxPayableAccountID"] = accountingAddAccount($config["liabilitiesDirectoryAccountID"], $config["defaultCurrencyID"], "Af te dragen BTW", "", 0);
$config["taxReceivableAccountID"] = accountingAddAccount($config["assetsDirectoryAccountID"], $config["defaultCurrencyID"], "Te ontvangen BTW", "", 0);


$config["componentsEnabled"] = array();
$config["componentsEnabled"][] = "customers";
$config["componentsEnabled"][] = "accounting";
$config["componentsEnabled"][] = "accounts";
$config["componentsEnabled"][] = "billing";

$config["invoiceLatexDocumentClass"] = question("Latex documentclass voor facturen?");

$config["invoiceAccountNumber"] = question("Rekeningnummer voor facturen?");
$config["invoiceAccountName"] = question("Naam rekeninghouder voor facturen?");
$config["invoiceSenderEmail"] = question("Afzend email adres voor facturen?");
$config["invoiceSenderName"] = question("Afzend naam voor facturen?");
$config["invoiceBCCEmail"] = question("BCC adres voor facturen?");
$config["invoiceEmailSignature"] = question("Handtekening onder factuur emails?");

$config["brandingColor"] = question("Bedrijfskleur?", randomKleur());

$username = question("Accountsnaam beheedersaccount?");
$password1 = question("Wachtwoord?");
$password2 = question("Bevestig wachtwoord?");
while($password1 != $password2) {
	echo "Wachtwoorden kwamen niet overeen, probeer opnieuw\n";
	$password1 = question("Wachtwoord?");
	$password2 = question("Bevestig wachtwoord?");
}
accountsAddAccount($username, $password1);

$configtext = writeConfig($config);

file_put_contents("src/config.php", $configtext);

function writeConfig($config)
{
	$text = "<?php\n\n";
	
	foreach($config as $name=>$value) {
		$text .= '$' . $name . ' = ';
		if(is_array($value)) {
			$text .= "array();\n";
			foreach($value as $subvalue) {
				$text .= '$' . $name . '[] = ';
				$text .= writeValue($subvalue);
				$text .= ";\n";
			}
		} else {
			$text .= writeValue($value);
			$text .= ";\n";
		}
	}
	
	return $text;
}

function writeValue($value)
{
	if(is_numeric($value)) {
		return $value;
	} else if(is_string($value)) {
		return '"' . $value . '"';
	} else if(is_bool($value)) {
		if($value) {
			return "true";
		} else {
			return "false";
		}
	} else {
		return $value;
	}
}

function question($question, $default = null)
{
	$line = "";
	while($line == "") {
		echo $question . ($default !== null ? " [" . $default . "]" : "") . "\n";
		$handle = fopen ("php://stdin","r");
		$line = trim(fgets($handle));
		fclose($handle);
		if($default !== null && $line == "") {
			return $default;
		}
	}
	return $line;
}

function randomString($length)
{
	$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$randstring = "";
	for ($i = 0; $i < $length; $i++) {
		$randstring .= $characters[rand(0, strlen($characters) - 1)];
	}
	return $randstring;
}

function randomKleur()
{
	$characters = "0123456789abcdef";
	$randstring = "#";
	for ($i = 0; $i < 6; $i++) {
		$randstring .= $characters[rand(0, strlen($characters) - 1)];
	}
	return $randstring;
}

?>