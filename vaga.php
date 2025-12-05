<?php
require_once 'functions.php';

// 1. CARREGAMENTO E SEGURANÇA
$id = $_GET['id'] ?? '';
$vaga = carregarVagaPorId($id);
$isAdmin = isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'];

if (!$vaga || (($vaga['status'] ?? '') !== 'ativa' && !$isAdmin)) {
    header("Location: index.php"); exit;
}

// 2. PROCESSAR COMENTÁRIOS
$msg_comentario = '';
$msg_tipo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'])) {
    $nome = trim($_POST['nome']) ?: 'Anônimo';
    $texto = trim($_POST['comentario']);
    if (!empty($texto)) {
        if (adicionarComentario($id, $nome, $texto)) {
            $msg_comentario = 'Comentário enviado para aprovação!';
            $msg_tipo = 'success';
        } else {
            $msg_comentario = 'Erro ao enviar comentário.';
            $msg_tipo = 'error';
        }
    }
}
$comentariosAprovados = array_filter($vaga['comentarios'] ?? [], fn($c) => ($c['status']??'') === 'aprovado');

// 3. LÓGICA DE VAGAS RELACIONADAS
$todasVagas = carregarVagas();
$relacionadas = array_filter($todasVagas, function($v) use ($vaga) {
    // Exclui a própria vaga e vagas não ativas
    if ($v['id'] === $vaga['id'] || ($v['status'] ?? '') !== 'ativa') return false;
    
    // Critério: Mesma Categoria OU Mesma Cidade
    $mesmaCategoria = $v['categoria'] === $vaga['categoria'];
    $mesmaCidade = !empty($v['endereco']['cidade']) && 
                   !empty($vaga['endereco']['cidade']) && 
                   $v['endereco']['cidade'] === $vaga['endereco']['cidade'];
                   
    return $mesmaCategoria || $mesmaCidade;
});
// Pega as 3 primeiras
$relacionadas = array_slice($relacionadas, 0, 3);

// 4. CONFIGURAÇÃO VISUAL DO BOTÃO DE APLICAÇÃO
$linkDestino = gerarLinkAplicacao($vaga);
$linkValido = ($linkDestino !== '#');
$tipoContato = $vaga['tipo_contato'] ?? 'link';

// Define estilo baseado no tipo
$btnConfig = match($tipoContato) {
    'whatsapp' => [
        'texto' => 'Candidatar via WhatsApp',
        'icon'  => 'fab fa-whatsapp',
        'cor'   => 'bg-green-600 hover:bg-green-700',
        'label' => 'WhatsApp:'
    ],
    'email' => [
        'texto' => 'Candidatar via E-mail',
        'icon'  => 'fas fa-envelope',
        'cor'   => 'bg-slate-700 hover:bg-slate-800',
        'label' => 'E-mail:'
    ],
    default => [ // Link
        'texto' => 'Candidatar no Site',
        'icon'  => 'fas fa-external-link-alt',
        'cor'   => 'bg-[#673DE6] hover:bg-[#5a32d1]',
        'label' => 'Link:'
    ]
};

// Dados estruturados para Google Jobs (Mantido da versão anterior)
$schema = [
    "@context" => "https://schema.org/",
    "@type" => "JobPosting",
    "title" => $vaga['titulo'],
    "description" => nl2br(strip_tags($vaga['descricao'])),
    "identifier" => ["@type" => "PropertyValue", "name" => $vaga['empresa'], "value" => $vaga['id']],
    "datePosted" => date('Y-m-d', strtotime($vaga['data_criacao'])),
    "hiringOrganization" => ["@type" => "Organization", "name" => $vaga['empresa']],
    "jobLocation" => ["@type" => "Place", "address" => ["@type" => "PostalAddress", "addressLocality" => $vaga['endereco']['cidade']??'Brasil', "addressRegion" => $vaga['endereco']['uf']??'']]
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($vaga['titulo']) ?> | Blipei Vagas</title>
    
    <script type="application/ld+json"><?= json_encode($schema) ?></script>
    <style>.sticky-card { position: -webkit-sticky; position: sticky; top: 100px; }</style>
</head>
<body class="bg-[#F8F9FC] text-gray-700 flex flex-col min-h-screen">

    <?php include 'includes/header.php'; ?>

    <div class="bg-white border-b border-gray-100">
        <div class="container mx-auto px-4 py-3 text-xs text-gray-500 font-medium flex items-center gap-2">
            <a href="index.php" class="hover:text-[#673DE6]">Home</a> <i class="fas fa-chevron-right text-[10px]"></i>
            <a href="index.php?cat=<?= urlencode($vaga['categoria']) ?>" class="hover:text-[#673DE6] capitalize"><?= str_replace('_', ' ', $vaga['categoria']) ?></a> <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-gray-800 truncate max-w-[200px]"><?= $vaga['titulo'] ?></span>
        </div>
    </div>

    <main class="container mx-auto px-4 py-8 max-w-6xl flex-grow">
        
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2 leading-tight"><?= htmlspecialchars($vaga['titulo']) ?></h1>
            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                <span class="font-semibold text-[#673DE6]"><i class="fas fa-building"></i> <?= htmlspecialchars($vaga['empresa']) ?></span>
                <span><i class="fas fa-map-marker-alt"></i> <?= !empty($vaga['endereco']['cidade']) ? htmlspecialchars($vaga['endereco']['cidade'] . ' - ' . $vaga['endereco']['uf']) : htmlspecialchars($vaga['endereco']['display'] ?? 'Local não informado') ?></span>
                <span><i class="far fa-clock"></i> <?= date('d/m/Y', strtotime($vaga['data_criacao'])) ?></span>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-8 relative">
            
            <div class="md:col-span-2 space-y-8">
                <div class="flex flex-wrap gap-3">
                    <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-lg text-sm font-semibold border border-blue-100"><?= $vaga['modelo'] ?></span>
                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold border border-gray-200"><?= $vaga['nivel'] ?></span>
                    <span class="px-3 py-1 bg-green-50 text-green-700 rounded-lg text-sm font-semibold border border-green-100"><?= $vaga['regime'] ?></span>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 pb-2 border-b border-gray-100">Descrição da Vaga</h2>
                    <div class="prose prose-indigo max-w-none text-gray-600 leading-relaxed space-y-4">
                        <?= nl2br(strip_tags($vaga['descricao'])) ?>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100" id="comentarios">
                    <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2"><i class="far fa-comments text-[#673DE6]"></i> Dúvidas e Comentários</h3>
                    <div class="space-y-6 mb-8">
                        <?php if (empty($comentariosAprovados)): ?>
                            <p class="text-gray-400 text-sm italic">Nenhum comentário. Tem alguma dúvida?</p>
                        <?php else: foreach($comentariosAprovados as $c): ?>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex justify-between mb-1"><strong class="text-sm text-gray-900"><?= htmlspecialchars($c['nome']) ?></strong><span class="text-xs text-gray-400"><?= date('d/m', strtotime($c['data'])) ?></span></div>
                                <p class="text-gray-700 text-sm"><?= nl2br(htmlspecialchars($c['texto'])) ?></p>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    <form method="POST" action="#comentarios" class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                        <?php if($msg_comentario): ?><div class="mb-4 p-3 rounded-lg text-sm text-center font-medium <?= $msg_tipo == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= $msg_comentario ?></div><?php endif; ?>
                        <div class="grid gap-3">
                            <input type="text" name="nome" class="w-full border rounded-lg p-3 text-sm bg-white" placeholder="Seu nome (Opcional)">
                            <textarea name="comentario" required rows="3" class="w-full border rounded-lg p-3 text-sm bg-white" placeholder="Escreva sua dúvida..."></textarea>
                            <button class="bg-gray-900 text-white py-2 px-6 rounded-lg text-sm font-bold hover:bg-black transition self-end">Enviar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="md:col-span-1">
                <div class="sticky-card space-y-6">
                    
                    <div class="bg-white p-6 rounded-2xl shadow-lg border border-indigo-50 text-center">
                        <p class="text-xs text-gray-400 uppercase font-bold mb-2">Remuneração</p>
                        <div class="text-xl font-bold text-green-600 bg-green-50 inline-block px-4 py-1 rounded-full border border-green-100 mb-6">
                            <?= $vaga['salario'] ?: 'A combinar' ?>
                        </div>

                        <?php if ($linkValido): ?>
                            <a href="<?= $linkDestino ?>" target="_blank" class="block w-full <?= $btnConfig['cor'] ?> text-white font-bold py-4 rounded-xl transition shadow-lg shadow-indigo-100 mb-4 flex items-center justify-center gap-2 group">
                                <i class="<?= $btnConfig['icon'] ?>"></i> <?= $btnConfig['texto'] ?>
                            </a>
                            
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 mt-4 text-left">
                                <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Se o botão falhar:</p>
                                <div class="flex items-center gap-2 bg-white p-2 rounded border border-gray-200">
                                    <i class="fas fa-copy text-gray-400 text-xs"></i>
                                    <input type="text" value="<?= htmlspecialchars($vaga['contato_valor']) ?>" readonly class="w-full text-xs text-gray-600 bg-transparent outline-none truncate" onclick="this.select();">
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1 text-center">Copie e cole no navegador ou app.</p>
                            </div>

                        <?php else: ?>
                            <button disabled class="block w-full bg-gray-300 text-gray-500 font-bold py-4 rounded-xl cursor-not-allowed mb-2">Indisponível</button>
                            <p class="text-xs text-red-400">Contato inválido ou expirado.</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <h4 class="font-bold text-gray-900 mb-4 border-b pb-2 text-sm">Localização da Empresa</h4>
                        <?php if(!empty($vaga['endereco']['cidade'])): ?>
                            <div class="text-sm text-gray-600 space-y-2">
                                <p><span class="font-bold text-gray-900">Cidade:</span> <?= $vaga['endereco']['cidade'] ?> - <?= $vaga['endereco']['uf'] ?></p>
                                <?php if(!empty($vaga['endereco']['bairro'])): ?>
                                    <p><span class="font-bold text-gray-900">Bairro:</span> <?= $vaga['endereco']['bairro'] ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if(!empty($vaga['endereco']['logradouro'])): ?>
                                <a href="http://googleusercontent.com/maps.google.com/2<?= urlencode($vaga['endereco']['logradouro'] . ', ' . $vaga['endereco']['numero'] . ' - ' . $vaga['endereco']['cidade']) ?>" target="_blank" class="block mt-4 text-center text-xs font-bold text-[#673DE6] hover:underline bg-indigo-50 py-2 rounded-lg">
                                    <i class="fas fa-map-marked-alt mr-1"></i> Ver no Google Maps
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">Localização exata não informada.</p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>

        <?php if (!empty($relacionadas)): ?>
        <div class="mt-16 border-t border-gray-200 pt-12">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">Vagas Relacionadas</h3>
            <div class="grid md:grid-cols-3 gap-6">
                <?php foreach($relacionadas as $r): 
                    $iniciaisR = strtoupper(substr($r['empresa'], 0, 2));
                    $colorR = ['bg-red-100 text-red-600','bg-blue-100 text-blue-600','bg-green-100 text-green-600'][crc32($r['empresa'])%3];
                ?>
                <a href="vaga.php?id=<?= $r['id'] ?>" class="bg-white p-5 rounded-xl border border-gray-200 hover:border-[#673DE6] hover:shadow-lg transition group">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-xs <?= $colorR ?>"><?= $iniciaisR ?></div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-gray-900 truncate group-hover:text-[#673DE6] transition"><?= $r['titulo'] ?></h4>
                            <p class="text-xs text-gray-500 truncate"><?= $r['empresa'] ?></p>
                        </div>
                    </div>
                    <div class="flex gap-2 text-[10px] font-semibold text-gray-600">
                        <span class="bg-gray-100 px-2 py-1 rounded"><?= $r['modelo'] ?></span>
                        <span class="bg-gray-100 px-2 py-1 rounded"><?= $r['nivel'] ?></span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between text-xs text-gray-400">
                        <span><i class="fas fa-map-marker-alt"></i> <?= explode('-', $r['endereco']['display']??'Brasil')[0] ?></span>
                        <span><?= date('d/m', strtotime($r['data_criacao'])) ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <footer class="bg-[#36344D] text-white pt-12 pb-8 border-t-4 border-[#673DE6] mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p class="text-xs text-gray-500">© <?= date('Y') ?> Blipei. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>