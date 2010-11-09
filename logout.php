<?php

require_once("common.php");

session_start();
session_destroy();

header("HTTP/1.1 303 See Other");
header("Location: {$GLOBALS["root"]}");

?>