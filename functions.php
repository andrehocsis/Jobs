<?php
/**
 * BLIPEI VAGAS - CORE SYSTEM v8.0 (Versão Definitiva)
 * Inclui: Vagas, Candidatos (Upload/Leitura), Comentários, Config, Magic Links e Validações.
 */
session_start();

// --- CONSTANTES E CONFIGURAÇÕES ---
define('DATA_DIR', 'data/');
define('VAGAS_FILE', 'data/vagas.json');
define('CANDIDATOS_FILE', 'data/candidatos.json');
define('CONFIG_FILE', 'data/config.json');
define('UPLOADS_DIR', 'uploads/curriculos/');
define('ADMIN_PASS', 'adminBlipei2024'); // IMPORTANTE: Altere sua senha aqui para segurança!

// --- INICIALIZAÇÃO DO SISTEMA ---
function inicializarSistema() {
    // Garante que as pastas existam
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);

    // Garante que os arquivos JSON existam
    if (!file_exists(VAGAS_FILE)) file_put_contents(VAGAS_FILE, json_encode([], JSON_PRETTY_PRINT));
    if (!file_exists(CANDIDATOS_FILE)) file_put_contents(CANDIDATOS_FILE, json_encode([], JSON_PRETTY_PRINT));
    
    // Configuração Padrão se não existir
    if (!file_exists(CONFIG_FILE)) {
        $config = [
            'categorias' => [
                'dev_back' => 'Backend', 'dev_front' => 'Frontend', 'dev_mobile' => 'Mobile',
                'dev_full' => 'Fullstack', 'dados' => 'Dados', 'devops' => 'DevOps',
                'design' => 'Design', 'produto' => 'Produto', 'mkt' => 'Marketing', 'suporte' => 'Suporte'
            ],
            'niveis' => ['Estágio', 'Júnior', 'Pleno', 'Sênior', 'Especialista', 'Tech Lead', 'C-Level'],
            'regimes' => ['CLT', 'PJ', 'Híbrido', 'Freelance'],
            'modelos' => ['100% Remoto', 'Híbrido', 'Presencial']
        ];
        salvarConfig($config);
    }
}

// --- GESTÃO DE CONFIGURAÇÕES ---
function carregarConfig() { 
    inicializarSistema(); 
    return json_decode(file_get_contents(CONFIG_FILE), true); 
}

function salvarConfig($data) { 
    return file_put_contents(CONFIG_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); 
}

// =============================================================================
// MÓDULO DE VAGAS
// =============================================================================

function carregarVagas() {
    inicializarSistema();
    $vagas = json_decode(file_get_contents(VAGAS_FILE), true) ?? [];
    // Ordena por data (mais recentes primeiro)
    usort($vagas, fn($a, $b) => strtotime($b['data_criacao']) - strtotime($a['data_criacao']));
    return $vagas;
}

function carregarVagaPorId($id) {
    $vagas = carregarVagas();
    foreach ($vagas as $v) {
        if ($v['id'] === $id) return $v;
    }
    return null;
}

function salvarVagas($vagas) {
    return file_put_contents(VAGAS_FILE, json_encode($vagas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function criarVaga($dados) {
    $vagas = carregarVagas();
    
    // Tratamento de Localização
    $locDisplay = strip_tags($dados['localizacao_manual'] ?? '');
    if (!empty($dados['cidade']) && !empty($dados['uf'])) {
        $locDisplay = $dados['cidade'] . ' - ' . $dados['uf'];
    }
    if (empty($locDisplay)) $locDisplay = "Local não informado";

    // Tratamento de Salário
    $salarioValor = preg_replace('/\D/', '', $dados['salario_valor'] ?? '');
    $salarioDisplay = 'A combinar';
    if (($dados['tipo_salario'] ?? '') === 'valor' && !empty($salarioValor)) {
        $salarioDisplay = 'R$ ' . number_format($salarioValor / 100, 2, ',', '.');
    }

    $nova = [
        'id' => uniqid('v_'),
        'titulo' => strip_tags($dados['titulo']),
        'empresa' => strip_tags($dados['empresa']),
        'categoria' => $dados['categoria'],
        'nivel' => $dados['nivel'],
        'regime' => $dados['regime'],
        'modelo' => $dados['modelo'],
        'descricao' => strip_tags($dados['descricao']),
        'tipo_contato' => $dados['tipo_contato'],
        'contato_valor' => strip_tags($dados['contato_valor']),
        'salario' => $salarioDisplay,
        'salario_raw' => $salarioValor,
        'endereco' => [
            'cep' => strip_tags($dados['cep'] ?? ''),
            'logradouro' => strip_tags($dados['logradouro'] ?? ''),
            'numero' => strip_tags($dados['numero'] ?? ''),
            'bairro' => strip_tags($dados['bairro'] ?? ''),
            'cidade' => strip_tags($dados['cidade'] ?? ''),
            'uf' => strip_tags($dados['uf'] ?? ''),
            'display' => $locDisplay
        ],
        'status' => 'pendente',
        'data_criacao' => date('Y-m-d H:i:s'),
        'comentarios' => []
    ];
    
    array_unshift($vagas, $nova);
    return salvarVagas($vagas);
}

function editarVagaCompleta($id, $dados) {
    $vagas = carregarVagas();
    $encontrou = false;

    // Tratamento de dados (Igual ao criarVaga)
    $locDisplay = strip_tags($dados['localizacao_manual'] ?? '');
    if (!empty($dados['cidade']) && !empty($dados['uf'])) {
        $locDisplay = $dados['cidade'] . ' - ' . $dados['uf'];
    }
    
    $salarioValor = preg_replace('/\D/', '', $dados['salario_valor'] ?? '');
    $salarioDisplay = 'A combinar';
    if (($dados['tipo_salario'] ?? '') === 'valor' && !empty($salarioValor)) {
        $salarioDisplay = 'R$ ' . number_format($salarioValor / 100, 2, ',', '.');
    }

    foreach ($vagas as &$v) {
        if ($v['id'] === $id) {
            $v['titulo'] = strip_tags($dados['titulo']);
            $v['empresa'] = strip_tags($dados['empresa']);
            $v['categoria'] = $dados['categoria'];
            $v['nivel'] = $dados['nivel'];
            $v['regime'] = $dados['regime'];
            $v['modelo'] = $dados['modelo'];
            $v['descricao'] = strip_tags($dados['descricao']);
            $v['tipo_contato'] = $dados['tipo_contato'];
            $v['contato_valor'] = strip_tags($dados['contato_valor']);
            $v['salario'] = $salarioDisplay;
            $v['salario_raw'] = $salarioValor;
            $v['endereco'] = [
                'cep' => strip_tags($dados['cep'] ?? ''),
                'logradouro' => strip_tags($dados['logradouro'] ?? ''),
                'numero' => strip_tags($dados['numero'] ?? ''),
                'bairro' => strip_tags($dados['bairro'] ?? ''),
                'cidade' => strip_tags($dados['cidade'] ?? ''),
                'uf' => strip_tags($dados['uf'] ?? ''),
                'display' => $locDisplay
            ];
            $encontrou = true; 
            break;
        }
    }

    if ($encontrou) return salvarVagas($vagas);
    return false;
}

// Funções de Status/Admin
function atualizarStatusVaga($id, $status) {
    $vagas = carregarVagas();
    foreach ($vagas as &$v) {
        if ($v['id'] === $id) {
            $v['status'] = $status;
            break;
        }
    }
    return salvarVagas($vagas);
}

function excluirVaga($id) {
    $vagas = carregarVagas();
    $novas = array_filter($vagas, fn($v) => $v['id'] !== $id);
    return salvarVagas(array_values($novas));
}

// =============================================================================
// MÓDULO DE CANDIDATOS
// =============================================================================

function salvarCandidato($dados, $arquivo) {
    inicializarSistema();
    $candidatos = json_decode(file_get_contents(CANDIDATOS_FILE), true) ?? [];
    
    // Processa Upload
    $nomeArquivo = null;
    if ($arquivo && $arquivo['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        $allowed = ['pdf', 'doc', 'docx'];
        if (in_array(strtolower($ext), $allowed)) {
            $novoNome = uniqid('cv_') . '.' . $ext;
            if (move_uploaded_file($arquivo['tmp_name'], UPLOADS_DIR . $novoNome)) {
                $nomeArquivo = $novoNome;
            }
        }
    }
    if (!$nomeArquivo) return ['sucesso' => false, 'msg' => 'Erro no upload. Apenas PDF, DOC ou DOCX.'];

    // Processa Endereço
    $endereco = null;
    if (isset($dados['aceita_presencial'])) {
        $endereco = [
            'cep' => strip_tags($dados['cep'] ?? ''),
            'cidade' => strip_tags($dados['cidade'] ?? ''),
            'uf' => strip_tags($dados['uf'] ?? ''),
            'bairro' => strip_tags($dados['bairro'] ?? '')
        ];
    }

    // Processa Salário
    $salario = "A combinar";
    if (($dados['tipo_salario']??'') === 'faixa') {
        $salario = "De R$ " . $dados['salario_min'] . " até R$ " . $dados['salario_max'];
    } elseif (($dados['tipo_salario']??'') === 'minimo') {
        $salario = "A partir de R$ " . $dados['salario_min'];
    }

    $novoCandidato = [
        'id' => uniqid('cand_'),
        'nome' => strip_tags($dados['nome']),
        'email' => strip_tags($dados['email']),
        'telefone' => strip_tags($dados['telefone']),
        'linkedin' => strip_tags($dados['linkedin']),
        'salario_pretensao' => $salario,
        'areas' => $dados['areas'] ?? [],
        'aceita_presencial' => isset($dados['aceita_presencial']),
        'endereco' => $endereco,
        'arquivo_cv' => $nomeArquivo,
        'data_cadastro' => date('Y-m-d H:i:s'),
        'aceite_termos' => true
    ];

    array_unshift($candidatos, $novoCandidato);
    file_put_contents(CANDIDATOS_FILE, json_encode($candidatos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return ['sucesso' => true];
}

function carregarCandidatos() {
    inicializarSistema();
    if (!file_exists(CANDIDATOS_FILE)) return [];
    
    $json = file_get_contents(CANDIDATOS_FILE);
    $candidatos = json_decode($json, true) ?? [];
    
    // Ordena por data (mais recentes primeiro)
    usort($candidatos, function($a, $b) {
        return strtotime($b['data_cadastro']) - strtotime($a['data_cadastro']);
    });
    
    return $candidatos;
}

function getUrlCurriculo($arquivo) {
    if (empty($arquivo)) return '';
    $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $base = $protocolo . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
    return rtrim($base, '/') . '/' . UPLOADS_DIR . $arquivo;
}

// =============================================================================
// MÓDULO DE COMENTÁRIOS E MAGIC LINKS
// =============================================================================

function adicionarComentario($vagaId, $nome, $texto) {
    $vagas = carregarVagas();
    foreach ($vagas as &$v) {
        if ($v['id'] === $vagaId) {
            $v['comentarios'][] = [
                'id' => uniqid('c_'), 
                'nome' => strip_tags($nome), 
                'texto' => strip_tags($texto), 
                'data' => date('Y-m-d H:i:s'), 
                'status' => 'pendente'
            ];
            return salvarVagas($vagas);
        }
    }
    return false;
}

function moderarComentario($vid, $cid, $acao) {
    $vagas = carregarVagas();
    foreach ($vagas as &$v) {
        if ($v['id'] === $vid && isset($v['comentarios'])) {
            foreach ($v['comentarios'] as $k => &$c) {
                if ($c['id'] === $cid) {
                    if ($acao === 'aprovar') $c['status'] = 'aprovado';
                    if ($acao === 'excluir') unset($v['comentarios'][$k]);
                }
            }
            $v['comentarios'] = array_values($v['comentarios']);
            return salvarVagas($vagas);
        }
    }
    return false;
}

function gerarLinkEdicaoExterno($id) {
    $vagas = carregarVagas();
    $link = false;
    foreach ($vagas as &$v) {
        if ($v['id'] === $id) {
            $token = bin2hex(random_bytes(16));
            $v['token_edicao'] = $token;
            $v['token_expiracao'] = time() + (24 * 60 * 60); // 24h
            
            $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $base = $protocolo . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
            $link = rtrim($base, '/') . "/editar_publico.php?token=" . $token;
            break;
        }
    }
    if ($link) { salvarVagas($vagas); return $link; }
    return false;
}

function validarTokenEdicao($token) {
    if (!$token) return null;
    $vagas = carregarVagas();
    foreach ($vagas as $v) {
        if (isset($v['token_edicao']) && $v['token_edicao'] === $token) {
            if (time() <= $v['token_expiracao']) return $v;
        }
    }
    return null;
}

// =============================================================================
// HELPERS
// =============================================================================

function gerarLinkAplicacao($vaga) {
    $tipo = $vaga['tipo_contato'] ?? 'link';
    $valor = trim($vaga['contato_valor'] ?? '');

    if (empty($valor)) return '#';

    if ($tipo === 'whatsapp') {
        $num = preg_replace('/\D/', '', $valor);
        if (strlen($num) < 10) return '#'; 
        if (substr($num, 0, 2) !== '55') $num = '55' . $num;
        $text = urlencode("Olá, vi a vaga {$vaga['titulo']} no Blipei Vagas e tenho interesse.");
        return "https://wa.me/{$num}?text={$text}";
    } elseif ($tipo === 'email') {
        if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) return '#';
        $subject = urlencode("Candidatura: {$vaga['titulo']}");
        return "mailto:{$valor}?subject={$subject}";
    } else {
        if (!preg_match("~^(?:f|ht)tps?://~i", $valor)) $valor = "https://" . $valor;
        return filter_var($valor, FILTER_VALIDATE_URL) ? $valor : '#';
    }
}

function tempoRelativo($data) {
    $diff = time() - strtotime($data);
    if ($diff < 3600) return "Novo";
    if ($diff < 86400) return "Hoje";
    return date('d/m', strtotime($data));
}

function paginar($items, $pagina = 1, $porPagina = 10) {
    $total = count($items);
    $totalPaginas = ceil($total / $porPagina);
    $offset = ($pagina - 1) * $porPagina;
    return [
        'dados' => array_slice($items, $offset, $porPagina),
        'total' => $total,
        'total_paginas' => $totalPaginas
    ];
}

// Inicializa o sistema ao incluir o arquivo
inicializarSistema();
?>