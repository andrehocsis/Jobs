<?php
require_once 'functions.php';

$token = $_GET['token'] ?? '';
$vaga = validarTokenEdicao($token);
$config = carregarConfig();

// Se token inválido ou expirado
if (!$vaga) {
    die("
    <link href='https://cdn.tailwindcss.com' rel='stylesheet'>
    <div class='h-screen flex items-center justify-center bg-gray-100'>
        <div class='bg-white p-8 rounded-xl shadow-lg text-center max-w-md'>
            <div class='text-4xl mb-4'>🚫</div>
            <h1 class='text-xl font-bold text-gray-800 mb-2'>Link Inválido ou Expirado</h1>
            <p class='text-gray-500 mb-6'>Este link de edição já venceu ou não existe. Entre em contato com o suporte para solicitar um novo.</p>
            <a href='index.php' class='bg-gray-800 text-white px-6 py-2 rounded-lg font-bold'>Ir para Home</a>
        </div>
    </div>
    ");
}

// Processar Edição
$sucesso = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reutiliza a função de edição do admin
    if (editarVagaCompleta($vaga['id'], $_POST)) {
        $sucesso = true;
        // Recarrega dados atualizados
        $vaga = carregarVagaPorId($vaga['id']);
    }
}

// Helpers de preenchimento (iguais ao admin_editar)
$end = $vaga['endereco'] ?? [];
$isSalarioValor = ($vaga['salario'] !== 'A combinar');
$valorSalario = $vaga['salario_raw'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vaga | Acesso Temporário</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>body{font-family:'DM Sans',sans-serif}</style>
</head>
<body class="bg-gray-50 text-gray-800">

    <nav class="bg-white border-b p-4 sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <span class="font-bold text-xl text-[#673DE6]">Edição de Vaga</span>
            <div class="text-xs bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full font-bold">
                Modo Visitante (24h)
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4 max-w-4xl my-8">
        
        <?php if ($sucesso): ?>
            <div class="bg-green-50 border border-green-200 p-6 rounded-xl text-center mb-8">
                <h2 class="text-2xl font-bold text-green-700 mb-2">Alterações Salvas!</h2>
                <p class="text-green-600 mb-4">Sua vaga foi atualizada com sucesso.</p>
                <a href="vaga.php?id=<?= $vaga['id'] ?>" target="_blank" class="inline-block bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700">Ver Vaga Online</a>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Título da Vaga *</label>
                    <input type="text" name="titulo" value="<?= htmlspecialchars($vaga['titulo']) ?>" required class="w-full border border-gray-300 rounded-lg p-3 focus:border-[#673DE6] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Empresa *</label>
                    <input type="text" name="empresa" value="<?= htmlspecialchars($vaga['empresa']) ?>" required class="w-full border border-gray-300 rounded-lg p-3 focus:border-[#673DE6] outline-none">
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Área</label>
                    <select name="categoria" class="w-full border border-gray-300 rounded-lg p-3 bg-white">
                        <?php foreach($config['categorias'] as $k => $v): ?>
                            <option value="<?=$k?>" <?= $vaga['categoria'] == $k ? 'selected' : '' ?>><?=$v?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nível</label>
                    <select name="nivel" class="w-full border border-gray-300 rounded-lg p-3 bg-white">
                        <?php foreach($config['niveis'] as $v): ?>
                            <option value="<?=$v?>" <?= $vaga['nivel'] == $v ? 'selected' : '' ?>><?=$v?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Modelo</label>
                    <select name="modelo" class="w-full border border-gray-300 rounded-lg p-3 bg-white">
                        <?php foreach($config['modelos'] as $v): ?>
                            <option value="<?=$v?>" <?= $vaga['modelo'] == $v ? 'selected' : '' ?>><?=$v?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <label class="block text-sm font-bold text-gray-700 mb-3">Salário</label>
                <div class="flex gap-6 items-center">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="tipo_salario" value="combinar" <?= !$isSalarioValor ? 'checked' : '' ?> onclick="toggleSalario(false)">
                        <span class="text-sm">A combinar</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="tipo_salario" value="valor" <?= $isSalarioValor ? 'checked' : '' ?> onclick="toggleSalario(true)">
                        <span class="text-sm">Informar valor</span>
                    </label>
                </div>
                <div id="campo_salario" class="<?= $isSalarioValor ? '' : 'hidden' ?> mt-3">
                    <input type="number" name="salario_valor" value="<?= $valorSalario ?>" class="w-full border border-gray-300 rounded-lg p-3" placeholder="Valor em centavos">
                </div>
            </div>

            <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 mb-6">
                <h3 class="text-lg font-bold text-[#673DE6] mb-4">Localização da Empresa</h3>
                <div class="grid md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">CEP</label>
                        <input type="text" name="cep" value="<?= $end['cep'] ?? '' ?>" class="w-full border rounded-lg p-2.5" onblur="buscarCep(this.value)">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-gray-500 mb-1">Rua</label>
                        <input type="text" name="logradouro" id="logradouro" value="<?= $end['logradouro'] ?? '' ?>" class="w-full border rounded-lg p-2.5">
                    </div>
                </div>
                <div class="grid md:grid-cols-3 gap-4 mb-4">
                    <div><label class="block text-xs font-bold text-gray-500 mb-1">Número</label><input type="text" name="numero" value="<?= $end['numero'] ?? '' ?>" class="w-full border rounded-lg p-2.5"></div>
                    <div><label class="block text-xs font-bold text-gray-500 mb-1">Bairro</label><input type="text" name="bairro" id="bairro" value="<?= $end['bairro'] ?? '' ?>" class="w-full border rounded-lg p-2.5"></div>
                    <div><label class="block text-xs font-bold text-gray-500 mb-1">Cidade</label><input type="text" name="cidade" id="cidade" value="<?= $end['cidade'] ?? '' ?>" class="w-full border rounded-lg p-2.5"></div>
                    <input type="hidden" name="uf" id="uf" value="<?= $end['uf'] ?? '' ?>">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Localização Manual</label>
                    <input type="text" name="localizacao_manual" id="loc_manual" value="<?= $end['display'] ?? '' ?>" class="w-full border rounded-lg p-2.5">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Descrição *</label>
                <textarea name="descricao" required rows="8" class="w-full border border-gray-300 rounded-lg p-3"><?= strip_tags($vaga['descricao']) ?></textarea>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-8 bg-indigo-50 p-6 rounded-xl">
                <div>
                    <label class="block text-sm font-bold text-indigo-900 mb-2">Regime</label>
                    <select name="regime" class="w-full border border-indigo-200 rounded-lg p-3 bg-white">
                        <?php foreach($config['regimes'] as $v): ?>
                            <option value="<?=$v?>" <?= $vaga['regime'] == $v ? 'selected' : '' ?>><?=$v?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-indigo-900 mb-2">Contato</label>
                    <div class="flex gap-2">
                        <select name="tipo_contato" class="w-1/3 border border-indigo-200 rounded-lg p-3 bg-white">
                            <option value="link" <?= $vaga['tipo_contato'] == 'link' ? 'selected' : '' ?>>Link</option>
                            <option value="email" <?= $vaga['tipo_contato'] == 'email' ? 'selected' : '' ?>>Email</option>
                            <option value="whatsapp" <?= $vaga['tipo_contato'] == 'whatsapp' ? 'selected' : '' ?>>Whats</option>
                        </select>
                        <input type="text" name="contato_valor" value="<?= htmlspecialchars($vaga['contato_valor']) ?>" required class="w-2/3 border border-indigo-200 rounded-lg p-3">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-[#673DE6] text-white font-bold py-4 rounded-xl hover:opacity-90 transition shadow-lg text-lg">
                Atualizar Minha Vaga
            </button>
        </form>
    </div>

    <script>
        function toggleSalario(mostrar) {
            document.getElementById('campo_salario').classList.toggle('hidden', !mostrar);
        }
        function buscarCep(cep) {
            cep = cep.replace(/\D/g, '');
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(r => r.json())
                    .then(d => {
                        if (!d.erro) {
                            document.getElementById('logradouro').value = d.logradouro;
                            document.getElementById('bairro').value = d.bairro;
                            document.getElementById('cidade').value = d.localidade;
                            document.getElementById('uf').value = d.uf;
                            document.getElementById('loc_manual').value = `${d.localidade} - ${d.uf}`;
                        }
                    });
            }
        }
    </script>
</body>
</html>