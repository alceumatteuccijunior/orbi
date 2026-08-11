<?php
session_start();

// ==============================================================================
// CONFIGURAÇÕES DO GOOGLE OAUTH
// ==============================================================================
require_once '../config/secrets.php';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$redirect_uri = $protocol . '://' . $host . '/public/auth.php';
define('GOOGLE_REDIRECT_URI', $redirect_uri);

// URL de Autenticação do Google
$login_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'online',
    'prompt' => 'select_account consent'
]);
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orbi - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bg: '#09090b', 
                        surface: '#18181b', 
                        border: '#27272a', 
                        muted: '#a1a1aa', 
                        accent: '#2dd4bf', // Ciano/Neon
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #09090b; color: #f4f4f5; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .bg-dots {
            background-image: radial-gradient(rgba(45, 212, 191, 0.15) 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden selection:bg-accent/30 selection:text-accent">

    <!-- LADO ESQUERDO: BANNER (Visível apenas em telas grandes) -->
    <div class="hidden lg:flex w-1/2 bg-surface relative flex-col justify-center p-16 border-r border-border overflow-hidden">
        
        <!-- Elementos Gráficos Hacker/Tech -->
        <div class="absolute inset-0 z-0 bg-[radial-gradient(circle_at_bottom_left,_var(--tw-gradient-stops))] from-accent/10 via-transparent to-transparent opacity-50"></div>
        <div class="absolute top-20 right-20 w-72 h-72 bg-accent/5 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="absolute bottom-1/4 right-16 w-32 h-32 border border-accent/10 rounded-full"></div>
        <div class="absolute bottom-[23%] right-[3.5rem] w-40 h-40 border border-accent/5 rounded-full"></div>
        
        <!-- Matriz de Pontos -->
        <div class="absolute top-1/4 right-1/4 w-32 h-32 bg-dots opacity-50"></div>

        <div class="relative z-10 max-w-xl">
            <h1 class="text-5xl font-bold mb-6 text-white leading-tight tracking-tight">
                Bem-vindo <br>de volta!
            </h1>
            <p class="text-lg text-zinc-400 mb-10 leading-relaxed font-light">
                Faça login para acessar o Orbi com sua conta institucional. Explore trilhas de conhecimento, participe de arenas ao vivo e evolua suas habilidades em desenvolvimento.
            </p>
            
            <!-- Terminal estilizado pequeno -->
            <div class="flex items-center gap-3 text-xs font-mono text-accent/70 border border-accent/20 bg-accent/5 w-fit px-4 py-2.5 rounded backdrop-blur-sm">
                <i data-lucide="terminal" class="w-4 h-4"></i>
                <span>root@orbi:~# system_login --secure</span>
                <span class="w-1.5 h-4 bg-accent/70 animate-pulse inline-block ml-1"></span>
            </div>
        </div>
    </div>

    <!-- LADO DIREITO: FORMULÁRIO DE LOGIN -->
    <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-8 md:p-16 relative bg-bg">
        
        <!-- Debug Box Oculta para Produção, mas útil se der erro -->
        <?php if(isset($_GET['debug'])): ?>
        <div class="absolute top-4 left-4 right-4 bg-yellow-500/10 border border-yellow-500/50 text-yellow-500 text-xs p-3 rounded-md">
            DEBUG URI: <?= htmlspecialchars(GOOGLE_REDIRECT_URI) ?>
        </div>
        <?php endif; ?>

        <div class="w-full max-w-sm">
            
            <!-- Logo Orbi -->
            <div class="flex items-center gap-3 mb-10 justify-center lg:justify-start">
                <div class="w-10 h-10 bg-surface border border-accent/50 rounded-xl flex items-center justify-center shadow-[0_0_15px_rgba(45,212,191,0.15)] relative overflow-hidden">
                    <div class="absolute inset-0 bg-accent/20 animate-pulse"></div>
                    <i data-lucide="hexagon" class="w-5 h-5 text-accent relative z-10"></i>
                </div>
                <span class="text-2xl font-bold tracking-tight text-white">Orbi</span>
            </div>

            <!-- Títulos do Formulário -->
            <div class="text-center lg:text-left mb-8">
                <h2 class="text-2xl font-semibold mb-2 text-white">Entrar</h2>
                <p class="text-sm text-zinc-400">Acesso seguro com Google Workspace</p>
            </div>

            <!-- Alerta de Erro -->
            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-xs px-4 py-3 rounded-lg mb-6 flex items-start gap-3">
                    <i data-lucide="shield-alert" class="w-4 h-4 mt-0.5 shrink-0"></i> 
                    <span>Falha na autenticação. Verifique se você usou o e-mail institucional correto.</span>
                </div>
            <?php endif; ?>

            <!-- Botão Google -->
            <a href="<?= htmlspecialchars($login_url) ?>" class="flex items-center justify-center gap-3 w-full bg-white text-zinc-900 font-semibold text-sm px-6 py-3.5 rounded-lg hover:bg-zinc-200 transition-all duration-200 shadow-[0_4px_14px_0_rgba(255,255,255,0.1)] hover:shadow-[0_6px_20px_rgba(255,255,255,0.15)] hover:-translate-y-0.5">
                <svg viewBox="0 0 24 24" class="w-5 h-5">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Continuar com o Google
            </a>

            <!-- Divisor -->
            <div class="mt-8 flex items-center justify-center">
                <div class="flex-1 h-px bg-border/60"></div>
                <span class="px-3 text-[10px] uppercase font-mono text-zinc-500 tracking-[0.2em]">Acesso Restrito</span>
                <div class="flex-1 h-px bg-border/60"></div>
            </div>

            <!-- Footer -->
            <p class="text-xs text-zinc-500 text-center mt-8">
                Não tem uma conta institucional? <br class="lg:hidden"><a href="#" class="text-accent hover:text-accentHover transition-colors">Fale com o suporte.</a>
            </p>

        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
