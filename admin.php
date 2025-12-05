<?php
require_once 'functions.php';

// --- AUTENTICAÇÃO ---
if (isset($_POST['senha'])) {
    if ($_POST['senha'] === ADMIN_PASS) {
        $_SESSION['admin_logado'] = true;
        header("Location: admin.php"); exit;
    } else { $erro = "Senha incorreta!"; }
}
if (isset($_GET['sair'])) { session_destroy(); header("Location: admin.php"); exit; }

if (!isset($_SESSION['admin_logado'])) {
    echo '<script src="https://cdn.tailwindcss.com"></script>';
    echo '<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet"><style>body{font-family:"DM Sans",sans-serif}</style>';
    echo '<body class="bg-[#F8F9FC] h-screen flex items-center justify-center flex-col gap-6">';
    echo '<div class="text-center"><h1 class="text-3xl font-bold text-[#36344D] mb-2">Blipei Vagas</h1><p class="text-gray-400">Área Administrativa</p></div>';
    echo '<form method="POST" class="bg-white p-8 rounded-2xl shadow-xl w-80 border border-gray-100">';
    if(isset($erro)) echo '<p class="text-red-500 text-sm mb-4 text-center">'.$erro.'</p>';
    echo '<input type="password" name="senha" class="w-full border border-gray-200 bg-gray-50 p-3 rounded-xl mb-4 focus:outline-none focus:border-[#673DE6] transition" placeholder="Senha" autofocus>';
    echo '<button class="w-full bg-[#673DE6] text-white py-3 rounded-xl font-bold hover:bg-[#5a32d1] transition shadow-lg shadow-indigo-200">Entrar</button>';
    echo '</form></body>';
    exit;
}

// =============================================================================
// LÓGICA DE EXPORTAÇÃO (Deve vir antes de qualquer HTML)
// =============================================================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $candidatos = carregarCandidatos();
    $config = carregarConfig();
    
    // Aplica os mesmos filtros da tela
    $f_area = $_GET['f_area'] ?? '';
    $f_regiao = $_GET['f_regiao'] ?? '';
    $f_salario = $_GET['f_salario'] ?? '';

    $dados_filtrados = array_filter($candidatos, function($c) use ($f_area, $f_regiao, $f_salario) {
        if ($f_area && !in_array($f_area, $c['areas'] ?? [])) return false;
        
        if ($f_regiao) {
            $local = ($c['endereco']['cidade'] ?? '') . ' ' . ($c['endereco']['uf'] ?? '');
            if (mb_strpos(mb_strtolower($local), mb_strtolower($f_regiao)) === false) return false;
        }
        
        if ($f_salario) {
            if (mb_strpos(mb_strtolower($c['salario_pretensao']), mb_strtolower($f_salario)) === false) return false;
        }
        return true;
    });

    // Gera CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=candidatos_blipei_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    
    // BOM para Excel reconhecer acentos
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Cabeçalho
    fputcsv($output, ['Data', 'Nome', 'Email', 'Telefone', 'LinkedIn', 'Salário', 'Áreas', 'Localização', 'Link Currículo']);

    foreach ($dados_filtrados as $c) {
        $areas_texto = implode(', ', $c['areas']); // Ex: dev_back, dev_front
        // Tenta traduzir as slugs das áreas
        $areas_legiveis = [];
        foreach($c['areas'] as $a) $areas_legiveis[] = $config['categorias'][$a] ?? $a;
        
        $local = !empty($c['endereco']) ? "{$c['endereco']['cidade']}-{$c['endereco']['uf']}" : "Não informado";
        $link_cv = getUrlCurriculo($c['arquivo_cv']);

        fputcsv($output, [
            date('d/m/Y H:i', strtotime($c['data_cadastro'])),
            $c['nome'],
            $c['email'],
            $c['telefone'],
            $c['linkedin'],
            $c['salario_pretensao'],
            implode(', ', $areas_legiveis),
            $local,
            $link_cv
        ]);
    }
    fclose($output);
    exit;
}

// --- PROCESSAMENTO DE AÇÕES ---

// Vagas
if (isset($_GET['acao_vaga'])) {
    $id = $_GET['id'];
    $act = $_GET['acao_vaga'];
    if ($act == 'aprovar') atualizarStatusVaga($id, 'ativa');
    if ($act == 'pausar') atualizarStatusVaga($id, 'pendente');
    if ($act == 'excluir') excluirVaga($id);
    header("Location: admin.php?tab=vagas"); exit;
}

// Configurações e Comentários (Mantidos igual ao anterior)
if (isset($_GET['acao_com'])) {
    $vid = $_GET['vid']; $cid = $_GET['cid']; $act = $_GET['acao_com'];
    moderarComentario($vid, $cid, $act);
    header("Location: admin.php?tab=comentarios"); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['config_action'])) {
    // ... (Lógica de config mantida igual ao código anterior) ...
    $config = carregarConfig();
    $action = $_POST['config_action'];
    $type   = $_POST['type'];
    if ($action === 'add') {
        $val = strip_tags($_POST['value']);
        if ($type === 'categorias') { $key = strip_tags($_POST['key']); if($key && $val) $config['categorias'][$key] = $val; } 
        else { if($val && !in_array($val, $config[$type])) $config[$type][] = $val; }
    }
    if ($action === 'edit') {
        $oldVal = $_POST['old_value']; $newVal = strip_tags($_POST['new_value']); $key = $_POST['key'] ?? null;
        if ($type === 'categorias' && isset($config['categorias'][$key])) { $config['categorias'][$key] = $newVal; } 
        else { $index = array_search($oldVal, $config[$type]); if ($index !== false && $newVal) $config[$type][$index] = $newVal; }
    }
    if ($action === 'delete') {
        if ($type === 'categorias') { $key = $_POST['key']; unset($config['categorias'][$key]); } 
        else { $val = $_POST['value']; $key = array_search($val, $config[$type]); if ($key !== false) { unset($config[$type][$key]); $config[$type] = array_values($config[$type]); } }
    }
    salvarConfig($config);
    header("Location: admin.php?tab=config"); exit;
}

// --- CARREGAMENTO DE DADOS ---
$vagas = carregarVagas();
$config = carregarConfig();
$tab = $_GET['tab'] ?? 'vagas';

// Filtros Vagas
$pendentes = array_filter($vagas, fn($v) => ($v['status']??'pendente') === 'pendente');
$ativas = array_filter($vagas, fn($v) => ($v['status']??'') === 'ativa');

// Comentários
$comentariosPendentes = [];
foreach ($vagas as $v) {
    if (isset($v['comentarios'])) {
        foreach ($v['comentarios'] as $c) {
            if (($c['status'] ?? 'pendente') === 'pendente') {
                $c['vaga_titulo'] = $v['titulo']; $c['vaga_id'] = $v['id'];
                $comentariosPendentes[] = $c;
            }
        }
    }
}

// --- LÓGICA DE CANDIDATOS (NOVO) ---
$candidatos = carregarCandidatos();
$totalCandidatos = count($candidatos);

// Paginação Candidatos
$candPage = isset($_GET['cp']) ? max(1, (int)$_GET['cp']) : 1;
$candLimit = 10;

// Filtros Candidatos
$f_area = $_GET['f_area'] ?? '';
$f_regiao = $_GET['f_regiao'] ?? '';
$f_salario = $_GET['f_salario'] ?? '';

$candidatos_filtrados = array_filter($candidatos, function($c) use ($f_area, $f_regiao, $f_salario) {
    // Filtro Area
    if ($f_area && !in_array($f_area, $c['areas'] ?? [])) return false;
    
    // Filtro Região (Texto parcial)
    if ($f_regiao) {
        $local = ($c['endereco']['cidade'] ?? '') . ' ' . ($c['endereco']['uf'] ?? '');
        if (mb_strpos(mb_strtolower($local), mb_strtolower($f_regiao)) === false) return false;
    }
    
    // Filtro Salário (Texto parcial)
    if ($f_salario) {
        if (mb_strpos(mb_strtolower($c['salario_pretensao']), mb_strtolower($f_salario)) === false) return false;
    }
    
    return true;
});

$totalFiltrados = count($candidatos_filtrados);
$totalPaginasCand = ceil($totalFiltrados / $candLimit);
$offsetCand = ($candPage - 1) * $candLimit;
$candidatos_paginados = array_slice($candidatos_filtrados, $offsetCand, $candLimit);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin | Blipei</title>
</head>
<body class="bg-[#F8F9FC] text-gray-800 flex flex-col min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-8 max-w-7xl">
        
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">
                <?php 
                    if ($tab === 'vagas') echo 'Gestão de Vagas';
                    elseif ($tab === 'candidatos') echo 'Banco de Talentos';
                    elseif ($tab === 'comentarios') echo 'Moderação';
                    else echo 'Configurações';
                ?>
            </h2>
            <div class="flex gap-4">
                <div class="bg-white px-4 py-2 rounded-lg border border-gray-200 shadow-sm text-sm">
                    <span class="text-gray-400">Total Vagas:</span> <strong><?= count($vagas) ?></strong>
                </div>
                <div class="bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100 shadow-sm text-sm text-indigo-700">
                    <span class="text-indigo-400">Currículos:</span> <strong><?= $totalCandidatos ?></strong>
                </div>
            </div>
        </div>

    
        <?php if ($tab === 'vagas'): ?>
            <div class="flex justify-end mb-4">
                <a href="anunciar.php" target="_blank" class="text-sm bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-black transition"><i class="fas fa-plus mr-1"></i> Nova Vaga</a>
            </div>
            <?php if(!empty($pendentes)): ?>
            <div class="mb-10">
                <h3 class="text-lg font-bold text-yellow-600 mb-4 flex items-center gap-2">
                    <span class="relative flex h-3 w-3"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span></span> Aguardando Aprovação
                </h3>
                <div class="grid gap-4">
                    <?php foreach($pendentes as $v): ?>
                    <div class="bg-white border-l-4 border-yellow-500 rounded-xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start gap-4">
                        <div class="flex-1">
                            <h4 class="font-bold text-xl text-gray-900 flex items-center gap-2"><?= $v['titulo'] ?> <a href="vaga.php?id=<?= $v['id'] ?>" target="_blank" class="text-gray-400 hover:text-blipei text-sm"><i class="fas fa-external-link-alt"></i></a></h4>
                            <p class="text-gray-500 text-sm mt-1"><span class="font-semibold text-gray-700"><?= $v['empresa'] ?></span> • <?= $config['categorias'][$v['categoria']] ?? $v['categoria'] ?></p>
                        </div>
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <a href="?acao_vaga=gerar_link&id=<?= $v['id'] ?>" class="text-purple-600 bg-purple-50 px-3 py-2.5 rounded-lg border border-purple-200 font-bold hover:bg-purple-100" title="Gerar Link Público"><i class="fas fa-link"></i></a>
                            <a href="admin_editar.php?id=<?= $v['id'] ?>" class="bg-blue-100 text-blue-600 px-3 py-2.5 rounded-lg font-bold hover:bg-blue-200 transition" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                            <a href="?acao_vaga=aprovar&id=<?= $v['id'] ?>" class="bg-green-600 text-white px-5 py-2.5 rounded-lg font-bold hover:bg-green-700 transition">Aprovar</a>
                            <a href="?acao_vaga=excluir&id=<?= $v['id'] ?>" onclick="return confirm('Excluir definitivamente?')" class="bg-red-50 text-red-500 px-4 py-2.5 rounded-lg font-bold hover:bg-red-100"><i class="fas fa-trash"></i></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 text-xs uppercase tracking-wider">
                            <tr><th class="p-5 font-semibold">Vaga</th><th class="p-5 font-semibold">Categoria</th><th class="p-5 font-semibold text-center">Status</th><th class="p-5 font-semibold text-right">Ações</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php foreach($vagas as $v): if(($v['status']??'') == 'pendente') continue; ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-5"><div class="font-bold text-gray-900 text-base"><a href="vaga.php?id=<?=$v['id']?>" target="_blank" class="hover:text-blipei"><?= $v['titulo'] ?></a></div><div class="text-gray-500 mt-0.5"><?= $v['empresa'] ?></div></td>
                                <td class="p-5 text-gray-600"><?= $config['categorias'][$v['categoria']] ?? $v['categoria'] ?></td>
                                <td class="p-5 text-center"><span class="px-3 py-1 rounded-full text-xs font-bold <?= $v['status']=='ativa'?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500' ?>"><?= strtoupper($v['status']) ?></span></td>
                                <td class="p-5 text-right space-x-2">
                                    <a href="?acao_vaga=gerar_link&id=<?= $v['id'] ?>" class="text-purple-500 hover:text-purple-700 font-medium mr-2" title="Link"><i class="fas fa-link"></i></a>
                                    <a href="admin_editar.php?id=<?= $v['id'] ?>" class="text-blue-500 hover:text-blue-700 font-medium mr-2" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                                    <?php if($v['status']=='ativa'): ?>
                                        <a href="?acao_vaga=pausar&id=<?= $v['id'] ?>" class="text-yellow-600 hover:text-yellow-700 font-medium text-xs border border-yellow-200 px-2 py-1 rounded bg-yellow-50">Pausar</a>
                                    <?php else: ?>
                                        <a href="?acao_vaga=aprovar&id=<?= $v['id'] ?>" class="text-green-600 hover:text-green-700 font-medium text-xs border border-green-200 px-2 py-1 rounded bg-green-50">Reativar</a>
                                    <?php endif; ?>
                                    <a href="?acao_vaga=excluir&id=<?= $v['id'] ?>" onclick="return confirm('Excluir?')" class="text-red-500 hover:text-red-700 ml-2"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($tab === 'candidatos'): ?>
            
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6">
                <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                    <input type="hidden" name="tab" value="candidatos">
                    
                    <div class="flex-1 w-full">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Região</label>
                        <input type="text" name="f_regiao" value="<?= htmlspecialchars($f_regiao) ?>" placeholder="Ex: São Paulo" class="w-full border rounded-lg p-2 text-sm">
                    </div>
                    
                    <div class="flex-1 w-full">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Área</label>
                        <select name="f_area" class="w-full border rounded-lg p-2 text-sm bg-white">
                            <option value="">Todas</option>
                            <?php foreach($config['categorias'] as $k => $v): ?>
                                <option value="<?=$k?>" <?= $f_area == $k ? 'selected' : '' ?>><?=$v?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex-1 w-full">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Salário (Busca)</label>
                        <input type="text" name="f_salario" value="<?= htmlspecialchars($f_salario) ?>" placeholder="Ex: 5000" class="w-full border rounded-lg p-2 text-sm">
                    </div>

                    <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-black transition h-10">Filtrar</button>
                    
                    <a href="?export=csv&f_regiao=<?=urlencode($f_regiao)?>&f_area=<?=urlencode($f_area)?>&f_salario=<?=urlencode($f_salario)?>" target="_blank" class="bg-green-600 text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-green-700 transition h-10 flex items-center gap-2">
                        <i class="fas fa-file-csv"></i> Exportar CSV
                    </a>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="p-4 font-semibold">Candidato</th>
                                <th class="p-4 font-semibold">Áreas</th>
                                <th class="p-4 font-semibold">Pretensão</th>
                                <th class="p-4 font-semibold">Local</th>
                                <th class="p-4 font-semibold text-right">Currículo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if(empty($candidatos_paginados)): ?>
                                <tr><td colspan="5" class="p-8 text-center text-gray-500">Nenhum candidato encontrado com estes filtros.</td></tr>
                            <?php else: foreach($candidatos_paginados as $c): 
                                $local = !empty($c['endereco']) ? $c['endereco']['cidade'].'-'.$c['endereco']['uf'] : 'Não informado';
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4">
                                    <div class="font-bold text-gray-900"><?= $c['nome'] ?></div>
                                    <div class="text-gray-500 text-xs mt-0.5">
                                        <?= $c['email'] ?> • <?= $c['telefone'] ?>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1"><?= date('d/m/Y H:i', strtotime($c['data_cadastro'])) ?></div>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach($c['areas'] as $a): ?>
                                            <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-[10px] border border-indigo-100">
                                                <?= $config['categorias'][$a] ?? $a ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="p-4 text-gray-600 font-medium"><?= $c['salario_pretensao'] ?></td>
                                <td class="p-4 text-gray-600"><?= $local ?></td>
                                <td class="p-4 text-right">
                                    <?php if($c['linkedin']): ?>
                                        <a href="<?= $c['linkedin'] ?>" target="_blank" class="text-blue-600 hover:text-blue-800 mr-2 text-lg" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
                                    <?php endif; ?>
                                    <a href="<?= UPLOADS_DIR . $c['arquivo_cv'] ?>" target="_blank" class="inline-block bg-[#673DE6] text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-[#5a32d1] transition shadow-sm">
                                        <i class="fas fa-download mr-1"></i> Baixar CV
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($totalPaginasCand > 1): ?>
            <div class="mt-6 flex justify-center">
                <nav class="flex gap-1 bg-white p-1 rounded-lg border border-gray-200 shadow-sm">
                    <?php for($i=1; $i <= $totalPaginasCand; $i++): ?>
                        <a href="?tab=candidatos&cp=<?=$i?>&f_area=<?=urlencode($f_area)?>&f_regiao=<?=urlencode($f_regiao)?>&f_salario=<?=urlencode($f_salario)?>" 
                           class="w-8 h-8 flex items-center justify-center rounded-md font-bold text-xs transition <?= $i == $candPage ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            </div>
            <?php endif; ?>

        <?php elseif ($tab === 'comentarios'): ?>
            <?php if(empty($comentariosPendentes)): ?>
                <div class="bg-white p-12 rounded-2xl border border-gray-200 text-center shadow-sm">
                    <div class="w-16 h-16 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fas fa-check"></i></div>
                    <h3 class="text-lg font-bold text-gray-700">Tudo limpo!</h3>
                    <p class="text-gray-500">Nenhum comentário pendente.</p>
                </div>
            <?php else: ?>
                <div class="grid gap-6">
                    <?php foreach($comentariosPendentes as $c): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 relative overflow-hidden">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-red-500"></div>
                        <div class="flex justify-between items-start mb-4">
                            <div><h4 class="font-bold text-gray-900"><?= htmlspecialchars($c['nome']) ?></h4><p class="text-xs text-gray-500">Na vaga: <a href="vaga.php?id=<?=$c['vaga_id']?>" target="_blank" class="text-blipei hover:underline font-bold"><?= $c['vaga_titulo'] ?></a></p></div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg text-gray-700 text-sm mb-6 border border-gray-100 italic">"<?= nl2br(htmlspecialchars($c['texto'])) ?>"</div>
                        <div class="flex gap-3">
                            <a href="?acao_com=aprovar&vid=<?= $c['vaga_id'] ?>&cid=<?= $c['id'] ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-700">Aprovar</a>
                            <a href="?acao_com=excluir&vid=<?= $c['vaga_id'] ?>&cid=<?= $c['id'] ?>" class="bg-red-50 text-red-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-100">Excluir</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($tab === 'config'): ?>
            <div class="grid lg:grid-cols-2 gap-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col h-[600px]">
                    <h3 class="font-bold text-lg text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-layer-group text-[#673DE6]"></i> Áreas</h3>
                    <div class="flex-1 overflow-y-auto space-y-2 pr-2 mb-4">
                        <?php foreach($config['categorias'] as $k => $v): ?>
                        <div class="flex gap-2 items-center group bg-gray-50 p-2 rounded-lg border border-transparent hover:border-gray-200">
                            <div class="px-2 py-1 bg-gray-200 text-gray-500 text-[10px] rounded font-mono w-20 truncate"><?=$k?></div>
                            <form method="POST" class="flex-1 flex gap-2"><input type="hidden" name="config_action" value="edit"><input type="hidden" name="type" value="categorias"><input type="hidden" name="key" value="<?=$k?>"><input type="text" name="new_value" value="<?=htmlspecialchars($v)?>" class="w-full bg-transparent border-b border-transparent hover:border-gray-300 focus:border-[#673DE6] outline-none text-sm font-medium"></form>
                            <form method="POST" onsubmit="return confirm('Excluir?')"><input type="hidden" name="config_action" value="delete"><input type="hidden" name="type" value="categorias"><input type="hidden" name="key" value="<?=$k?>"><button class="text-gray-300 hover:text-red-500 px-2"><i class="fas fa-trash"></i></button></form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <form method="POST" class="border-t pt-4 bg-gray-50 -mx-6 -mb-6 p-6 rounded-b-2xl">
                        <input type="hidden" name="config_action" value="add"><input type="hidden" name="type" value="categorias">
                        <div class="grid grid-cols-2 gap-3"><input type="text" name="key" placeholder="Slug (ex: ia)" required class="border rounded-lg p-2 text-sm"><input type="text" name="value" placeholder="Nome (ex: AI & ML)" required class="border rounded-lg p-2 text-sm"></div>
                        <button class="w-full bg-gray-900 text-white mt-3 py-2 rounded-lg text-sm font-bold hover:bg-black">Adicionar</button>
                    </form>
                </div>
                <div class="space-y-8">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                        <h3 class="font-bold text-lg text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-laptop-house text-[#673DE6]"></i> Modelos</h3>
                        <ul class="space-y-2 mb-4">
                            <?php foreach($config['modelos'] as $m): ?>
                            <li class="flex justify-between items-center group bg-gray-50 px-3 py-2 rounded-lg hover:bg-white border border-transparent hover:border-gray-200">
                                <form method="POST" class="flex-1 mr-2"><input type="hidden" name="config_action" value="edit"><input type="hidden" name="type" value="modelos"><input type="hidden" name="old_value" value="<?=htmlspecialchars($m)?>"><input type="text" name="new_value" value="<?=htmlspecialchars($m)?>" class="bg-transparent w-full text-sm outline-none font-medium"></form>
                                <form method="POST" onsubmit="return confirm('Remover?')"><input type="hidden" name="config_action" value="delete"><input type="hidden" name="type" value="modelos"><input type="hidden" name="value" value="<?=htmlspecialchars($m)?>"><button class="text-gray-300 hover:text-red-500 text-sm"><i class="fas fa-times"></i></button></form>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <form method="POST" class="flex gap-2"><input type="hidden" name="config_action" value="add"><input type="hidden" name="type" value="modelos"><input type="text" name="value" placeholder="Novo modelo..." required class="flex-1 border rounded-lg p-2 text-sm"><button class="bg-gray-900 text-white px-4 rounded-lg text-sm font-bold"><i class="fas fa-plus"></i></button></form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <footer class="bg-[#36344D] text-white pt-12 pb-8 border-t-4 border-[#673DE6] mt-auto">
        <div class="container mx-auto px-4 text-center"><p class="text-xs text-gray-500">© <?= date('Y') ?> Blipei Admin.</p></div>
    </footer>
</body>
</html>