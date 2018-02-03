<?php

use Kassner\LogParser\LogParser;

require 'config.php';
require '../vendor/autoload.php';

try {
    $dbh = new PDO(config\database\DSN, config\database\USER, config\database\PASSWORD);
    echo "Connected\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e;
    die();
}
