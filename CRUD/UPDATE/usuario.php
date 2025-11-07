<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

$id = $_GET['id'] ?? null;
$mensagem = '';
$tipo_mensagem = '';

if (!$id) {
    header('Location: ./read-usuario.php');
    exit;
}

// Buscar usuário
try {
    $stmt = $conexao->prepare('SELECT * FROM usuarios WHERE id = :id');
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $usuario = $stmt->fetch();

    if (!$usuario) {
        throw new Exception('Usuário não encontrado.');
    }
} catch (Exception $e) {
    $mensagem = 'Erro: ' . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Novos campos
    $cpf = trim($_POST['cpf'] ?? '');
    $rg = trim($_POST['rg'] ?? '');
    $rg_igual_cpf = isset($_POST['rg_igual_cpf']) ? 1 : 0;
    $telefone = trim($_POST['telefone'] ?? '');
    $tipo = trim($_POST['tipo'] ?? 'Administrador/Acessor');
    $assinatura = trim($_POST['assinatura'] ?? '');
    $casado = isset($_POST['casado']) ? 1 : 0;
    $nome_conjuge = trim($_POST['nome_conjuge'] ?? '');
    $cpf_conjuge = trim($_POST['cpf_conjuge'] ?? '');
    $rg_conjuge = trim($_POST['rg_conjuge'] ?? '');
    $rg_conjuge_igual_cpf = isset($_POST['rg_conjuge_igual_cpf']) ? 1 : 0;
    $telefone_conjuge = trim($_POST['telefone_conjuge'] ?? '');
    $assinatura_conjuge = trim($_POST['assinatura_conjuge'] ?? '');
    
    // Endereço
    $endereco_cep = trim($_POST['endereco_cep'] ?? '');
    $endereco_logradouro = trim($_POST['endereco_logradouro'] ?? '');
    $endereco_numero = trim($_POST['endereco_numero'] ?? '');
    $endereco_complemento = trim($_POST['endereco_complemento'] ?? '');
    $endereco_bairro = trim($_POST['endereco_bairro'] ?? '');
    $endereco_cidade = trim($_POST['endereco_cidade'] ?? '');
    $endereco_estado = trim($_POST['endereco_estado'] ?? '');

    try {
        // Validações
        if (empty($nome)) {
            throw new Exception('O nome é obrigatório.');
        }

        if (empty($email)) {
            throw new Exception('O email é obrigatório.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido.');
        }
        
        // Validar CPF (se preenchido)
        if (!empty($cpf)) {
            $cpf_numeros = preg_replace('/\D/', '', $cpf);
            if (strlen($cpf_numeros) !== 11) {
                throw new Exception('CPF inválido. Deve conter 11 dígitos.');
            }
            
            // Verificar se CPF já existe (exceto o próprio usuário)
            $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE cpf = :cpf AND id != :id');
            $stmt->bindValue(':cpf', $cpf);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            if ($stmt->fetch()) {
                throw new Exception('Este CPF já está cadastrado por outro usuário.');
            }
        }
        
        // Validar telefone (se preenchido)
        if (!empty($telefone)) {
            $telefone_numeros = preg_replace('/\D/', '', $telefone);
            if (strlen($telefone_numeros) < 10 || strlen($telefone_numeros) > 11) {
                throw new Exception('Telefone inválido. Deve conter 10 ou 11 dígitos.');
            }
        }

        // Formatação de RG (todos menos último + '-' + último)
        $formatarRg = function($valor){
            $d = preg_replace('/\D/','', $valor);
            if (strlen($d) <= 1) return $d; // um dígito sem hífen
            return substr($d,0,-1) . '-' . substr($d,-1);
        };
    if ($rg_igual_cpf) { $rg = $cpf; } else { $rg = $formatarRg($rg); }
        $rg_nums = preg_replace('/\D/','', $rg);
        if (strlen($rg_nums) < 2) { throw new Exception('O RG é obrigatório e deve ter ao menos 2 dígitos.'); }

        // Endereço obrigatório
        if (empty($endereco_cep) || empty($endereco_logradouro) || empty($endereco_numero) || empty($endereco_bairro) || empty($endereco_cidade) || empty($endereco_estado)) {
            throw new Exception('Todos os campos de endereço (CEP, logradouro, número, bairro, cidade e estado) são obrigatórios.');
        }

        // Assinatura obrigatória
        if (empty($assinatura)) {
            throw new Exception('A assinatura do usuário é obrigatória.');
        }

        // Se casado, validar dados completos do cônjuge
        if ($casado) {
            if (empty($nome_conjuge)) {
                throw new Exception('O nome do cônjuge é obrigatório.');
            }
            $cpf_conjuge_num = preg_replace('/\D/','', $cpf_conjuge);
            if (strlen($cpf_conjuge_num) !== 11) {
                throw new Exception('CPF do cônjuge inválido.');
            }
            $tel_conj_num = preg_replace('/\D/','', $telefone_conjuge);
            if (strlen($tel_conj_num) < 10 || strlen($tel_conj_num) > 11) {
                throw new Exception('Telefone do cônjuge inválido.');
            }
            if (empty($assinatura_conjuge)) {
                throw new Exception('A assinatura do cônjuge é obrigatória.');
            }
            if ($rg_conjuge_igual_cpf) { $rg_conjuge = $cpf_conjuge; } else if (!empty($rg_conjuge)) { $rg_conjuge = $formatarRg($rg_conjuge); }
            if (!empty($rg_conjuge)) {
                $rnums = preg_replace('/\D/','', $rg_conjuge);
                if (strlen($rnums) < 2) { throw new Exception('O RG do cônjuge deve ter ao menos 2 dígitos.'); }
            }
        } else {
            $nome_conjuge = $cpf_conjuge = $rg_conjuge = $telefone_conjuge = $assinatura_conjuge = '';
            $rg_conjuge_igual_cpf = 0;
        }

        // Verificar se email já existe (exceto o próprio usuário)
        $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE email = :email AND id != :id');
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('Este email já está cadastrado por outro usuário.');
        }

        // Atualizar dados
        if (!empty($senha)) {
            // Se senha foi informada, validar e atualizar
            if (strlen($senha) < 6) {
                throw new Exception('A senha deve ter no mínimo 6 caracteres.');
            }

            if ($senha !== $confirmar_senha) {
                throw new Exception('As senhas não conferem.');
            }

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET 
                    nome = :nome, 
                    email = :email, 
                    senha = :senha, 
                    ativo = :ativo,
                    cpf = :cpf,
            rg = :rg,
            rg_igual_cpf = :rg_igual_cpf,
                    telefone = :telefone,
                    tipo = :tipo,
                    assinatura = :assinatura,
                    endereco_cep = :endereco_cep,
                    endereco_logradouro = :endereco_logradouro,
                    endereco_numero = :endereco_numero,
                    endereco_complemento = :endereco_complemento,
                    endereco_bairro = :endereco_bairro,
                    endereco_cidade = :endereco_cidade,
            endereco_estado = :endereco_estado,
            casado = :casado,
            nome_conjuge = :nome_conjuge,
            cpf_conjuge = :cpf_conjuge,
            rg_conjuge = :rg_conjuge,
            telefone_conjuge = :telefone_conjuge,
        assinatura_conjuge = :assinatura_conjuge,
        rg_conjuge_igual_cpf = :rg_conjuge_igual_cpf
                    WHERE id = :id";
            $stmt = $conexao->prepare($sql);
            $stmt->bindValue(':senha', $senha_hash);
        } else {
            // Sem alteração de senha
        $sql = "UPDATE usuarios SET 
                    nome = :nome, 
                    email = :email, 
                    ativo = :ativo,
                    cpf = :cpf,
            rg = :rg,
            rg_igual_cpf = :rg_igual_cpf,
                    telefone = :telefone,
                    tipo = :tipo,
                    assinatura = :assinatura,
                    endereco_cep = :endereco_cep,
                    endereco_logradouro = :endereco_logradouro,
                    endereco_numero = :endereco_numero,
                    endereco_complemento = :endereco_complemento,
                    endereco_bairro = :endereco_bairro,
                    endereco_cidade = :endereco_cidade,
            endereco_estado = :endereco_estado,
            casado = :casado,
            nome_conjuge = :nome_conjuge,
            cpf_conjuge = :cpf_conjuge,
            rg_conjuge = :rg_conjuge,
            telefone_conjuge = :telefone_conjuge,
        assinatura_conjuge = :assinatura_conjuge,
        rg_conjuge_igual_cpf = :rg_conjuge_igual_cpf
                    WHERE id = :id";
            $stmt = $conexao->prepare($sql);
        }

        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':ativo', $ativo);
        $stmt->bindValue(':cpf', $cpf);
    $stmt->bindValue(':rg', $rg);
    $stmt->bindValue(':rg_igual_cpf', $rg_igual_cpf, PDO::PARAM_INT);
        $stmt->bindValue(':telefone', $telefone);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':assinatura', $assinatura);
        $stmt->bindValue(':endereco_cep', $endereco_cep);
        $stmt->bindValue(':endereco_logradouro', $endereco_logradouro);
        $stmt->bindValue(':endereco_numero', $endereco_numero);
        $stmt->bindValue(':endereco_complemento', $endereco_complemento);
        $stmt->bindValue(':endereco_bairro', $endereco_bairro);
        $stmt->bindValue(':endereco_cidade', $endereco_cidade);
        $stmt->bindValue(':endereco_estado', $endereco_estado);
    $stmt->bindValue(':casado', $casado, PDO::PARAM_INT);
    $stmt->bindValue(':nome_conjuge', $nome_conjuge);
    $stmt->bindValue(':cpf_conjuge', $cpf_conjuge);
    $stmt->bindValue(':rg_conjuge', $rg_conjuge);
    $stmt->bindValue(':telefone_conjuge', $telefone_conjuge);
    $stmt->bindValue(':assinatura_conjuge', $assinatura_conjuge);
    $stmt->bindValue(':rg_conjuge_igual_cpf', $rg_conjuge_igual_cpf, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        // Redirecionar para listagem com mensagem de sucesso
        header('Location: ./read-usuario.php?updated=1');
        exit;

    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}
?>
