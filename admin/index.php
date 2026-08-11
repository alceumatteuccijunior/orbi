<?php
session_start();
require_once '../config/database.php';

// Proteção de Rota: Apenas Admin ou Professor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_nivel'], ['admin_master', 'professor'])) {
    header("Location: ../public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT nome, email, foto_perfil, nivel_acesso FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$initials = strtoupper(substr($user['nome'], 0, 1));

// ================= COLETANDO ESTATÍSTICAS PARA O DASHBOARD =================
$total_alunos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE nivel_acesso = 'aluno'")->fetchColumn();
$total_cursos = $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
$entregas_pendentes = $pdo->query("SELECT COUNT(*) FROM alunos_ucs WHERE status = 'pendente'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Gestão - Orbi Educacional</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
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
                        accent: '#2dd4bf', 
                        accentHover: '#14b8a6', 
                        admin: '#f43f5e', // Rosa/Vermelho para diferenciar o painel Admin
                        adminHover: '#e11d48'
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #09090b; color: #f4f4f5; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #27272a; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #3f3f46; }
    </style>
</head>
<body class="flex h-screen overflow-hidden antialiased selection:bg-admin/30 selection:text-admin" x-data="{ sidebarOpen: false, currentTab: 'overview' }">

    <!-- MOBILE OVERLAY -->
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-bg/80 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false" style="display:none;"></div>

    <!-- SIDEBAR -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 border-r border-border bg-bg transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:w-64 flex flex-col justify-between">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-medium tracking-tight flex items-center gap-2 text-zinc-100">
                    <i data-lucide="shield" class="text-admin w-5 h-5"></i> Orbi Admin
                </h1>
                <button @click="sidebarOpen = false" class="lg:hidden text-muted hover:text-zinc-100"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            
            <nav class="mt-8 space-y-2">
                <button @click="currentTab = 'overview'; sidebarOpen = false" :class="currentTab === 'overview' ? 'bg-surface border-border text-zinc-100' : 'border-transparent text-muted hover:bg-surface/50 hover:text-zinc-100'" class="w-full flex items-center gap-3 text-sm px-3 py-2 rounded-md transition border group">
                    <i data-lucide="pie-chart" class="w-4 h-4 group-hover:text-admin transition-colors"></i> Visão Geral
                </button>
                <button @click="currentTab = 'cursos'; sidebarOpen = false" :class="currentTab === 'cursos' ? 'bg-surface border-border text-zinc-100' : 'border-transparent text-muted hover:bg-surface/50 hover:text-zinc-100'" class="w-full flex items-center gap-3 text-sm px-3 py-2 rounded-md transition border group">
                    <i data-lucide="library" class="w-4 h-4 group-hover:text-admin transition-colors"></i> Cursos & UCs
                </button>
                <button @click="currentTab = 'conteudo'; sidebarOpen = false" :class="currentTab === 'conteudo' ? 'bg-surface border-border text-zinc-100' : 'border-transparent text-muted hover:bg-surface/50 hover:text-zinc-100'" class="w-full flex items-center gap-3 text-sm px-3 py-2 rounded-md transition border group">
                    <i data-lucide="clapperboard" class="w-4 h-4 group-hover:text-admin transition-colors"></i> Aulas & Atividades
                </button>
                <button @click="currentTab = 'alunos'; sidebarOpen = false" :class="currentTab === 'alunos' ? 'bg-surface border-border text-zinc-100' : 'border-transparent text-muted hover:bg-surface/50 hover:text-zinc-100'" class="w-full flex items-center gap-3 text-sm px-3 py-2 rounded-md transition border group relative">
                    <i data-lucide="users" class="w-4 h-4 group-hover:text-admin transition-colors"></i> Alunos & Matrículas
                    <?php if($entregas_pendentes > 0): ?>
                        <span class="absolute right-3 top-2.5 w-2 h-2 rounded-full bg-admin"></span>
                    <?php endif; ?>
                </button>
            </nav>
        </div>

        <div class="p-4 border-t border-border bg-surface/30">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-surface border border-admin/50 flex items-center justify-center overflow-hidden">
                    <?php if($user['foto_perfil']): ?>
                        <img src="<?= htmlspecialchars($user['foto_perfil']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="text-sm font-medium text-zinc-100"><?= $initials ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex-1 overflow-hidden">
                    <h4 class="text-xs font-medium text-zinc-200 truncate"><?= htmlspecialchars($user['nome']) ?></h4>
                    <p class="text-[10px] text-admin uppercase tracking-wider"><?= htmlspecialchars($user['nivel_acesso']) ?></p>
                </div>
            </div>
            <a href="../public/logout.php" class="w-full flex items-center justify-center gap-2 text-[10px] text-zinc-500 hover:text-red-400 border border-border hover:border-red-500/30 rounded py-1 transition-colors mt-2">
                <i data-lucide="log-out" class="w-3 h-3"></i> Sair do Painel
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 h-screen bg-bg relative overflow-y-auto w-full">
        
        <header class="sticky top-0 z-40 bg-bg/80 backdrop-blur-md border-b border-border px-4 lg:px-8 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden text-zinc-100 p-1"><i data-lucide="menu" class="w-5 h-5"></i></button>
                <div class="hidden sm:flex items-center gap-3 text-sm text-muted bg-surface/50 border border-border rounded-md px-3 py-1.5 w-64 lg:w-80 focus-within:border-admin/50 transition">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <input type="text" placeholder="Buscar no admin..." class="bg-transparent border-none outline-none w-full text-zinc-200 placeholder-zinc-600">
                </div>
            </div>
            
            <a href="../public/index.php" class="text-xs text-muted hover:text-admin flex items-center gap-2 border border-border px-3 py-1.5 rounded transition">
                <i data-lucide="external-link" class="w-3 h-3"></i> Portal do Aluno
            </a>
        </header>

        <div class="p-4 lg:p-8 pb-24 max-w-7xl mx-auto w-full min-h-max">
            
            <!-- OVERVIEW -->
            <div x-show="currentTab === 'overview'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                <div class="flex justify-between items-end mb-6 border-b border-border pb-4">
                    <div>
                        <h2 class="text-xl font-medium text-zinc-100">Visão Geral</h2>
                        <p class="text-xs text-muted mt-1">Bem-vindo ao centro de comando da plataforma.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-surface/20 border border-border rounded-xl p-6 relative overflow-hidden">
                        <i data-lucide="users" class="absolute -right-4 -bottom-4 w-24 h-24 text-bg pointer-events-none"></i>
                        <h3 class="text-xs text-muted uppercase tracking-wider mb-2">Total de Alunos</h3>
                        <div class="text-3xl font-medium text-zinc-100"><?= $total_alunos ?></div>
                    </div>
                    <div class="bg-surface/20 border border-border rounded-xl p-6 relative overflow-hidden">
                        <i data-lucide="library" class="absolute -right-4 -bottom-4 w-24 h-24 text-bg pointer-events-none"></i>
                        <h3 class="text-xs text-muted uppercase tracking-wider mb-2">Cursos Ativos</h3>
                        <div class="text-3xl font-medium text-zinc-100"><?= $total_cursos ?></div>
                    </div>
                    <div class="bg-admin/10 border border-admin/30 rounded-xl p-6 relative overflow-hidden shadow-[0_0_20px_rgba(244,63,94,0.1)]">
                        <i data-lucide="bell-ring" class="absolute -right-4 -bottom-4 w-24 h-24 text-bg pointer-events-none"></i>
                        <h3 class="text-xs text-admin uppercase tracking-wider mb-2">Solicitações Pendentes</h3>
                        <div class="text-3xl font-medium text-admin"><?= $entregas_pendentes ?></div>
                    </div>
                </div>
            </div>

            <!-- CURSOS & UCS PLACEHOLDER -->
            <div x-show="currentTab === 'cursos'" style="display: none;">
                 <div class="text-center py-20 text-muted"><p>O módulo de Gestão de Cursos e UCs será carregado aqui.</p></div>
            </div>
            
            <!-- CONTEÚDO PLACEHOLDER -->
            <div x-show="currentTab === 'conteudo'" style="display: none;">
                 <div class="text-center py-20 text-muted"><p>O módulo de Criação de Aulas e Atividades será carregado aqui.</p></div>
            </div>

            <!-- ALUNOS PLACEHOLDER -->
            <div x-show="currentTab === 'alunos'" style="display: none;">
                 <div class="text-center py-20 text-muted"><p>O módulo de Aprovação de Matrículas será carregado aqui.</p></div>
            </div>

        </div>
    </main>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.effect(() => {
                const tab = Alpine.store('currentTab');
                setTimeout(() => lucide.createIcons(), 50);
            });
        });
        lucide.createIcons();
    </script>
</body>
</html>
