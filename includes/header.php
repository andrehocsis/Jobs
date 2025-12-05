<?php
// Garante que a sessão esteja iniciada
if (session_status() === PHP_SESSION_NONE) session_start();

// Verifica se é admin
$isAdmin = isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true;

// Variáveis para destacar o menu ativo
$currentPage = basename($_SERVER['PHP_SELF']);
$tab = $_GET['tab'] ?? ''; 
?>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    body { font-family: 'DM Sans', sans-serif; }
    .text-blipei { color: #673DE6; }
    .bg-blipei { background-color: #673DE6; }
    .border-blipei { border-color: #673DE6; }
    .hover-text-blipei:hover { color: #673DE6; }
    .hover-bg-blipei:hover { background-color: #5a32d1; }
    
    #mobile-menu {
        transition: all 0.3s ease-in-out;
        transform-origin: top;
    }
    #mobile-menu.hidden {
        display: none;
    }
</style>

<nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
    <div class="container mx-auto px-4 h-20 flex items-center justify-between">
        
        <a href="index.php" class="flex items-center gap-2 group logo-group decoration-0">
            <svg width="36" height="36" viewBox="0 0 40 40" fill="none">
                <rect width="40" height="40" rx="8" fill="#673DE6" class="transition-colors duration-300"/>
                <path d="M12 20L18 26L28 14" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="font-bold text-2xl tracking-tight text-gray-900 group-hover:text-blipei transition">
                Blipei<span class="text-blipei">Vagas</span>
            </span>
        </a>

        <div class="hidden md:flex items-center gap-4 lg:gap-6 font-medium text-sm">
            <?php if ($isAdmin): ?>
                <a href="admin.php?tab=vagas" class="<?= ($currentPage=='admin.php' && ($tab=='vagas' || $tab=='')) ? 'text-blipei bg-indigo-50 font-bold' : 'text-gray-600' ?> hover:text-blipei px-3 py-2 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-briefcase"></i> Vagas
                </a>

                <a href="admin.php?tab=candidatos" class="<?= ($currentPage=='admin.php' && $tab=='candidatos') ? 'text-blipei bg-indigo-50 font-bold' : 'text-gray-600' ?> hover:text-blipei px-3 py-2 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-users"></i> Currículos
                </a>
                
                <a href="admin.php?tab=comentarios" class="<?= ($currentPage=='admin.php' && $tab=='comentarios') ? 'text-blipei bg-indigo-50 font-bold' : 'text-gray-600' ?> hover:text-blipei px-3 py-2 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-comments"></i> <span class="hidden lg:inline">Comentários</span>
                </a>
                
                <a href="admin.php?tab=config" class="<?= ($currentPage=='admin.php' && $tab=='config') ? 'text-blipei bg-indigo-50 font-bold' : 'text-gray-600' ?> hover:text-blipei px-3 py-2 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-sliders-h"></i> <span class="hidden lg:inline">Config</span>
                </a>
                
                <div class="h-6 w-px bg-gray-200 mx-1"></div>
                
                <a href="index.php" target="_blank" class="text-gray-500 hover:text-blipei transition" title="Ver site como visitante">
                    <i class="fas fa-external-link-alt"></i>
                </a>
                
                <a href="admin.php?sair=1" class="text-red-500 hover:text-red-700 font-bold ml-1 flex items-center gap-1">
                    <i class="fas fa-sign-out-alt"></i>
                </a>

            <?php else: ?>
                <a href="cadastrar_curriculo.php" class="<?= $currentPage == 'cadastrar_curriculo.php' ? 'text-blipei font-bold' : 'text-gray-600' ?> hover:text-blipei transition">
                    Sou Candidato
                </a>

                <a href="https://blipei.com.br/" target="_blank" class="text-gray-600 hover:text-blipei transition">
                    Gestão de Projetos
                </a>
                
                <a href="https://blog.blipei.com.br/" target="_blank" class="text-gray-600 hover:text-blipei transition">
                    Blog
                </a>
                
                <a href="anunciar.php" class="bg-blipei hover-bg-blipei text-white px-6 py-2.5 rounded-full font-bold transition shadow-lg shadow-indigo-200 flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i> Anunciar Vaga
                </a>
            <?php endif; ?>
        </div>

        <button id="btn-mobile" class="md:hidden text-gray-600 text-2xl focus:outline-none p-2 rounded hover:bg-gray-100">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-100 px-4 pb-6 shadow-xl">
        <div class="flex flex-col gap-4 mt-4 font-medium text-gray-700">
            <?php if ($isAdmin): ?>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Administração</span>
                <a href="admin.php?tab=vagas" class="flex items-center gap-2 py-2 border-b border-gray-50"><i class="fas fa-briefcase w-5 text-blipei"></i> Gerenciar Vagas</a>
                <a href="admin.php?tab=candidatos" class="flex items-center gap-2 py-2 border-b border-gray-50"><i class="fas fa-users w-5 text-blipei"></i> Banco de Talentos</a>
                <a href="admin.php?tab=comentarios" class="flex items-center gap-2 py-2 border-b border-gray-50"><i class="fas fa-comments w-5 text-blipei"></i> Comentários</a>
                <a href="admin.php?tab=config" class="flex items-center gap-2 py-2 border-b border-gray-50"><i class="fas fa-sliders-h w-5 text-blipei"></i> Configurações</a>
                <a href="admin.php?sair=1" class="text-red-500 font-bold py-2 mt-2">Sair do Painel</a>
            <?php else: ?>
                <a href="cadastrar_curriculo.php" class="flex items-center gap-2 py-2 border-b border-gray-50 text-blipei font-bold">Sou Candidato</a>
                <a href="https://blipei.com.br/" class="flex items-center gap-2 py-2 border-b border-gray-50">SaaS Blipei</a>
                <a href="https://blog.blipei.com.br/" class="flex items-center gap-2 py-2 border-b border-gray-50">Blog</a>
                <a href="anunciar.php" class="bg-blipei text-white text-center py-3 rounded-lg font-bold mt-2 shadow-md">Anunciar Vaga</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnMobile = document.getElementById('btn-mobile');
        const mobileMenu = document.getElementById('mobile-menu');

        if (btnMobile && mobileMenu) {
            btnMobile.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
    });
</script>