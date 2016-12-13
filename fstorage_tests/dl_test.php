<?php

$CLIENT_FILE = dirname(__FILE__) . "/../fstorage_client/fstorage_client.php";
require($CLIENT_FILE);
$client = new \FStorage\API_Client($_REQUEST['SERVER'],$_REQUEST['USER'],$_REQUEST['PASS']);

$client->downloadObject($_REQUEST['bucket'],$_REQUEST['key']);
