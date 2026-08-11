<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Não autorizado"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $atividade_id = $_POST['atividade_id'] ?? null;
    $codigo = $_POST['codigo'] ?? null;
    
    if (!$atividade_id) {
        http_response_code(400);
        echo json_encode(["error" => "ID da atividade não informado"]);
        exit;
    }

    if (empty(trim($codigo))) {
        http_response_code(400);
        echo json_encode(["error" => "O código não pode estar vazio"]);
        exit;
    }

    try {
        // Verifica se a atividade existe
        $stmt = $pdo->prepare("SELECT xp_recompensa FROM atividades WHERE id = ?");
        $stmt->execute([$atividade_id]);
        $atividade = $stmt->fetch();

        if (!$atividade) {
            http_response_code(404);
            echo json_encode(["error" => "Atividade não encontrada"]);
            exit;
        }

        // Verifica se já entregou
        $stmt = $pdo->prepare("SELECT id FROM entregas_alunos WHERE atividade_id = ? AND usuario_id = ?");
        $stmt->execute([$atividade_id, $user_id]);
        if ($stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(["error" => "Você já enviou esta atividade"]);
            exit;
        }

        // Insere entrega (usando conteúdo para Snippets de Código)
        $stmt = $pdo->prepare("INSERT INTO entregas_alunos (atividade_id, usuario_id, conteudo) VALUES (?, ?, ?)");
        $stmt->execute([$atividade_id, $user_id, $codigo]);

        // Adiciona XP
        $xp = $atividade['xp_recompensa'];
        $pdo->exec("UPDATE usuarios SET xp = xp + $xp WHERE id = $user_id");

        echo json_encode(["success" => true, "message" => "Atividade enviada com sucesso!", "xp_ganho" => $xp]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erro no servidor: " . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Método não permitido"]);
}
