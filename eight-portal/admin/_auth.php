<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['admin','agente'])) {
    header('Location: ../portal.php'); exit;
}
