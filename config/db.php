<?php
require_once __DIR__.'/config.php';
function bd(): PDO {
    static $pdo;
    if (!$pdo) {
        try { $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); }
        catch (PDOException $error) { error_log($error->getMessage()); throw new RuntimeException('No se pudo conectar con lab_web. Inicia MySQL e importa sql/lab_web.sql.'); }
    }
    return $pdo;
}
