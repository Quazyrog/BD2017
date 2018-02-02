<?php
require_once '../lib/config.php';

try {
    $dbh = new PDO(config\database\DSN, config\database\USER, config\database\PASSWORD);
    echo "Connected";
} catch (PDOException $e) {
    echo "ERROR: " . $e;
    die();
}