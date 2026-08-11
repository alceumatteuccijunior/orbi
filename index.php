<?php
/**
 * teeeeeeeeeeeeeeeeeeeeeeeessssste
 * Orbi Educacional - Root Entry Point
 * Este arquivo fica na raiz (public_html) e faz o roteamento inteligente.
 */
session_start();

// Se o usuário não estiver logado, redireciona para a tela de Login
if (!isset($_SESSION['user_id'])) {
    header("Location: public/login.php");
    exit;
}

// Se estiver logado, redireciona conforme o nível de acesso
if (isset($_SESSION['user_nivel'])) {
    if ($_SESSION['user_nivel'] === 'admin_master' || $_SESSION['user_nivel'] === 'professor') {
        header("Location: admin/index.php");
    } else {
        header("Location: public/index.php"); // Dashboard do Aluno
    }
    exit;
}
?>