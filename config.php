<?php
// config.php

define('DB_HOST', 'localhost');
define('DB_USER', 'notificacoes');
define('DB_PASS', 'Ptacaapt@190667');
define('DB_NAME', 'notificacoes22gc');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Conexão com o banco de dados estabelecida com sucesso!"; // Remova em produção
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>