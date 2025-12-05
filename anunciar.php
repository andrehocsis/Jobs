<?php
require_once 'functions.php';
$config = carregarConfig();
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(criarVaga($_POST)) $sucesso = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anunciar Vaga | Blipei</title>
    <style>body { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800">
    
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto p-4 max-w-4xl my-8">
        <?php if ($sucesso): ?>
            <div class="bg-white p-12 rounded-2xl shadow-xl text-center border border-green-100">
                <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">🎉</div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Vaga enviada!</h2>
                <p class="text-gray-500 mb-8">Nossa equipe irá revisar e publicar em breve.</p>
                <a href="index.php" class="bg-[#673DE6] text-white px-8 py-3 rounded-xl font-bold hover:opacity-90">Ver Vagas</a>
            </div>
        <?php else: ?>
            
            <form method="POST" class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                <h1 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-4">Nova Oportunidade</h1>
                
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Título da Vaga *</label>
                        <input type="text" name="titulo" required class="w-full border border-gray-300 rounded-lg p-3 focus:border-[#673DE6] outline-none transition" placeholder="Ex: Desenvolvedor PHP">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Empresa *</label>
                        <input type="text" name="empresa" required class="w-full border border-gray-300 rounded-lg p-3 focus:border-[#673DE6] outline-none transition" placeholder="Nome da empresa">
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Área</label>
                        <select name="categoria" class="w-full border border-gray-300 rounded-lg p-3 bg-white">
                            <?php foreach($config['categorias'] as $k => $v): echo "<option value='$k'>$v</option>"; endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nível</label>
                        <select name="nivel" class="w-full border border-gray-300 rounded-lg p-3 bg-white">
                            <?php foreach($config['niveis'] as $v): echo "<option value='$v'>$v</option>"; endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Modelo</label>
                        <select name="modelo" class="w-full border border-gray-300 rounded-lg p-3 bg-white">
                            <?php foreach($config['modelos'] as $v): echo "<option value='$v'>$v</option>"; endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <label class="block text-sm font-bold text-gray-700 mb-3">Salário / Remuneração</label>
                    <div class="flex gap-6 items-center">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="tipo_salario" value="combinar" checked onclick="toggleSalario(false)" class="text-[#673DE6] focus:ring-[#673DE6]">
                            <span class="text-sm">A combinar</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="tipo_salario" value="valor" onclick="toggleSalario(true)" class="text-[#673DE6] focus:ring-[#673DE6]">
                            <span class="text-sm">Informar valor</span>
                        </label>
                    </div>
                    <div id="campo_salario" class="hidden mt-3">
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-gray-500 font-bold">R$</span>
                            <input type="number" name="salario_valor" class="w-full pl-10 border border-gray-300 rounded-lg p-3" placeholder="Ex: 5000,00">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">O Google Jobs prioriza vagas com salário informado.</p>
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 mb-6">
                    <h3 class="text-lg font-bold text-[#673DE6] mb-4 flex items-center gap-2"><i class="fas fa-map-marker-alt"></i> Localização da Empresa</h3>
                    <div class="grid md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">CEP</label>
                            <input type="text" name="cep" id="cep" class="w-full border rounded-lg p-2.5" placeholder="00000-000" onblur="buscarCep(this.value)">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Rua</label>
                            <input type="text" name="logradouro" id="logradouro" class="w-full border rounded-lg p-2.5 bg-white" readonly>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-3 gap-4 mb-4">
                        <div><label class="block text-xs font-bold text-gray-500 mb-1">Número</label><input type="text" name="numero" id="numero" class="w-full border rounded-lg p-2.5"></div>
                        <div><label class="block text-xs font-bold text-gray-500 mb-1">Bairro</label><input type="text" name="bairro" id="bairro" class="w-full border rounded-lg p-2.5 bg-white" readonly></div>
                        <div><label class="block text-xs font-bold text-gray-500 mb-1">Cidade - UF</label><input type="text" name="cidade" id="cidade" class="w-full border rounded-lg p-2.5 bg-white mb-1" readonly><input type="hidden" name="uf" id="uf"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Localização Manual (Se busca falhar)</label>
                        <input type="text" name="localizacao_manual" id="loc_manual" class="w-full border rounded-lg p-2.5" placeholder="Ex: Pinheiros, São Paulo - SP">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Descrição da Vaga *</label>
                    <textarea name="descricao" required rows="6" class="w-full border border-gray-300 rounded-lg p-3 focus:border-[#673DE6] outline-none" placeholder="Requisitos, benefícios..."></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-6 mb-8 bg-indigo-50 p-6 rounded-xl">
                    <div>
                        <label class="block text-sm font-bold text-indigo-900 mb-2">Regime</label>
                        <select name="regime" class="w-full border border-indigo-200 rounded-lg p-3 bg-white">
                            <?php foreach($config['regimes'] as $v): echo "<option value='$v'>$v</option>"; endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-indigo-900 mb-2">Canal de Candidatura</label>
                        <div class="flex gap-2">
                            <select name="tipo_contato" id="tipo_contato" class="w-1/3 border border-indigo-200 rounded-lg p-3 bg-white">
                                <option value="link">Link</option>
                                <option value="email">E-mail</option>
                                <option value="whatsapp">WhatsApp</option>
                            </select>
                            <input type="text" name="contato_valor" id="contato_valor" required class="w-2/3 border border-indigo-200 rounded-lg p-3" placeholder="https://...">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-[#673DE6] text-white font-bold py-4 rounded-xl hover:opacity-90 transition shadow-lg text-lg">Publicar Vaga</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Toggle Salário
        function toggleSalario(mostrar) {
            document.getElementById('campo_salario').classList.toggle('hidden', !mostrar);
        }

        // Placeholder Contato
        const tipoSelect = document.getElementById('tipo_contato');
        const contatoInput = document.getElementById('contato_valor');
        
        tipoSelect.addEventListener('change', () => {
            const map = { 'link': 'https://gupy.io/...', 'email': 'rh@empresa.com', 'whatsapp': '(11) 99999-9999' };
            contatoInput.placeholder = map[tipoSelect.value];
        });

        // ViaCEP
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
                            document.getElementById('numero').focus();
                        }
                    });
            }
        }
    </script>
</body>
</html>