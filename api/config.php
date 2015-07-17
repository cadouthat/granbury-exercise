<?php
/*
Configuration for Granbury API
*/
define("DB_HOST", "localhost");
define("DB_USER", "granbury");
define("DB_PASS", "granbury");
define("DB_NAME", "granbury");

if(!defined("API_DEBUG")) define("API_DEBUG", false);
if(!defined("API_TEST")) define("API_TEST", false);

date_default_timezone_set("America/Los_Angeles");
?>