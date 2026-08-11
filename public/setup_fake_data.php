<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 1. Cria um Curso Fake
    $stmt = $pdo->query("SELECT id FROM cursos LIMIT 1");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("INSERT INTO cursos (nome, descricao) VALUES ('Formação Desenvolvedor Full-Stack', 'Curso completo de desenvolvimento de software.')");
        $curso_id = $pdo->lastInsertId();
    } else {
        $curso_id = $stmt->fetchColumn();
    }

    // 2. Cria um Semestre Fake
    $stmt = $pdo->prepare("SELECT id FROM semestres WHERE curso_id = ? LIMIT 1");
    $stmt->execute([$curso_id]);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("INSERT INTO semestres (curso_id, nome) VALUES ($curso_id, '1º Semestre de 2026')");
        $semestre_id = $pdo->lastInsertId();
    } else {
        $semestre_id = $stmt->fetchColumn();
    }

    // 3. Cria uma Matéria (UC) Fake
    $stmt = $pdo->prepare("SELECT id FROM unidades_curriculares WHERE semestre_id = ? LIMIT 1");
    $stmt->execute([$semestre_id]);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("INSERT INTO unidades_curriculares (semestre_id, nome, descricao) VALUES ($semestre_id, 'Back-end com PHP e MySQL', 'Aprenda a construir APIs seguras e manipular banco de dados.')");
        $uc_id = $pdo->lastInsertId();
    } else {
        $uc_id = $stmt->fetchColumn();
    }

    // 4. Matricula o usuário logado nessa UC
    $stmt = $pdo->prepare("SELECT id FROM alunos_ucs WHERE usuario_id = ? AND uc_id = ?");
    $stmt->execute([$user_id, $uc_id]);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("INSERT INTO alunos_ucs (usuario_id, uc_id, status) VALUES ($user_id, $uc_id, 'aprovado')");
    }

    // 5. Cria alguns Módulos de exemplo para a UC
    $stmt = $pdo->prepare("SELECT id FROM modulos WHERE uc_id = ? ORDER BY ordem ASC LIMIT 1");
    $stmt->execute([$uc_id]);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("INSERT INTO modulos (uc_id, nome, ordem, status) VALUES ($uc_id, 'Módulo 1: Fundamentos da Linguagem', 1, 'ativo')");
        $modulo1_id = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO modulos (uc_id, nome, ordem, status) VALUES ($uc_id, 'Módulo 2: Modelagem de Dados', 2, 'ativo')");
        $modulo2_id = $pdo->lastInsertId();
    } else {
        $modulo1_id = $stmt->fetchColumn();
    }

    // 6. Cria uma Aula e uma Atividade
    $stmt = $pdo->prepare("SELECT id FROM aulas WHERE modulo_id = ? LIMIT 1");
    $stmt->execute([$modulo1_id]);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("INSERT INTO aulas (modulo_id, titulo, descricao, tipo, ordem, xp_recompensa) VALUES ($modulo1_id, '1. O que é o PHP', 'Introdução ao ecossistema PHP', 'video', 1, 50)");
        $aula1_id = $pdo->lastInsertId();
        
        $pdo->exec("INSERT INTO aulas (modulo_id, titulo, descricao, tipo, ordem, xp_recompensa) VALUES ($modulo1_id, '2. Desafio Prático', 'Envie seu primeiro script', 'desafio', 2, 100)");
        $aula2_id = $pdo->lastInsertId();

        // Adiciona a atividade na aula 2
        $pdo->exec("INSERT INTO atividades (aula_id, titulo, descricao, xp_recompensa) VALUES ($aula2_id, 'Hello World em PHP', 'Escreva um script PHP que imprima Hello World.', 100)");
    }

} catch (PDOException $e) {
    die("Erro ao popular dados fakes: " . $e->getMessage());
}

header("Location: index.php");
exit;
?>
