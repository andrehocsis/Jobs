<?php
require_once 'functions.php';
$config = carregarConfig();

// --- CONFIGURAÇÃO INICIAL ---
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;

// --- FILTROS ---
$busca  = $_GET['q'] ?? '';
$f_cat  = $_GET['cat'] ?? '';
$f_mod  = $_GET['mod'] ?? '';
$f_niv  = $_GET['niv'] ?? '';
$f_loc  = $_GET['loc'] ?? '';

$todas_vagas = carregarVagas();

// --- LOGICA DE LOCAIS ---
$locais_disponiveis = ['Estados' => [], 'Cidades' => [], 'Bairros' => []];
foreach ($todas_vagas as $v) {
    if (($v['status'] ?? 'pendente') !== 'ativa') continue;
    $end = $v['endereco'] ?? [];
    if (!empty($end['uf'])) $locais_disponiveis['Estados'][$end['uf']] = $end['uf'];
    if (!empty($end['cidade'])) {
        $label = $end['cidade'] . ' - ' . ($end['uf'] ?? '');
        $locais_disponiveis['Cidades'][$label] = $end['cidade'];
    }
    if (!empty($end['bairro']) && !empty($end['cidade'])) {
        $label = $end['bairro'] . ' (' . $end['cidade'] . ')';
        $locais_disponiveis['Bairros'][$label] = $end['bairro'];
    }
}
asort($locais_disponiveis['Estados']);
asort($locais_disponiveis['Cidades']);
asort($locais_disponiveis['Bairros']);

// --- FILTRAGEM ---
$vagas_filtradas = array_filter($todas_vagas, function($v) use ($busca, $f_cat, $f_mod, $f_niv, $f_loc) {
    if (($v['status'] ?? 'pendente') !== 'ativa') return false;
    
    if ($busca) {
        $t = mb_strtolower($busca);
        if (mb_strpos(mb_strtolower($v['titulo']), $t) === false && mb_strpos(mb_strtolower($v['empresa']), $t) === false) return false;
    }
    if ($f_cat && $v['categoria'] !== $f_cat) return false;
    if ($f_mod && $v['modelo'] !== $f_mod) return false;
    if ($f_niv && $v['nivel'] !== $f_niv) return false;
    
    if ($f_loc) {
        $tLoc = mb_strtolower($f_loc);
        $end = $v['endereco'] ?? [];
        $match = false;
        if (
            (!empty($end['uf']) && mb_strtolower($end['uf']) === $tLoc) ||
            (!empty($end['cidade']) && mb_strpos(mb_strtolower($end['cidade']), $tLoc) !== false) ||
            (!empty($end['bairro']) && mb_strpos(mb_strtolower($end['bairro']), $tLoc) !== false)
        ) $match = true;
        if (!$match && !empty($v['endereco']['display']) && mb_strpos(mb_strtolower($v['endereco']['display']), $tLoc) !== false) $match = true;
        if (!$match) return false;
    }
    return true;
});

// Paginação
$paginacao = paginar($vagas_filtradas, $page, $limit);

function getCorEmpresa($nome) {
    $colors = ['bg-red-100 text-red-600', 'bg-blue-100 text-blue-600', 'bg-green-100 text-green-600', 'bg-yellow-100 text-yellow-600', 'bg-purple-100 text-purple-600', 'bg-pink-100 text-pink-600', 'bg-indigo-100 text-indigo-600'];
    return $colors[crc32($nome) % count($colors)];
}

// Link do WhatsApp para Anúncios
$zapAds = "https://wa.me/5511986052880?text=" . urlencode("Olá! Gostaria de saber mais sobre os planos de destaque no Blipei Vagas.");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blipei Vagas | Tecnologia & Marketing</title>
        <?php include 'includes/seo.php'; ?>
    <style>
        .job-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .job-card:hover { transform: translateY(-3px); border-color: #673DE6; box-shadow: 0 10px 30px -10px rgba(103, 61, 230, 0.15); }
        
        /* Animação do Loader */
        .loader-bar {
            height: 4px;
            background: #673DE6;
            width: 0%;
            transition: width 3s linear;
        }
        .modal-active .loader-bar {
            width: 100%;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen text-gray-800 bg-[#F8F9FC]">

    <?php include 'includes/header.php'; ?>

    <header class="bg-white border-b border-slate-200 py-10 px-4 relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-purple-50 rounded-full blur-3xl opacity-60"></div>
        
        <div class="container mx-auto max-w-6xl text-center relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-4">O Hub de Carreiras da <span class="text-blipei">Tecnologia</span></h1>
            <p class="text-slate-500 mb-8 max-w-2xl mx-auto text-lg">Conectando profissionais de TI, Produto e Marketing às melhores empresas.</p>

            <form class="max-w-4xl mx-auto relative shadow-xl shadow-indigo-100 rounded-2xl bg-white border border-slate-200 p-2 flex flex-col md:flex-row gap-2">
                <div class="flex items-center pl-4 text-slate-400"><i class="fas fa-search"></i></div>
                <input type="text" name="q" value="<?= htmlspecialchars($busca) ?>" class="flex-1 bg-transparent border-none focus:ring-0 px-2 py-3 text-slate-700" placeholder="Cargo, empresa ou skill...">
                <div class="hidden md:block w-px h-8 bg-slate-200 my-auto"></div>
                <div class="w-full md:w-64 relative">
                    <i class="fas fa-map-marker-alt absolute left-3 top-3.5 text-slate-400"></i>
                    <select name="loc" class="w-full pl-9 pr-8 py-3 bg-transparent border-none focus:ring-0 text-slate-700 text-sm font-medium cursor-pointer appearance-none">
                        <option value="">Qualquer lugar</option>
                        <?php if(!empty($locais_disponiveis['Estados'])): ?><optgroup label="Estados"><?php foreach($locais_disponiveis['Estados'] as $uf) echo "<option value='$uf' ".($f_loc==$uf?'selected':'').">Todo o Estado - $uf</option>"; ?></optgroup><?php endif; ?>
                        <?php if(!empty($locais_disponiveis['Cidades'])): ?><optgroup label="Cidades"><?php foreach($locais_disponiveis['Cidades'] as $lbl => $val) echo "<option value='$val' ".($f_loc==$val?'selected':'').">$lbl</option>"; ?></optgroup><?php endif; ?>
                        <?php if(!empty($locais_disponiveis['Bairros'])): ?><optgroup label="Bairros"><?php foreach($locais_disponiveis['Bairros'] as $lbl => $val) echo "<option value='$val' ".($f_loc==$val?'selected':'').">$lbl</option>"; ?></optgroup><?php endif; ?>
                    </select>
                </div>
                <button type="submit" class="w-full md:w-auto bg-blipei text-white px-8 py-3 rounded-xl font-bold hover:bg-[#5a32d1] transition">Buscar</button>
            </form>
        </div>
    </header>

    <div class="container mx-auto px-4 mt-8 max-w-7xl">
        <a href="<?= $zapAds ?>" target="_blank" class="block bg-gradient-to-r from-indigo-900 to-purple-900 rounded-xl p-1 shadow-md hover:shadow-xl transition transform hover:-translate-y-1 group">
            <div class="bg-[#36344D] border border-white/10 rounded-lg p-6 flex flex-col md:flex-row items-center justify-between gap-4 text-center md:text-left">
                <div>
                    <h3 class="text-white font-bold text-lg flex items-center justify-center md:justify-start gap-2">
                        <span class="bg-yellow-400 text-black text-xs px-2 py-0.5 rounded font-bold uppercase">Anúncio</span>
                        Divulgue seu Curso ou Treinamento aqui!
                    </h3>
                    <p class="text-indigo-200 text-sm mt-1">Alcance milhares de profissionais de tecnologia todos os dias. Destaque sua marca.</p>
                </div>
                <button class="bg-yellow-400 text-indigo-900 font-bold px-6 py-2 rounded-full whitespace-nowrap group-hover:bg-yellow-300 transition">
                    Saiba Mais
                </button>
            </div>
        </a>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-7xl flex flex-col lg:flex-row gap-8">
        
        <aside class="w-full lg:w-64 flex-shrink-0 lg:sticky lg:top-24 h-fit space-y-6">
            
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-slate-800">Filtros</h3>
                    <?php if($busca||$f_cat||$f_mod||$f_niv||$f_loc): ?><a href="index.php" class="text-xs text-red-500 font-bold hover:underline">Limpar</a><?php endif; ?>
                </div>
                <form id="filterSide" class="space-y-3">
                    <?php if($busca): ?><input type="hidden" name="q" value="<?=htmlspecialchars($busca)?>"><?php endif; ?>
                    <?php if($f_loc): ?><input type="hidden" name="loc" value="<?=htmlspecialchars($f_loc)?>"><?php endif; ?>
                    
                    <?php 
                    $filtros = [
                        'cat' => ['label' => 'Área', 'opts' => $config['categorias'], 'val' => $f_cat],
                        'mod' => ['label' => 'Modelo', 'opts' => $config['modelos'], 'val' => $f_mod],
                        'niv' => ['label' => 'Senioridade', 'opts' => $config['niveis'], 'val' => $f_niv]
                    ];
                    foreach($filtros as $name => $data): ?>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1"><?= $data['label'] ?></label>
                        <select name="<?= $name ?>" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-slate-200 rounded-lg text-sm text-slate-700 outline-none cursor-pointer">
                            <option value="">Todos</option>
                            <?php foreach($data['opts'] as $k => $v): $val = is_numeric($k)?$v:$k; ?>
                                <option value="<?= $val ?>" <?= $data['val'] == $val ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endforeach; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-indigo-100 shadow-sm overflow-hidden">
                <div class="bg-indigo-50 p-4 text-center">
                    <h4 class="text-indigo-900 font-bold text-lg">Busca Vagas?</h4>
                    <p class="text-indigo-600 text-xs mt-1">Deixe as empresas te acharem</p>
                </div>
                <div class="p-4">
                    <a href="cadastrar_curriculo.php" class="flex items-center justify-center w-full py-3 bg-[#673DE6] text-white rounded-lg text-sm font-bold hover:bg-[#5a32d1] transition shadow-sm group">
                        <i class="fas fa-file-upload mr-2"></i> Cadastrar Currículo
                    </a>
                    <p class="text-[10px] text-center text-slate-400 pt-2">
                        Totalmente gratuito e seguro.
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-yellow-100 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-slate-800 to-slate-900 p-4 text-center">
                    <h4 class="text-white font-bold text-lg">Vai contratar?</h4>
                    <p class="text-slate-300 text-xs mt-1">Publique sua vaga agora mesmo</p>
                </div>
                <div class="p-4 space-y-3">
                    <a href="anunciar.php" class="flex items-center justify-center w-full py-2.5 border border-slate-200 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition">
                        Anunciar Grátis
                    </a>
                    
                    <a href="<?= $zapAds ?>" target="_blank" class="flex items-center justify-center w-full py-2.5 bg-yellow-400 text-yellow-900 rounded-lg text-sm font-bold hover:bg-yellow-300 transition shadow-sm group">
                        <i class="fas fa-star mr-2 group-hover:rotate-12 transition"></i> Com Destaque
                    </a>
                </div>
            </div>

        </aside>

        <main class="flex-1">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-xl text-slate-800"><?= $paginacao['total'] ?> vagas encontradas</h2>
                <span class="text-xs text-slate-400">Pág <?= $page ?> de <?= max(1, $paginacao['total_paginas']) ?></span>
            </div>

            <?php if (empty($paginacao['dados'])): ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-16 text-center">
                    <i class="fas fa-search text-4xl text-slate-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-slate-800">Nada encontrado</h3>
                    <p class="text-slate-500 mb-6">Tente outros filtros ou termos.</p>
                    <a href="index.php" class="text-blipei font-bold hover:underline">Ver todas</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($paginacao['dados'] as $v): 
                        $avatarColor = getCorEmpresa($v['empresa']);
                        $iniciais = strtoupper(substr($v['empresa'], 0, 2));
                        $isNew = (strtotime($v['data_criacao']) > strtotime('-2 days'));
                        $loc = $v['endereco']['display'] ?? 'Remoto';
                    ?>
                    <a href="vaga.php?id=<?= $v['id'] ?>" onclick="abrirAnuncio(event, this.href)" class="job-card bg-white rounded-2xl p-5 border border-slate-200 block group relative h-full flex flex-col transition-all overflow-hidden cursor-pointer">
                        <?php if($isNew): ?><div class="absolute top-0 right-0 bg-green-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl z-10">NOVO</div><?php endif; ?>
                        
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-12 h-12 rounded-xl flex-shrink-0 flex items-center justify-center font-bold text-sm shadow-sm <?= $avatarColor ?>"><?= $iniciais ?></div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-slate-900 text-base leading-tight mb-1 truncate group-hover:text-blipei transition"><?= $v['titulo'] ?></h3>
                                <p class="text-xs font-medium text-slate-500 truncate"><?= $v['empresa'] ?></p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 mb-4 mt-auto">
                            <span class="px-2 py-1 rounded text-[10px] font-semibold bg-slate-50 text-slate-600 border border-slate-100"><?= $v['modelo'] ?></span>
                            <span class="px-2 py-1 rounded text-[10px] font-semibold bg-slate-50 text-slate-600 border border-slate-100"><?= $v['nivel'] ?></span>
                        </div>

                        <div class="flex items-center justify-between text-[10px] font-medium text-slate-400 pt-3 border-t border-slate-50">
                            <div class="flex items-center gap-1 truncate max-w-[70%]"><i class="fas fa-map-marker-alt text-blipei"></i> <span class="truncate"><?= $loc ?></span></div>
                            <span><?= date('d/m', strtotime($v['data_criacao'])) ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($paginacao['total_paginas'] > 1): ?>
                <div class="mt-8 flex justify-center">
                    <nav class="flex gap-1 bg-white p-1 rounded-lg border border-slate-200 shadow-sm">
                        <?php for($i=1; $i <= $paginacao['total_paginas']; $i++): ?>
                            <a href="?page=<?=$i?>&q=<?=urlencode($busca)?>&cat=<?=$f_cat?>&mod=<?=$f_mod?>&loc=<?=urlencode($f_loc)?>" 
                               class="w-8 h-8 flex items-center justify-center rounded-md font-bold text-xs transition <?= $i == $page ? 'bg-blipei text-white' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <div id="adModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/90 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div class="bg-white w-full max-w-md mx-4 rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-transform duration-300" id="adCard">
            <div class="w-full bg-gray-100 h-1"><div class="loader-bar" id="loaderBar"></div></div>
            
            <div class="p-8 text-center relative">
                
                <div class="mb-6 flex flex-col items-center justify-center">
                    <i class="fas fa-circle-notch fa-spin text-4xl text-[#673DE6] mb-3"></i>
                    <p class="text-sm text-slate-500 font-bold animate-pulse">Carregando detalhes da vaga...</p>
                </div>
                
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-xl p-6 text-white mb-2 shadow-lg border border-white/20">
                    <div class="mb-3 text-3xl">🚀</div>
                    <h3 class="font-bold text-xl mb-2">Impulsione sua Carreira!</h3>
                    <p class="text-indigo-100 text-sm mb-4">Participe do nosso grupo VIP de vagas e receba oportunidades exclusivas antes de todo mundo.</p>
                    <a href="https://jobs.blipei.com.br/grupos.php" target="_blank" class="block w-full bg-yellow-400 text-indigo-900 font-bold py-3 rounded-lg hover:bg-yellow-300 transition text-sm">
                        Quero participar agora
                    </a>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-[#36344D] text-white pt-12 pb-8 border-t-4 border-[#673DE6] mt-auto">
        <div class="container mx-auto px-4 text-center">
            <div class="flex items-center justify-center gap-2 mb-4">
                <span class="font-bold text-xl">Blipei<span class="text-[#A085F5]">Vagas</span></span>
            </div>
            <div class="flex flex-wrap justify-center gap-4 md:gap-8 text-sm text-gray-300 mb-8 font-medium">
                <a href="https://blipei.com.br/quem-somos" class="hover:text-white transition hover:underline">Quem Somos</a>
                <a href="https://blipei.com.br/politicas" class="hover:text-white transition hover:underline">Políticas de Privacidade</a>
                <a href="https://blipei.com.br/contato" class="hover:text-white transition hover:underline">Contato</a>
                <a href="<?= $zapAds ?>" target="_blank" class="text-yellow-400 hover:text-yellow-300 transition hover:underline">Anuncie Aqui</a>
            </div>
            <p class="text-xs text-gray-500">© <?= date('Y') ?> Blipei. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        let redirectUrl = '';
        let timer;

        function abrirAnuncio(e, url) {
            e.preventDefault(); // Impede o clique direto
            redirectUrl = url;
            
            const modal = document.getElementById('adModal');
            const card = document.getElementById('adCard');
            const bar = document.getElementById('loaderBar');
            
            // Reset
            bar.style.width = '0%';
            
            // Mostrar Modal
            modal.classList.remove('hidden');
            
            // Pequeno delay para a transição CSS funcionar e a barra começar
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95');
                card.classList.add('scale-100');
                modal.classList.add('modal-active'); // Inicia a animação da barra CSS
            }, 10);

            // Redireciona após 3 segundos
            timer = setTimeout(() => {
                window.location.href = redirectUrl;
            }, 3000);
        }
    </script>
</body>
</html>