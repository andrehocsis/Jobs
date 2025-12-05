<?php
// Configurações básicas
$pageTitle = "Grupos de Vagas e Networking | Blipei";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <?php include 'includes/seo.php'; ?>

    <style>
        body { font-family: 'DM Sans', sans-serif; background-color: #F8F9FC; }
        .card-grupo { transition: transform 0.2s, box-shadow 0.2s; }
        .card-grupo:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .bg-whatsapp { background-color: #25D366; }
        .bg-telegram { background-color: #0088cc; }
        .text-blipei { color: #673DE6; }
        
        /* Modal Animation */
        .modal-enter { opacity: 0; transform: scale(0.95); }
        .modal-enter-active { opacity: 1; transform: scale(1); transition: opacity 0.3s, transform 0.3s; }
        .modal-exit { opacity: 1; transform: scale(1); }
        .modal-exit-active { opacity: 0; transform: scale(0.95); transition: opacity 0.2s, transform 0.2s; }
    </style>
</head>
<body class="flex flex-col min-h-screen text-gray-800">

    <?php include 'includes/header.php'; ?>

    <header class="bg-white border-b border-slate-200 py-12 px-4 text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-indigo-50 to-white opacity-50 z-0"></div>
        <div class="relative z-10 container mx-auto max-w-4xl">
            <span class="text-[#673DE6] font-bold tracking-wider text-sm uppercase mb-2 block">Comunidade Blipei</span>
            <h1 class="text-3xl md:text-5xl font-bold text-slate-900 mb-4">
                Participe dos nossos <span class="text-[#673DE6]">Grupos VIP</span>
            </h1>
            <p class="text-slate-500 text-lg max-w-2xl mx-auto">
                Receba vagas em tempo real, faça networking com outros profissionais de TI e fique por dentro das novidades do mercado.
            </p>
        </div>
    </header>

    <main class="flex-grow container mx-auto px-4 py-12 max-w-5xl">
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <?php
            // Lista de Grupos
            $grupos = [
                [
                    'tipo' => 'whatsapp',
                    'nome' => 'Vagas Tech Brasil #01',
                    'desc' => 'Foco em desenvolvimento e QA.',
                    'link' => 'https://chat.whatsapp.com/6Bpt2vw1ZUK9i9RhnpVlQ1'
                ],
                [
                    'tipo' => 'whatsapp',
                    'nome' => 'Blipei Networking #02',
                    'desc' => 'Vagas gerais de TI e troca de ideias.',
                    'link' => 'https://chat.whatsapp.com/IU5EjAPg7kW9vuMKdJVtuT'
                ],
                [
                    'tipo' => 'whatsapp',
                    'nome' => 'Oportunidades TI #03',
                    'desc' => 'Vagas remotas e presenciais.',
                    'link' => 'https://chat.whatsapp.com/EXBBRaKEr7zCFSaVJm62u7'
                ],
                [
                    'tipo' => 'telegram',
                    'nome' => 'Canal Oficial Blipei',
                    'desc' => 'Todas as vagas publicadas no site.',
                    'link' => 'https://t.me/joinchat/q_PRgF_18n00NjEx'
                ],
                [
                    'tipo' => 'telegram',
                    'nome' => 'Comunidade Devs',
                    'desc' => 'Discussões técnicas e vagas.',
                    'link' => 'https://t.me/joinchat/ItMbuRCNaQ5sDXSKc4Ja5Q'
                ]
            ];

            foreach ($grupos as $g): 
                $isWhats = $g['tipo'] === 'whatsapp';
                $icon = $isWhats ? 'fab fa-whatsapp' : 'fab fa-telegram-plane';
                $bgClass = $isWhats ? 'bg-green-50 text-green-700 border-green-100' : 'bg-sky-50 text-sky-700 border-sky-100';
                $btnClass = $isWhats ? 'bg-[#25D366] hover:bg-[#20bd5a]' : 'bg-[#0088cc] hover:bg-[#0077b5]';
            ?>
            
            <div class="card-grupo bg-white rounded-2xl p-6 border border-slate-200 flex flex-col items-center text-center h-full">
                <div class="w-16 h-16 rounded-full flex items-center justify-center text-3xl mb-4 <?= $bgClass ?>">
                    <i class="<?= $icon ?>"></i>
                </div>
                
                <h3 class="font-bold text-xl text-slate-900 mb-2"><?= $g['nome'] ?></h3>
                <p class="text-slate-500 text-sm mb-6 flex-grow"><?= $g['desc'] ?></p>
                
                <button onclick="abrirPopup(event, '<?= $g['link'] ?>')" class="w-full text-white font-bold py-3 rounded-xl transition shadow-md flex items-center justify-center gap-2 <?= $btnClass ?>">
                    Entrar no Grupo <i class="fas fa-arrow-right text-sm"></i>
                </button>
            </div>

            <?php endforeach; ?>

            <div class="card-grupo bg-[#36344D] rounded-2xl p-6 border border-[#36344D] flex flex-col items-center text-center h-full text-white">
                <div class="w-16 h-16 rounded-full flex items-center justify-center text-3xl mb-4 bg-white/10 text-[#A085F5]">
                    <i class="fas fa-file-contract"></i>
                </div>
                
                <h3 class="font-bold text-xl mb-2">Banco de Talentos</h3>
                <p class="text-gray-300 text-sm mb-6 flex-grow">Ainda não cadastrou seu CV? As empresas olham aqui primeiro.</p>
                
                <a href="cadastrar_curriculo.php" class="w-full bg-[#673DE6] hover:bg-[#5a32d1] text-white font-bold py-3 rounded-xl transition shadow-md">
                    Cadastrar Agora
                </a>
            </div>

        </div>
    </main>

    <footer class="bg-[#36344D] text-white pt-12 pb-8 border-t-4 border-[#673DE6] mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p class="text-xs text-gray-500">© <?= date('Y') ?> Blipei. Todos os direitos reservados.</p>
        </div>
    </footer>

    <div id="modalConduta" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/90 backdrop-blur-sm px-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="modalCard">
            
            <div class="bg-indigo-50 p-6 text-center border-b border-indigo-100">
                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl shadow-sm text-indigo-600">
                    <i class="fas fa-hand-sparkles"></i>
                </div>
                <h3 class="text-xl font-bold text-indigo-900">Antes de entrar...</h3>
                <p class="text-indigo-600 text-sm">Leia nossas regras rápidas de convivência.</p>
            </div>

            <div class="p-6 space-y-4">
                <ul class="space-y-3 text-sm text-gray-600">
                    <li class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 mt-1"></i>
                        <span><strong>Respeito acima de tudo:</strong> Sem spam, correntes ou conteúdo ofensivo.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 mt-1"></i>
                        <span><strong>Foco em Vagas:</strong> Use o grupo para networking e oportunidades de TI.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 mt-1"></i>
                        <span><strong>Segurança:</strong> Nunca pague para participar de processos seletivos.</span>
                    </li>
                </ul>

                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 flex items-start gap-3 mt-4">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mt-1"></i>
                    <div>
                        <h4 class="font-bold text-yellow-800 text-sm">Importante!</h4>
                        <p class="text-xs text-yellow-700 mt-1">
                            Recrutadores buscam primeiro em nosso banco de dados. Para aumentar suas chances, certifique-se de ter seu currículo cadastrado.
                        </p>
                        <a href="cadastrar_curriculo.php" class="text-xs font-bold text-yellow-900 underline hover:text-yellow-600 mt-2 block">
                            Cadastrar meu currículo agora ->
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-gray-50 flex gap-3 justify-end">
                <button onclick="fecharPopup()" class="px-4 py-2 text-gray-500 font-bold text-sm hover:bg-gray-200 rounded-lg transition">
                    Cancelar
                </button>
                <button id="btnProceguir" class="px-6 py-2 bg-[#673DE6] text-white font-bold text-sm rounded-lg hover:bg-[#5a32d1] transition shadow-lg flex items-center gap-2">
                    Concordo e Entrar <i class="fas fa-external-link-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        let targetUrl = '';
        const modal = document.getElementById('modalConduta');
        const card = document.getElementById('modalCard');
        const btnGo = document.getElementById('btnProceguir');

        function abrirPopup(e, url) {
            e.preventDefault();
            targetUrl = url;
            
            // Mostra o modal
            modal.classList.remove('hidden');
            
            // Animação de entrada
            setTimeout(() => {
                card.classList.remove('scale-95', 'opacity-0');
                card.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function fecharPopup() {
            // Animação de saída
            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Ação do botão "Concordo"
        btnGo.addEventListener('click', () => {
            window.open(targetUrl, '_blank');
            fecharPopup();
        });

        // Fechar ao clicar fora
        modal.addEventListener('click', (e) => {
            if (e.target === modal) fecharPopup();
        });
    </script>

</body>
</html>