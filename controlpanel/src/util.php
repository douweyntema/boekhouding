<?php

define("COUNTRYCODES_FILE", dirname(__FILE__) . "/../countrycodes");
define("RESERVED_USERNAMES_FILE", dirname(__FILE__) . "/../../reserved-usernames");

function searchKey($array /*, $keys */)
{
	$keys = func_get_args();
	array_shift($keys);
	foreach($keys as $key) {
		if(isset($array[$key])) {
			return $key;
		}
	}
	return null;
}

function formatPrice($cents, $symbol = "&euro;")
{
	return "$symbol " . formatPriceRaw($cents);
}

function formatPriceRaw($cents)
{
	return ($cents < 0 ? "-" : "") . floor(abs($cents) / 100) . "," . str_pad(abs($cents) % 100, 2, "0", STR_PAD_LEFT);
}

function parsePrice($string)
{
	$count = preg_match("/^(?<negatief>[-]?)(?<euro>[0-9]*)([,\\.](?<cent>[0-9]{2}))?$/", $string, $matches);
	if($count !== 1) {
		return null;
	}
	if(isset($matches["negatief"]) && $matches["negatief"] == "-") {
		$negatief = -1;
	} else {
		$negatief = 1;
	}
	if(isset($matches["cent"])) {
		return $negatief * ($matches["euro"] * 100 + $matches["cent"]);
	} else {
		return $negatief * ($matches["euro"] * 100);
	}
}

function normalizePrice(&$values, $name)
{
	if(isset($values[$name])) {
		$values[$name] = formatPriceRaw(parsePrice($values[$name]));
	}
}

function parseDate($string)
{
	$date = strtotime($string);
	if($date === false) {
		return null;
	}
	return $date;
}

function texdate($date)
{
	$maanden = array("",
		"januari",
		"februari",
		"maart",
		"april",
		"mei",
		"juni",
		"juli",
		"augustus",
		"september",
		"oktober",
		"november",
		"december");
	$day = date("j", $date);
	$month = date("n", $date);
	$year = date("Y", $date);
	return $day . " " . $maanden[$month] . " " . $year;
}

function latexEscapeString($string)
{
	$replaces = array(
		'\\'=>'\\backslash',
		'~'=>' ',
		'{'=>'\\{',
		'}'=>'\\}',
		'`'=>'\\`{}',
		'#'=>'\\#',
		'$'=>'\\$',
		'%'=>'\\%',
		'^'=>'\\^',
		'&'=>'\\&',
		'_'=>'\\_',
		'|'=>'\\textbar\\ ',
		'<'=>'\\textless',
		'>'=>'\\textgreater'
	);
	
	$search = array();
	$replace = array();
	foreach($replaces as $from=>$to) {
		$search[] = $from;
		$replace[] = $to;
	}
	return str_replace($search, $replace, $string);
}

function countryCodes()
{
	$countryCodes = array();
	foreach(explode("\n", file_get_contents(COUNTRYCODES_FILE)) as $line) {
		if($line == "") {
			continue;
		}
		$parts = explode(" ", $line);
		$code = $parts[0];
		array_shift($parts);
		$name = implode(" ", $parts);
		$countryCodes[$code] = $name; 
	}
	return $countryCodes;
}

function countryName($code)
{
	$code = strtoupper($code);
	$country = countryCodes();
	if(isset($country[$code])) {
		return $country[$code];
	} else {
		return $code;
	}
}

function validIPv4($ip)
{
	if(count(explode(".", $ip)) != 4) {
		return false;
	}
	foreach(explode(".", $ip) as $part) {
		if(!ctype_digit($part)) {
			return false;
		}
		if($part < 0 || $part > 255) {
			return false;
		}
	}
	return true;
}

function validIPv6($ip)
{
	$parts = explode(":", $ip);
	if(count($parts) > 8) {
		return false;
	}
	$emptyFound = false;
	for($i = 1; $i < count($parts) - 1; $i++) {
		if($parts[$i] == "") {
			if($emptyFound) {
				return false;
			} else {
				$emptyFound = true;
			}
		}
	}
	if($parts[0] == "" && $parts[1] != "") {
		return false;
	}
	if($parts[count($parts) - 1] == "" && $parts[count($parts) - 2] != "") {
		return false;
	}
	if(!$emptyFound && count($parts) != 8) {
		return false;
	}
	foreach($parts as $part) {
		if(strlen($part) > 4) {
			return false;
		}
		for($i = 0; $i < strlen($part); $i++) {
			if(trim($part[$i], "1234567890abcdefABCDEF") != "") {
				return false;
			}
		}
	}
	return true;
}

function validAccountName($username)
{
	if(strlen($username) < 3 || strlen($username) > 30) {
		return false;
	}
	if(preg_match('/^[a-zA-Z_][-a-zA-Z0-9_]*$/', $username) != 1) {
		return false;
	}
	return true;
}

function reservedAccountName($username)
{
	foreach(explode("\n", file_get_contents(RESERVED_USERNAMES_FILE)) as $reserved) {
		$reserved = trim($reserved);
		if($reserved == "" || $reserved[0] == "#") {
			continue;
		}
		if($username == $reserved) {
			return true;
		}
	}
	return false;
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
	
	if(trim($localpart, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-_.^*={}") != "") {
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

function validDomainPart($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	if(preg_match('/^[-_a-zA-Z0-9]*$/', $name) != 1) {
		return false;
	}
	return true;
}

function validDomain($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	
	$parts = explode(".", $name);
	if(count($parts) == 0) {
		return false;
	}
	
	foreach($parts as $part) {
		if(!validDomainPart($part)) {
			return false;
		}
	}
	
	return true;
}

function validDocumentRoot($root)
{
	if(strlen($root) > 255) {
		return false;
	}
	if(substr($root, 0, 1) == '/') {
		$root = substr($root, 1);
	}
	if(substr($root, -1) == '/') {
		$root = substr($root, 0, -1);
	}
	$parts = explode("/", $root);
	foreach($parts as $part) {
		if(!validDirectory($part)) {
			return false;
		}
		if($part == "." || $part == "..") {
			return false;
		}
	}
	return true;
}

function validDatabaseName($name)
{
	if(strlen($name) < 1 || strlen($name) > 255) {
		return false;
	}
	if(preg_match('/^[-a-zA-Z0-9_]*$/', $name) != 1) {
		return false;
	}
	return true;
}

function pdfLatex($tex)
{
	$dir = tempnam("/tmp", "controlpanel-");
	unlink($dir);
	mkdir($dir);
	chdir($dir);
	
	$h = fopen($dir . "/file.tex", "w");
	fwrite($h, $tex);
	fclose($h);
	
	$md5 = "";
	$count = 10;
	do {
		`/usr/bin/pdflatex $dir/file.tex`;
		if(!file_exists("$dir/file.pdf")) {
			`rm -r $dir`;
			return null;
		}
		$oldmd5 = $md5;
		$md5 = md5(file_get_contents("$dir/file.pdf"));
		$count--;
	} while($md5 != $oldmd5 && $count > 0);
	
	$pdf = file_get_contents($dir . "/file.pdf");
	`rm -r $dir`;
	return $pdf;
}

?>