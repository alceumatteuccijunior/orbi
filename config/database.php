<?php
/**
 * Orbi Educational Portal - Database Configuration & Auto-Migrations
 * Conecta ao banco de dados e cria as tabelas ausentes na inicialização.
 */

// Configurações do Banco de Dados
$host = 'localhost';
$dbname = 'castelob_orbi';
$user = 'castelob_orbi';
$pass = 'M7bqmXvZ64UjMG7C3Q6A';

try {
    // Tenta conectar no servidor MySQL sem selecionar o banco para criá-lo se não existir
    $pdo_setup = new PDO("mysql:host=$host", $user, $pass);
    $pdo_setup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_setup->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Agora conecta selecionando o banco de dados
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Array de Migrations (Tabelas do Sistema)
    $tables = [
        "usuarios" => "CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            google_id VARCHAR(255) UNIQUE,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            foto_perfil VARCHAR(500),
            nivel_acesso ENUM('aluno', 'professor', 'admin_master') DEFAULT 'aluno',
            xp INT DEFAULT 0,
            nivel_atual VARCHAR(50) DEFAULT 'Script Kiddie',
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "cursos" => "CREATE TABLE IF NOT EXISTS cursos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            descricao TEXT,
            icone VARCHAR(100),
            status ENUM('ativo', 'inativo') DEFAULT 'ativo'
        )",
        
        "semestres" => "CREATE TABLE IF NOT EXISTS semestres (
            id INT AUTO_INCREMENT PRIMARY KEY,
            curso_id INT,
            nome VARCHAR(255) NOT NULL,
            FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
        )",
        
        "unidades_curriculares" => "CREATE TABLE IF NOT EXISTS unidades_curriculares (
            id INT AUTO_INCREMENT PRIMARY KEY,
            semestre_id INT,
            nome VARCHAR(255) NOT NULL,
            descricao TEXT,
            FOREIGN KEY (semestre_id) REFERENCES semestres(id) ON DELETE CASCADE
        )",
        
        "alunos_ucs" => "CREATE TABLE IF NOT EXISTS alunos_ucs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT,
            uc_id INT,
            status ENUM('pendente', 'aprovado', 'recusado') DEFAULT 'pendente',
            data_solicitacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (uc_id) REFERENCES unidades_curriculares(id) ON DELETE CASCADE
        )",
        
        "modulos" => "CREATE TABLE IF NOT EXISTS modulos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            uc_id INT,
            nome VARCHAR(255) NOT NULL,
            ordem INT DEFAULT 0,
            status ENUM('ativo', 'bloqueado') DEFAULT 'ativo',
            FOREIGN KEY (uc_id) REFERENCES unidades_curriculares(id) ON DELETE CASCADE
        )",
        
        "aulas" => "CREATE TABLE IF NOT EXISTS aulas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            modulo_id INT,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            tipo ENUM('video', 'pdf', 'desafio', 'misto') DEFAULT 'video',
            url_ou_caminho VARCHAR(500),
            ordem INT DEFAULT 0,
            data_publicacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE
        )",
        
        "atividades" => "CREATE TABLE IF NOT EXISTS atividades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aula_id INT,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            status_envio ENUM('aberto', 'fechado') DEFAULT 'aberto',
            data_limite DATETIME NULL,
            xp_recompensa INT DEFAULT 100,
            FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE
        )",
        
        "entregas_alunos" => "CREATE TABLE IF NOT EXISTS entregas_alunos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            atividade_id INT,
            usuario_id INT,
            tipo_arquivo ENUM('link', 'imagem', 'pdf', 'zip', 'snippet') NOT NULL,
            caminho_arquivo_ou_link VARCHAR(500),
            conteudo_snippet LONGTEXT,
            data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
            xp_ganho INT DEFAULT 0,
            FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        )",
        
        "conquistas" => "CREATE TABLE IF NOT EXISTS conquistas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            icone VARCHAR(100)
        )",
        
        "usuario_conquistas" => "CREATE TABLE IF NOT EXISTS usuario_conquistas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT,
            conquista_id INT,
            data_conquista DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (conquista_id) REFERENCES conquistas(id) ON DELETE CASCADE
        )",
        
        "feed_comunidade" => "CREATE TABLE IF NOT EXISTS feed_comunidade (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT,
            uc_id INT,
            tipo ENUM('duvida', 'snippet', 'showcase') DEFAULT 'duvida',
            conteudo TEXT,
            data_postagem DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (uc_id) REFERENCES unidades_curriculares(id) ON DELETE CASCADE
        )",
        
        "comentarios_feed" => "CREATE TABLE IF NOT EXISTS comentarios_feed (
            id INT AUTO_INCREMENT PRIMARY KEY,
            feed_id INT,
            usuario_id INT,
            conteudo TEXT,
            data_comentario DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (feed_id) REFERENCES feed_comunidade(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        )",
        
        "quizzes_live" => "CREATE TABLE IF NOT EXISTS quizzes_live (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT,
            titulo VARCHAR(255),
            status ENUM('aguardando', 'ativo', 'finalizado') DEFAULT 'aguardando',
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES usuarios(id)
        )",
        
        "quizzes_perguntas" => "CREATE TABLE IF NOT EXISTS quizzes_perguntas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quiz_id INT,
            pergunta TEXT,
            tempo_segundos INT DEFAULT 30,
            alternativas JSON, /* Ex: {A: 'texto', B: 'texto', C: 'texto', D: 'texto', correta: 'C'} */
            FOREIGN KEY (quiz_id) REFERENCES quizzes_live(id) ON DELETE CASCADE
        )",
        
        "quizzes_respostas" => "CREATE TABLE IF NOT EXISTS quizzes_respostas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quiz_id INT,
            pergunta_id INT,
            usuario_id INT,
            resposta VARCHAR(10),
            tempo_resposta INT,
            pontos_ganhos INT DEFAULT 0,
            FOREIGN KEY (quiz_id) REFERENCES quizzes_live(id) ON DELETE CASCADE,
            FOREIGN KEY (pergunta_id) REFERENCES quizzes_perguntas(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        )",
        
        "desafios_p2p" => "CREATE TABLE IF NOT EXISTS desafios_p2p (
            id INT AUTO_INCREMENT PRIMARY KEY,
            desafiante_id INT,
            desafiado_id INT,
            tipo ENUM('codigo', 'quiz') DEFAULT 'codigo',
            status ENUM('pendente', 'ativo', 'finalizado', 'recusado') DEFAULT 'pendente',
            vencedor_id INT NULL,
            xp_apostado INT DEFAULT 0,
            data_desafio DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (desafiante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (desafiado_id) REFERENCES usuarios(id) ON DELETE CASCADE
        )"
    ];

    // Executa as queries de criação de tabela
    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
    }

} catch (PDOException $e) {
    die("Erro na conexão ou migração do banco de dados: " . $e->getMessage());
}
?>
