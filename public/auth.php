<?php
session_start();
require_once '../config/database.php';

// ==============================================================================
// CONFIGURAÇÕES PROVISÓRIAS DO GOOGLE OAUTH
// IMPORTANTE: Substitua pelas chaves reais
// ==============================================================================
require_once '../config/secrets.php';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$redirect_uri = $protocol . '://' . $host . '/public/auth.php';
define('GOOGLE_REDIRECT_URI', $redirect_uri);

if (isset($_GET['code'])) {
    
    // 1. Obter Access Token usando cURL puro
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/token");
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code',
        'code' => $_GET['code']
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        
        // 2. Obter Dados do Perfil do Usuário
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v2/userinfo");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $token_data['access_token']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $userinfo_response = curl_exec($ch);
        curl_close($ch);

        $google_user = json_decode($userinfo_response, true);

        // 3. Registrar ou Logar no Banco de Dados
        if (isset($google_user['email'])) {
            $email = $google_user['email'];
            $nome = $google_user['name'];
            $google_id = $google_user['id'] ?? null;
            $foto = $google_user['picture'] ?? null;

            // Opcional: Bloquear domínios não permitidos aqui
            // if (!str_ends_with($email, '@instituicao.edu.br')) { die("Acesso negado."); }

            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                // Usuário novo, criar conta (padrão 'aluno')
                $stmt = $pdo->prepare("INSERT INTO usuarios (google_id, nome, email, foto_perfil) VALUES (?, ?, ?, ?)");
                $stmt->execute([$google_id, $nome, $email, $foto]);
                $user_id = $pdo->lastInsertId();
                
                // Recarrega os dados recém-inseridos
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                // Atualiza foto e google_id caso já exista pelo email
                $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ?, google_id = ? WHERE id = ?");
                $stmt->execute([$foto, $google_id, $user['id']]);
            }

            // 4. Salvar Sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nivel'] = $user['nivel_acesso'];
            
            // 5. Redirecionar com base no nível de acesso
            if ($user['nivel_acesso'] === 'admin_master' || $user['nivel_acesso'] === 'professor') {
                header("Location: ../admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit;
        }
    }
}

// Em caso de erro ou acesso direto sem código, volta para login
header("Location: login.php?error=oauth_failed");
exit;
?>
