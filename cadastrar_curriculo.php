<?php
require_once 'functions.php';
$config = carregarConfig();
$sucesso = false;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação básica
    if (empty($_POST['nome']) || empty($_POST['email']) || empty($_FILES['curriculo']['name'])) {
        $erro = "Por favor, preencha os campos obrigatórios e anexe o currículo.";
    } elseif (!isset($_POST['termos'])) {
        $erro = "Você precisa aceitar os termos para continuar.";
    } else {
        $resultado = salvarCandidato($_POST, $_FILES['curriculo']);
        if ($resultado['sucesso']) {
            $sucesso = true;
        } else {
            $erro = $resultado['msg'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Currículo | Blipei Vagas</title>
    <style>body { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800">
    
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto p-4 max-w-3xl my-8">
        
        <?php if ($sucesso): ?>
            <div class="bg-white p-12 rounded-2xl shadow-xl text-center border border-green-100">
                <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">🚀</div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Currículo Recebido!</h2>
                <p class="text-gray-500 mb-8 max-w-lg mx-auto">
                    Nossa equipe irá analisar seus dados e currículo. Assim que surgirem oportunidades compatíveis com seu perfil, entraremos em contato. Boa sorte!
                </p>
                <a href="index.php" class="bg-[#673DE6] text-white px-8 py-3 rounded-xl font-bold hover:opacity-90 transition">Ver Vagas Disponíveis</a>
            </div>
        <?php else: ?>
            
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#36344D]">Banco de Talentos</h1>
                <p class="text-gray-500 mt-2">Cadastre-se para receber convites de empresas parceiras.</p>
            </div>

            <form method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                
                <?php if ($erro): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-6 text-sm font-bold border border-red-100">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?= $erro ?>
                    </div>
                <?php endif; ?>

                <h3 class="text-lg font-bold text-[#673DE6] mb-4 flex items-center gap-2">
                    <i class="fas fa-user-circle"></i> Dados Pessoais
                </h3>
                
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nome Completo *</label>
                        <input type="text" name="nome" required class="w-full border border-gray-300 rounded-lg p-3 focus:border-[#673DE6] outline-none transition" placeholder="Seu nome">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">E-mail *</label>
                        <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg p-3 focus:border-[#673DE6] outline-none transition" placeholder="seu@email.com">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Telefone / WhatsApp *</label>
                        <input type="tel" name="telefone" required class="w-full border border-gray-300 rounded-lg p-3 focus:border-[#673DE6] outline-none transition" placeholder="(11) 99999-9999">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">LinkedIn (Opcional)</label>
                        <input type="url" name="linkedin" class="w-full border border-gray-300 rounded-lg p-3 focus:border-[#673DE6] outline-none transition" placeholder="linkedin.com/in/voce">
                    </div>
                </div>

                <hr class="border-gray-100 my-8">

                <h3 class="text-lg font-bold text-[#673DE6] mb-4 flex items-center gap-2">
                    <i class="fas fa-money-bill-wave"></i> Pretensão Salarial
                </h3>

                <div class="mb-6">
                    <div class="flex gap-6 mb-4">
                        <label class="flex items-center gap-2 cursor-pointer bg-gray-50 px-4 py-2 rounded-lg border border-gray-200 has-[:checked]:border-[#673DE6] has-[:checked]:bg-indigo-50 transition">
                            <input type="radio" name="tipo_salario" value="minimo" checked onclick="toggleSalario('minimo')" class="text-[#673DE6] focus:ring-[#673DE6]">
                            <span class="text-sm font-medium">A partir de X</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer bg-gray-50 px-4 py-2 rounded-lg border border-gray-200 has-[:checked]:border-[#673DE6] has-[:checked]:bg-indigo-50 transition">
                            <input type="radio" name="tipo_salario" value="faixa" onclick="toggleSalario('faixa')" class="text-[#673DE6] focus:ring-[#673DE6]">
                            <span class="text-sm font-medium">Faixa (De X até Y)</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-gray-500 font-bold">R$</span>
                            <input type="number" name="salario_min" required class="w-full pl-10 border border-gray-300 rounded-lg p-3" placeholder="Valor Mínimo">
                        </div>
                        <div class="relative hidden" id="div_salario_max">
                            <span class="absolute left-3 top-3 text-gray-500 font-bold">R$</span>
                            <input type="number" name="salario_max" id="salario_max" class="w-full pl-10 border border-gray-300 rounded-lg p-3" placeholder="Valor Máximo">
                        </div>
                    </div>
                </div>

                <hr class="border-gray-100 my-8">

                <h3 class="text-lg font-bold text-[#673DE6] mb-4 flex items-center gap-2">
                    <i class="fas fa-layer-group"></i> Áreas de Interesse
                </h3>
                <p class="text-xs text-gray-500 mb-4">Selecione uma ou mais áreas que você atua.</p>
                
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-8">
                    <?php foreach($config['categorias'] as $k => $v): ?>
                    <label class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-100 transition has-[:checked]:border-[#673DE6] has-[:checked]:bg-indigo-50">
                        <input type="checkbox" name="areas[]" value="<?=$k?>" class="rounded text-[#673DE6] focus:ring-[#673DE6]">
                        <span class="text-sm font-medium text-gray-700"><?=$v?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <hr class="border-gray-100 my-8">

                <h3 class="text-lg font-bold text-[#673DE6] mb-4 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt"></i> Disponibilidade
                </h3>

                <div class="mb-6">
                    <label class="flex items-center gap-3 p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition w-full md:w-1/2">
                        <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                            <input type="checkbox" name="aceita_presencial" id="check_presencial" value="1" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer border-gray-300" onclick="toggleEndereco(this.checked)"/>
                            <label for="check_presencial" class="toggle-label block overflow-hidden h-5 rounded-full bg-gray-300 cursor-pointer"></label>
                        </div>
                        <span class="text-sm font-bold text-gray-700">Aceito trabalhar presencialmente</span>
                    </label>
                    
                    <div id="form_endereco" class="hidden mt-4 p-5 bg-gray-50 rounded-xl border border-gray-200">
                        <p class="text-xs text-gray-500 mb-3 font-bold uppercase">Sua Localização</p>
                        <div class="grid md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <input type="text" name="cep" id="cep" class="w-full border rounded-lg p-2.5 text-sm" placeholder="CEP" onblur="buscarCep(this.value)">
                            </div>
                            <div class="md:col-span-3">
                                <input type="text" name="cidade" id="cidade" class="w-full border rounded-lg p-2.5 text-sm bg-gray-100" readonly placeholder="Cidade">
                            </div>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <input type="text" name="uf" id="uf" class="w-full border rounded-lg p-2.5 text-sm bg-gray-100" readonly placeholder="Estado">
                            <input type="text" name="bairro" id="bairro" class="w-full border rounded-lg p-2.5 text-sm bg-gray-100" readonly placeholder="Bairro">
                        </div>
                    </div>
                </div>

                <hr class="border-gray-100 my-8">

                <h3 class="text-lg font-bold text-[#673DE6] mb-4 flex items-center gap-2">
                    <i class="fas fa-file-upload"></i> Currículo
                </h3>
                
                <div class="mb-8">
                    <label class="flex flex-col items-center px-4 py-6 bg-white text-blue rounded-lg shadow-lg tracking-wide uppercase border border-blue cursor-pointer hover:bg-blue hover:text-white border-dashed border-2 border-gray-300 hover:border-[#673DE6] transition group">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 group-hover:text-[#673DE6] mb-2 transition"></i>
                        <span class="mt-2 text-sm font-medium text-gray-600 group-hover:text-[#673DE6]">Selecione seu arquivo (PDF, DOC, DOCX)</span>
                        <input type='file' name="curriculo" class="hidden" accept=".pdf,.doc,.docx" required onchange="document.getElementById('file-name').textContent = this.files[0].name" />
                    </label>
                    <p id="file-name" class="text-center text-sm text-gray-500 mt-2 font-medium"></p>
                </div>

                <div class="bg-indigo-50 p-4 rounded-lg mb-6 flex items-start gap-3">
                    <input type="checkbox" name="termos" id="termos" required class="mt-1 rounded text-[#673DE6] focus:ring-[#673DE6]">
                    <label for="termos" class="text-xs text-gray-600 leading-relaxed cursor-pointer">
                        Aceito receber oportunidades por e-mail e WhatsApp de vagas que se relacionam com meu perfil e permito a divulgação do meu currículo e dados com empresas parceiras do Blipei interessadas em contratar, de acordo com a LGPD.
                    </label>
                </div>

                <button type="submit" class="w-full bg-[#673DE6] text-white font-bold py-4 rounded-xl hover:opacity-90 transition shadow-lg text-lg flex items-center justify-center gap-2">
                    <i class="fas fa-paper-plane"></i> Enviar Currículo
                </button>

            </form>
        <?php endif; ?>
    </div>

    <style>
        /* Toggle Switch CSS */
        .toggle-checkbox:checked { right: 0; border-color: #68D391; }
        .toggle-checkbox:checked + .toggle-label { background-color: #68D391; }
        .toggle-checkbox { right: 50%; transition: all 0.3s; }
    </style>

    <script>
        function toggleSalario(tipo) {
            const divMax = document.getElementById('div_salario_max');
            if (tipo === 'faixa') {
                divMax.classList.remove('hidden');
                document.getElementById('salario_max').required = true;
            } else {
                divMax.classList.add('hidden');
                document.getElementById('salario_max').required = false;
                document.getElementById('salario_max').value = '';
            }
        }

        function toggleEndereco(checked) {
            const div = document.getElementById('form_endereco');
            if (checked) {
                div.classList.remove('hidden');
                document.getElementById('cep').required = true;
            } else {
                div.classList.add('hidden');
                document.getElementById('cep').required = false;
            }
        }

        function buscarCep(cep) {
            cep = cep.replace(/\D/g, '');
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(r => r.json())
                    .then(d => {
                        if (!d.erro) {
                            document.getElementById('cidade').value = d.localidade;
                            document.getElementById('uf').value = d.uf;
                            document.getElementById('bairro').value = d.bairro;
                        } else {
                            alert('CEP não encontrado.');
                        }
                    });
            }
        }
    </script>
</body>
</html>