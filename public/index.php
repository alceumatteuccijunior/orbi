<?php
session_start();
require_once '../config/database.php';

// Proteção de Rota
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Busca dados do usuário logado
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Lógica de iniciais do nome para o Avatar
$nameParts = explode(' ', trim($user['nome']));
$initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

// Busca os cursos matriculados do aluno
$stmt = $pdo->prepare("
    SELECT uc.id, uc.nome, uc.descricao, c.icone, a_uc.status 
    FROM alunos_ucs a_uc
    JOIN unidades_curriculares uc ON a_uc.uc_id = uc.id
    JOIN semestres s ON uc.semestre_id = s.id
    JOIN cursos c ON s.curso_id = c.id
    WHERE a_uc.usuario_id = ? AND a_uc.status = 'aprovado'
");
$stmt->execute([$user_id]);
$cursos_matriculados = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orbi - Painel do Aluno</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    
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
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #09090b; color: #f4f4f5; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #27272a; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #3f3f46; }
    </style>
</head>
<body class="flex h-screen overflow-hidden antialiased selection:bg-accent/30 selection:text-accent" x-data="{ sidebarOpen: false, currentTab: 'cursos', activeModule: 2 }">

    <!-- MOBILE OVERLAY -->
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-bg/80 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

    <!-- SIDEBAR -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 border-r border-border bg-bg transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:w-64 flex flex-col justify-between">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-medium tracking-tight flex items-center gap-2 text-zinc-100">
                    <i data-lucide="hexagon" class="text-accent w-5 h-5 animate-pulse-slow"></i> Orbi
                </h1>
                <button @click="sidebarOpen = false" class="lg:hidden text-muted hover:text-zinc-100"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            
            <nav class="mt-8 space-y-2">
                <button @click="currentTab = 'dashboard'; sidebarOpen = false" :class="currentTab === 'dashboard' ? 'bg-surface border-border text-zinc-100' : 'border-transparent text-muted hover:bg-surface/50 hover:text-zinc-100'" class="w-full flex items-center gap-3 text-sm px-3 py-2 rounded-md transition border group">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 group-hover:text-accent transition-colors"></i> Dashboard Geral
                </button>
                <button @click="currentTab = 'cursos'; sidebarOpen = false" :class="currentTab === 'cursos' ? 'bg-surface border-border text-zinc-100' : 'border-transparent text-muted hover:bg-surface/50 hover:text-zinc-100'" class="w-full flex items-center gap-3 text-sm px-3 py-2 rounded-md transition border group relative">
                    <i data-lucide="book-open" class="w-4 h-4 group-hover:text-accent transition-colors"></i> Meus Cursos
                    <span x-show="currentTab !== 'cursos'" class="absolute right-3 top-2.5 w-1.5 h-1.5 rounded-full bg-accent"></span>
                </button>
                <div class="pt-4 pb-2">
                    <div class="h-px w-full bg-border"></div>
                </div>
                <button @click="currentTab = 'arena'; sidebarOpen = false" :class="currentTab === 'arena' ? 'bg-surface border-border text-zinc-100' : 'border-transparent text-muted hover:bg-surface/50 hover:text-zinc-100'" class="w-full flex items-center gap-3 text-sm px-3 py-2 rounded-md transition border group relative">
                    <i data-lucide="monitor-play" class="w-4 h-4 group-hover:text-accent transition-colors"></i> Arena Ao Vivo
                    <span class="absolute right-3 top-2.5 w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                    <span class="absolute right-3 top-2.5 w-2 h-2 rounded-full bg-red-500"></span>
                </button>
            </nav>
        </div>

        <!-- Current Learning / User Rank -->
        <div class="p-4 border-t border-border bg-surface/30">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-surface border border-accent/50 flex items-center justify-center overflow-hidden">
                    <?php if($user['foto_perfil']): ?>
                        <img src="<?= htmlspecialchars($user['foto_perfil']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="text-sm font-medium text-zinc-100"><?= $initials ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex-1 overflow-hidden">
                    <h4 class="text-xs font-medium text-zinc-200 truncate"><?= htmlspecialchars($user['nome']) ?></h4>
                    <p class="text-[10px] text-accent font-mono"><?= htmlspecialchars($user['nivel_atual']) ?> • <?= $user['xp'] ?>_B</p>
                </div>
            </div>
            <a href="logout.php" class="w-full flex items-center justify-center gap-2 text-[10px] text-zinc-500 hover:text-red-400 border border-border hover:border-red-500/30 rounded py-1 transition-colors mt-2">
                <i data-lucide="log-out" class="w-3 h-3"></i> Sair
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex flex-col h-full bg-bg relative overflow-y-auto w-full">
        
        <!-- TOPBAR -->
        <header class="sticky top-0 z-40 bg-bg/80 backdrop-blur-md border-b border-border px-4 lg:px-8 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden text-zinc-100 p-1"><i data-lucide="menu" class="w-5 h-5"></i></button>
                <div class="hidden sm:flex items-center gap-3 text-sm text-muted bg-surface/50 border border-border rounded-md px-3 py-1.5 w-64 lg:w-80 focus-within:border-accent/50 transition">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <input type="text" placeholder="Buscar cursos, aulas ou fórum..." class="bg-transparent border-none outline-none w-full text-zinc-200 placeholder-zinc-600">
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button class="text-muted hover:text-zinc-100 transition relative">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    <span class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-accent rounded-full border border-bg"></span>
                </button>
            </div>
        </header>

        <div class="p-4 lg:p-8 pb-24 max-w-7xl mx-auto w-full overflow-hidden">
            
            <!-- ================= VIEW: MEUS CURSOS ================= -->
            <div x-show="currentTab === 'cursos'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                
                <div class="flex justify-between items-end mb-6 border-b border-border pb-4">
                    <div>
                        <h2 class="text-xl font-medium text-zinc-100">Meus Cursos</h2>
                        <p class="text-xs text-muted mt-1">Sua prateleira de conhecimentos matriculados.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <?php if (empty($cursos_matriculados)): ?>
                        
                        <!-- ESTADO VAZIO: Nenhum curso matriculado -->
                        <div class="col-span-full py-16 text-center border border-border border-dashed rounded-xl bg-surface/5">
                            <i data-lucide="inbox" class="w-12 h-12 text-muted mx-auto mb-4"></i>
                            <h3 class="text-lg font-medium text-zinc-300">Nenhum curso encontrado</h3>
                            <p class="text-sm text-zinc-500 mt-2 max-w-sm mx-auto">Você ainda não foi matriculado em nenhuma Trilha de Conhecimento. Aguarde a liberação do Administrador ou solicite acesso no Catálogo.</p>
                            
                            <!-- BOTAO TEMPORÁRIO PARA INSERIR DADOS FAKES (Só pra vc ver rodando agora) -->
                            <form action="setup_fake_data.php" method="POST" class="mt-8">
                                <button type="submit" class="bg-accent/10 border border-accent/50 text-accent hover:bg-accent/20 px-4 py-2 rounded text-xs transition-colors">
                                    [Admin] Popular banco com 1 Curso Fake para Teste
                                </button>
                            </form>
                        </div>
                    
                    <?php else: ?>
                        
                        <!-- RENDERIZAÇÃO REAL DOS CURSOS DO BANCO -->
                        <?php foreach($cursos_matriculados as $curso): ?>
                        <div class="border border-border bg-surface/10 rounded-xl overflow-hidden hover:border-accent/50 transition-all duration-300 group cursor-pointer shadow-lg shadow-black/50 hover:-translate-y-1" @click="currentTab = 'trilha_<?= $curso['id'] ?>'">
                            <div class="h-36 bg-surface relative overflow-hidden border-b border-border">
                                <img src="https://images.unsplash.com/photo-1555099962-4199c345e5dd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover opacity-30 group-hover:opacity-50 group-hover:scale-105 transition-all duration-500">
                                <div class="absolute inset-0 bg-gradient-to-t from-surface to-transparent"></div>
                                <div class="absolute bottom-3 left-4 text-[10px] font-mono text-accent bg-accent/10 px-2 py-1 rounded border border-accent/20 backdrop-blur-sm">MATÉRIA (UC)</div>
                                
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <div class="w-12 h-12 rounded-full bg-accent text-bg flex items-center justify-center shadow-[0_0_20px_rgba(45,212,191,0.4)]">
                                        <i data-lucide="play" class="w-5 h-5 ml-1"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="p-5">
                                <h3 class="text-sm font-medium text-zinc-200 group-hover:text-accent transition-colors"><?= htmlspecialchars($curso['nome']) ?></h3>
                                <p class="text-xs text-muted mt-2 line-clamp-2"><?= htmlspecialchars($curso['descricao']) ?></p>
                                
                                <div class="mt-5 pt-4 border-t border-border">
                                    <div class="flex justify-between text-[10px] font-medium text-muted mb-1.5">
                                        <span>Progresso</span>
                                        <span class="text-accent">0%</span>
                                    </div>
                                    <div class="w-full bg-bg border border-border h-2 rounded-full overflow-hidden">
                                        <div class="bg-accent h-full relative" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Botão Explorar -->
                        <div class="border border-border border-dashed bg-transparent rounded-xl flex items-center justify-center p-8 text-center hover:bg-surface/10 transition-colors cursor-pointer group">
                            <div>
                                <div class="w-12 h-12 rounded-full bg-surface border border-border flex items-center justify-center mx-auto mb-3 text-muted group-hover:text-accent group-hover:border-accent/50 transition-colors">
                                    <i data-lucide="search" class="w-5 h-5"></i>
                                </div>
                                <h3 class="text-sm font-medium text-zinc-300">Catálogo</h3>
                                <p class="text-xs text-muted mt-1">Solicite novas Trilhas.</p>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

            <!-- ================= VIEW: TRILHAS INDIVIDUAIS (GERADAS DINAMICAMENTE) ================= -->
            <?php foreach($cursos_matriculados as $curso): ?>
            <div x-show="currentTab === 'trilha_<?= $curso['id'] ?>'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                
                <button @click="currentTab = 'cursos'" class="text-xs text-muted hover:text-zinc-200 mb-6 flex items-center gap-2 transition-colors">
                    <i data-lucide="arrow-left" class="w-3 h-3"></i> Voltar para Meus Cursos
                </button>

                <!-- Hero Header for the Trail -->
                <div class="border border-border bg-surface/20 rounded-2xl p-6 md:p-10 mb-8 relative overflow-hidden flex flex-col md:flex-row gap-8 items-center md:items-start justify-between">
                    <div class="absolute -right-20 -top-20 opacity-5 pointer-events-none">
                        <i data-lucide="server" class="w-96 h-96"></i>
                    </div>
                    
                    <div class="relative z-10 w-full">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-[10px] font-mono text-accent bg-accent/10 px-2 py-1 rounded border border-accent/20">TRILHA ATIVA</span>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-medium text-zinc-100"><?= htmlspecialchars($curso['nome']) ?></h2>
                        <p class="text-sm text-zinc-400 mt-3 max-w-2xl leading-relaxed"><?= nl2br(htmlspecialchars($curso['descricao'])) ?></p>
                        
                        <div class="mt-8 flex flex-col sm:flex-row items-center gap-4">
                            <button class="w-full sm:w-auto bg-accent text-bg px-6 py-2.5 rounded-md text-sm font-medium hover:bg-accentHover transition shadow-[0_0_15px_rgba(45,212,191,0.2)] flex items-center justify-center gap-2">
                                <i data-lucide="play-circle" class="w-4 h-4"></i> Iniciar Trilha
                            </button>
                        </div>
                    </div>
                </div>

                <div class="max-w-4xl mx-auto w-full">
                    <div class="border border-border border-dashed bg-bg/20 rounded-xl p-8 text-center">
                        <i data-lucide="construction" class="w-8 h-8 text-muted mx-auto mb-3"></i>
                        <h4 class="text-sm text-zinc-300">Os módulos desta disciplina serão carregados aqui.</h4>
                        <p class="text-xs text-zinc-500 mt-1">A implementação completa da árvore de módulos (Fase 2) está em desenvolvimento.</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Dashboard Placeholder -->
            <div x-show="currentTab === 'dashboard'" style="display: none;">
                 <div class="text-center py-20 text-muted"><p>O Dashboard Geral e Fórum (Fase 3) será construído aqui.</p></div>
            </div>
            
            <div x-show="currentTab === 'arena'" style="display: none;">
                 <div class="text-center py-20 text-muted"><p>A Arena Live (Fase 5) será construída aqui.</p></div>
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
