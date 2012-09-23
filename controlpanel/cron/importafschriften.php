<?php

$ingUsername = "";
$ingPassword = "";

$dbHostname = "";
$dbUsername = "";
$dbPassword = "";
$dbDatabase = "";

$mailAddress = "afschriften@treva.nl";

require_once("/usr/lib/phpdatabase/database.php");
require_once("/usr/lib/phpmail/mimemail.php");

function error($string)
{
	$mail = new mimemail();
	$mail->addReceiver($GLOBALS["mailAddress"], "Treva Afschriften");
	$mail->setSender($GLOBALS["mailAddress"], "Treva Afschriften - Systeem");
	$mail->setSubject("Error afschriften script");
	$mail->setTextMessage($string);
	$mail->send();
	die($string);
}

function my_http_request($server, $url, $referer, $cookies, $post_parameters = null, &$setcookies = null, &$headers = null)
{
	if(substr($server, 0, 6) == "ssl://" || substr($server, 0, 6) == "tls://") {
		$hostname = substr($server, 6);
		$port = 443;
	} else {
		$hostname = $server;
		$port = 80;
	}
	
	$conn = fsockopen($server, $port, $a, $b, 60);
	if($conn === false) {
		echo("Kan geen verbinding maken\n");
		return null;
	}
	stream_set_timeout($conn, 60000);
	
	$data = socket_http_request($conn, $hostname, $url, $referer, $cookies, $post_parameters, $setcookies, $headers);
	fclose($conn);
	return $data;
}

function socket_http_request($conn, $server, $url, $referer, $cookies, $post_parameters = null, &$setcookies = null, &$headers = null)
{
	if($post_parameters !== null) {
		$request = "POST " . $url . " HTTP/1.1\r\n";
	} else {
		$request = "GET " . $url . " HTTP/1.1\r\n";
	}
	
	$request .= "Accept: */*\r\n";
	$request .= "Accept-Language: nl\r\n";
	$request .= "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:11.0) Gecko/20100101 Firefox/11.0 Iceweasel/11.0\r\n";
	$request .= "Host: " . $server . "\r\n";
	$request .= "Connection: Keep-Alive\r\n";
	
	if($referer) {
		$request .= "Referer: " . $referer . "\r\n";
	}
	
	$r_cookies = "";
	foreach($cookies as $cookie) {
		if(isset($cookie["expires"]) && $cookie["expires"] < time()) {
			continue;
		}
		$r_cookies .= ($r_cookies == "" ? "Cookie: " : "; ") . urlencode($cookie["name"]) . "=" . str_replace("%3A", ":", urlencode($cookie["value"]));
	}
	if($r_cookies != "") {
		$request .= $r_cookies . "\r\n";
	}

	if($post_parameters === null) {
		$request .= "\r\n";
	} else {
		$post_payload = "";
		foreach($post_parameters as $name=>$value) {
			if($post_payload != "") {
				$post_payload .= "&";
			}
			$post_payload .= $name . "=" . urlencode($value);
		}
		
		$request .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$request .= "Content-Length: " . strlen($post_payload) . "\r\n";
		$request .= "\r\n";
		$request .= $post_payload;
	}
	
	fwrite($conn, $request);
	
	$allheaders = "";
	$headers = array();
	$length = -2;
	$i = 0;
	$connectionClose = true;
	while(true) {
		$header = fgets($conn);
		$allheaders .= $header;
		if($header === false) {
			echo "Timeout\n";
			return null;
		}
		if($i++ > 10000) {
			echo "Too many headers\n";
			return null;
		}
		if($header == "\r\n") {
			break;
		}
		$pos = strpos($header, ":");
		if($pos === false) {
			$key = "";
			$value = trim($header);
		} else {
			$key = substr($header, 0, $pos);
			$value = trim(substr($header, $pos + 1));
		}
		$headers[$key] = $value;
		if(strpos($header, "Content-Length:") === 0 && $length != -1) {
			$length = ltrim(rtrim(substr($header, strlen("Content-Length:"))));
		}
		if(strpos($header, "Transfer-Encoding:") === 0) {
			$length = (strtolower(ltrim(rtrim(substr($header, strlen("Transfer-Encoding:"))))) == "chunked") ? -1 : $length;
		}
		if(strpos($header, "Connection:") === 0) {
			if(strtolower(ltrim(rtrim(substr($header, strlen("Connection:"))))) == "keep-alive") {
				$connectionClose = false;
			}
		}
	}
	
	$setcookies = array_merge(parse_cookies($allheaders), $cookies);
	
	if($length == -2 && !$connectionClose) {
		echo "Bad response\n";
		return null;
	} else if($length == -2) {
		$data = "";
		while(!feof($conn)) {
			$data .= fgets($conn);
		}
	} else if($length != -1) {
		$data = fgetbytes($conn, $length);
	} else {
		$data = "";
		while(true) {
			$size = hexdec(rtrim(fgets($conn)));
			if($size == 0) {
				break;
			}
			
			$data .= fgetbytes($conn, $size);
			fgets($conn);
		}
	}
	return $data;
}

function fgetbytes($resource, $bytes)
{
	$data = "";
	while(strlen($data) < $bytes && !feof($resource)) {
		$data .= fread($resource, $bytes - strlen($data));
	}
	return $data;
}

function parse_cookies($headers)
{
	$setcookies = array();
	$allheaders = explode("\r\n", $headers);
	foreach($allheaders as $header) {
		if($header == "") {
			// einde headers
			break;
		}
		if(strpos($header, "Set-Cookie:") !== 0) {
			continue;
		}
		$cookie = array();
		$data = explode(";", substr($header, strlen("Set-Cookie:")));
		foreach($data as $element) {
			$element = rtrim(ltrim($element));
			if($element == "") {
				continue;
			}
			$pos = strpos($element, "=");
			if($pos == false) {
				continue;
			}
			$name = substr($element, 0, $pos);
			$value = substr($element, $pos + 1);
			if(!isset($cookie["name"])) {
				$cookie["name"] = urldecode($name);
				$cookie["value"] = urldecode($value);
			} else if($name == "expires") {
				$cookie["expires"] = strtotime($value);
			}
		}
		if(isset($cookie["expires"]) && $cookie["expires"] == 0) {
			unset($cookie["expires"]);
		}
		$setcookies[$cookie["name"]] = $cookie;
	}
	return $setcookies;
}

function hidden($html, $name)
{
	$nameRegex = preg_quote($name);
	if(!preg_match("/<input type=\"hidden\" name=\"$nameRegex\" value=\"(?<value>[^\"]*)\">/", $html, $match)) {
		return null;
	}
	return html_entity_decode($match["value"]);
}

class Session
{
	var $cookies = array();
	function get($url)
	{
		return my_http_request("ssl://mijnzakelijk.ing.nl", $url, null, $this->cookies, null, $this->cookies);
	}
	function post($url, $post)
	{
		return my_http_request("ssl://mijnzakelijk.ing.nl", $url, null, $this->cookies, $post, $this->cookies);
	}
}

function parse_csv($csv)
{
	if(strpos($csv, '"Tegenrekening"') === false) {
		return null;
	}
	$output = array();
	foreach(explode("\n", trim($csv)) as $line) {
		$entry = array();
		while(true) {
			$pos = strpos($line, '"');
			if($pos === false) {
				break;
			}
			$pos2 = strpos($line, '"', $pos + 1);
			if($pos === false) {
				break;
			}
			
			$value = substr($line, $pos + 1, $pos2 - $pos - 1);
			$line = substr($line, $pos2 + 1);
			$entry[] = $value;
		}
		$output[] = $entry;
	}
	$result = array();
	array_shift($output);
	foreach($output as $entry) {
		if(count($entry) != 9) {
			return null;
		}
		$beschrijving = trim($entry[8]);
		while(strpos($beschrijving, "  ") !== false) {
			$beschrijving = str_replace("  ", " ", $beschrijving);
		}
		$result[] = array(
			"datum"=>mktime(0, 0, 0, substr($entry[0], 4, 2), substr($entry[0], 6, 2), substr($entry[0], 0, 4)),
			"naam"=>$entry[1],
			"rekening"=>$entry[2],
			"tegenrekening"=>$entry[3],
			"code"=>$entry[4],
			"afbij"=>$entry[5],
			"bedrag"=>$entry[6],
			"mutatiesoort"=>$entry[7],
			"beschrijving"=>$beschrijving,
			"centenbij"=>($entry[5] == "Af" ? -1 : 1) * str_replace(",", "", $entry[6])
		);
	}
	return $result;
}

function download_afschrift($username, $password, $van, $tot)
{
	$session = new Session();
	
	$html = $session->get("/internetbankieren/SesamLoginServlet");
	
	if($html === null) {
		error("Unable to start session\n");
	}
	
	preg_match(':<div id="gebruikersnaam" class="form_element">\\s*<label[^>]*>Gebruikersnaam</label>\\s*<div class="tooltip-icon"></div>\\s*<input[^>]* type="text"[^>]* name="(?<username>[^"]*)"[^>]*/>\\s*</div>:', str_replace("\n", " ", $html), $usernames);
	
	preg_match(':<div id="wachtwoord" class="form_element">\\s*<label[^>]*>Wachtwoord</label>\\s*<div class="tooltip-icon"></div>\\s*<input[^>]* type="password"[^>]*name="(?<password>[^"]*)"\\s*id="[^"]*"[^>]*/>\\s*<div class="notification hide-element">\\s*<div class="notification-icon notification-error"></div>\\s*<div class="notification-message">\\s*</div>\\s*</div>\\s*</div>:', str_replace("\n", " ", $html), $passwords);
	
	if(!count($usernames) || !count($passwords)) {
		error("Unable to parse login page\n");
	}
	
	$usernameField = $usernames["username"];
	$passwordField = $passwords["password"];
	
	$html = $session->post("/internetbankieren/SesamLoginServlet", array($usernameField=>$username, $passwordField=>$password));
	if(strpos($html, '<form id="changepasswdform" action="/internetbankieren/SesamChangePasswordServlet" autocomplete="off" method="post">') !== false) {
		error("Het wachtwoord van ing.nl moet gewijzigd worden. Tot dat punt is het afschrift import script stuk.\n");
	}
	if(strpos($html, '<meta http-equiv="refresh" content="0;URL=/internetbankieren/jsp/IndexLogon.jsp" />') === false) {
		error("Unable to login\n");
	}
	
	$session->get("/internetbankieren/jsp/IndexLogon.jsp");
	$session->get("/internetbankieren/jsp/sesam_cockpit.jsp");
	$session->get("/mpz/belangrijkbericht/psdbericht.do");
	$session->get("/mpz/startframes.do");
	$session->get("/mpz/startframes.do");
	$session->get("/mpz/startpagina.do");
	$session->get("/mpz/startpaginarekeninginfo.do");
	$session->get("/mpz/LandingPageContentServlet?doel=Navigatie+Home");
	$session->get("/mpz/weboffer/aanbieding.do");
	
	
	
	$html = $session->get("/mpz/DeepLinkServlet?ID=GirorekRdpl");
	preg_match(':menu/servlet/NavigatieMenuServlet\\?seqts=[0-9]+:', $html, $navMatch);
	preg_match(':menu/servlet/MenuServlet\\?seqts=[0-9]+&ID=GirorekRdpl&Load=Y:', $html, $menuMatch);
	
	if(!count($navMatch) || !count($menuMatch)) {
		error("Unable to parse start page");
	}
	
	$navUrl = "/mpz/" . $navMatch[0];
	$menuUrl = "/mpz/" . $menuMatch[0];
	
	$session->get($navUrl);
	$html = $session->get($menuUrl);
	preg_match(':/mpz/girordpl/girorekeningraadplegeninit\\.do\\?seqts=[0-9]+:', $html, $match);
	if(!count($match)) {
		error("Unable to parse transaction page");
	}
	$initUrl = $match[0];
	
	$html = $session->get($initUrl);
	$datumtot = hidden($html, "datumtot");
	
	$html = $session->post("/mpz/girordpl/downloadperiodeselecteren.do", array(
		"returnscreen"=>"girorekeningraadplegen.do",
		"mutatie_selected"=>"",
		"mutatiesbladeren"=>"",
		"datumvan"=>"",
		"datumtot"=>$datumtot,
		"vorigerekeningselectie"=>"0",
		"afkomstigvan"=>"raadplegen",
		"afdrukkenvoorbrowser"=>"",
		"rekeningselectie"=>"0"));
	$screen = hidden($html, "screen");
	$returnscreen = hidden($html, "returnscreen");
	$time = hidden($html, "time");
	
	$html = $session->post("/mpz/girordpl/downloadcheck.do", array(
		"screen"=>$screen,
		"returnscreen"=>$returnscreen,
		"time"=>$time,
		"rekeningselectie"=>"0",
		"datumvan"=>$van,
		"datumtot"=>$tot,
		"formaat"=>"kommacsv"));
	preg_match(':"download.do?(?<url>[^"]*)":', $html, $match);
	if(!count($match)) {
		error("Unable to parse download page");
	}
	$downloadUrl = "/mpz/girordpl/download.do?" . $match["url"];
	
	$csv = $session->get($downloadUrl);
	$parsed = parse_csv($csv);
	if($parsed === null) {
		error("Unable to parse CSV");
	}
	return $parsed;
}

$transacties = download_afschrift($ingUsername, $ingPassword, date("d-m-Y", time() - 86400 * 31), date("d-m-Y", time() - 86400));

$database = new MysqlConnection();
$database->open($dbHostname, $dbUsername, $dbPassword, $dbDatabase);

foreach($transacties as $transactie) {
	if(!$database->stdExists("transacties", $transactie)) {
		$database->stdNew("transacties", $transactie);
		
		$richting = $transactie["centenbij"] < 0 ? "naar" : "van";
		$sign = $transactie["centenbij"] < 0 ? "-" : "+";
		$datum = date("Y-m-d", $transactie["datum"]);
		$body = <<<BODY
Datum: $datum
Naam: {$transactie["naam"]}
Rekening: {$transactie["tegenrekening"]}
Bedrag: $sign{$transactie["bedrag"]}
Beschrijving:
{$transactie["beschrijving"]}

BODY;
		$mail = new mimemail();
		$mail->addReceiver($mailAddress, "Treva Afschriften");
		$mail->setSender($mailAddress, "Treva Afschriften - {$transactie["naam"]}");
		$mail->setSubject("Afschrift - {$transactie["bedrag"]} $richting {$transactie["naam"]}");
		$mail->setTextMessage($body);
		$mail->send();
	}
}

?>