<?php
require_once 'functions.php';

// Segurança: Apenas Admin
if (!isset($_SESSION['admin_logado'])) {
    header("Location: admin.php"); exit;
}

$id = $_GET['id'] ?? '';
$vaga = carregarVagaPorId($id);
$config = carregarConfig();

if (!$vaga) {
    header("Location: admin.php"); exit;
}

// Processar Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (editarVagaCompleta($id, $_POST)) {
        header("Location: admin.php?tab=vagas&msg=editado");
        exit;
    } else {
        $erro = "Erro ao salvar alterações.";
    }
}

// Helpers para preencher o formulário
$end = $vaga['endereco'] ?? [];
$isSalarioValor = ($vaga['salario'] !== 'A combinar');
$valorSalario = $vaga['salario_raw'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Vaga - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>body{font-family:'DM Sans',sans-serif}</style>
</head>
<body class="bg-gray-100 text-gray-800">

    <nav class="bg-white border-b p-4 sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <span class="font-bold text-xl text-[#673DE6]">Editar Vaga</span>
            <a href="admin.php" class="text-sm font-bold text-gray-500 hover:text-red-500">Cancelar e Voltar</a>
        </div>
    </nav>

    <div class="container mx-auto p-4 max-w-4xl my-8">
        <form method="POST" class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
            
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Título</label>
                    <input type="text" name="titulo" value="<?= htmlspecialchars($vaga['titulo']) ?>" required class="w-full border border-gray-300 rounded-lg p-3">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Empresa</label>
                    <input type="text" name="empresa" value="<?= htmlspecialchars($vaga['empresa']) ?>" required class="w-full border border-gray-300 rounded-lg p-3">
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
                    <input type="number" name="salario_valor" value="<?= $valorSalario ?>" class="w-full border border-gray-300 rounded-lg p-3" placeholder="Valor em centavos (ex: 500000)">
                </div>
            </div>

            <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 mb-6">
                <h3 class="text-lg font-bold text-[#673DE6] mb-4">Localização</h3>
                <div class="grid md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">CEP</label>
                        <input type="text" name="cep" id="cep" value="<?= $end['cep'] ?? '' ?>" class="w-full border rounded-lg p-2.5" onblur="buscarCep(this.value)">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-gray-500 mb-1">Rua</label>
                        <input type="text" name="logradouro" id="logradouro" value="<?= $end['logradouro'] ?? '' ?>" class="w-full border rounded-lg p-2.5">
                    </div>
                </div>
                <div class="grid md:grid-cols-3 gap-4 mb-4">
                    <div><label class="block text-xs font-bold text-gray-500 mb-1">Número</label><input type="text" name="numero" id="numero" value="<?= $end['numero'] ?? '' ?>" class="w-full border rounded-lg p-2.5"></div>
                    <div><label class="block text-xs font-bold text-gray-500 mb-1">Bairro</label><input type="text" name="bairro" id="bairro" value="<?= $end['bairro'] ?? '' ?>" class="w-full border rounded-lg p-2.5"></div>
                    <div><label class="block text-xs font-bold text-gray-500 mb-1">Cidade</label><input type="text" name="cidade" id="cidade" value="<?= $end['cidade'] ?? '' ?>" class="w-full border rounded-lg p-2.5"></div>
                    <input type="hidden" name="uf" id="uf" value="<?= $end['uf'] ?? '' ?>">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Display Manual</label>
                    <input type="text" name="localizacao_manual" id="loc_manual" value="<?= $end['display'] ?? '' ?>" class="w-full border rounded-lg p-2.5">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Descrição</label>
                <textarea name="descricao" required rows="10" class="w-full border border-gray-300 rounded-lg p-3"><?= strip_tags($vaga['descricao']) ?></textarea>
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
                            <option value="email" <?= $vaga['tipo_contato'] == 'email' ? 'selected' : '' ?>>E-mail</option>
                            <option value="whatsapp" <?= $vaga['tipo_contato'] == 'whatsapp' ? 'selected' : '' ?>>Whats</option>
                        </select>
                        <input type="text" name="contato_valor" value="<?= htmlspecialchars($vaga['contato_valor']) ?>" required class="w-2/3 border border-indigo-200 rounded-lg p-3">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-[#673DE6] text-white font-bold py-4 rounded-xl hover:opacity-90 transition shadow-lg text-lg">
                Salvar Alterações
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