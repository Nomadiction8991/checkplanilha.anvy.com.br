<?php
// Se for registro público, não exige autenticação
if (!defined('PUBLIC_REGISTER')) {
    require_once __DIR__ . '/../../auth.php'; // Autenticação apenas para admins
}

require_once __DIR__ . '/../conexao.php';

$mensagem = '';
$tipo_mensagem = '';

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
    
    // Estado civil e cônjuge
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

        if (empty($senha)) {
            throw new Exception('A senha é obrigatória.');
        }

        if (strlen($senha) < 6) {
            throw new Exception('A senha deve ter no mínimo 6 caracteres.');
        }

        if ($senha !== $confirmar_senha) {
            throw new Exception('As senhas não conferem.');
        }
        
        // Validar CPF (básico: apenas formato)
        if (empty($cpf)) {
            throw new Exception('O CPF é obrigatório.');
        }
        
        $cpf_numeros = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf_numeros) !== 11) {
            throw new Exception('CPF inválido. Deve conter 11 dígitos.');
        }
        
        // Função para formatar RG (todos menos último + '-' + último)
        $formatarRg = function($valor){
            $d = preg_replace('/\D/','', $valor);
            if (strlen($d) <= 1) return $d; // um dígito sem hífen
            return substr($d,0,-1) . '-' . substr($d,-1);
        };
        if ($rg_igual_cpf) {
            // Se RG igual CPF, mantém exatamente o CPF informado (com máscara) para RG
            $rg = $cpf;
        } else {
            $rg = $formatarRg($rg);
        }
        $rg_numeros = preg_replace('/\D/','', $rg);
        if (strlen($rg_numeros) < 2) {
            throw new Exception('O RG é obrigatório e deve ter ao menos 2 dígitos.');
        }

        // Validar telefone (básico: formato)
        if (empty($telefone)) {
            throw new Exception('O telefone é obrigatório.');
        }
        
        $telefone_numeros = preg_replace('/\D/', '', $telefone);
        if (strlen($telefone_numeros) < 10 || strlen($telefone_numeros) > 11) {
            throw new Exception('Telefone inválido.');
        }

        // Verificar se email já existe
        $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE email = :email');
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('Este email já está cadastrado.');
        }
        
        // Verificar se CPF já existe
        $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE cpf = :cpf');
        $stmt->bindValue(':cpf', $cpf);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('Este CPF já está cadastrado.');
        }

        // Endereço obrigatório (CEP, logradouro, numero, bairro, cidade, estado)
        if (empty($endereco_cep) || empty($endereco_logradouro) || empty($endereco_numero) || empty($endereco_bairro) || empty($endereco_cidade) || empty($endereco_estado)) {
            throw new Exception('Todos os campos de endereço (CEP, logradouro, número, bairro, cidade e estado) são obrigatórios.');
        }

        // Assinatura obrigatória
        if (empty($assinatura)) {
            throw new Exception('A assinatura do usuário é obrigatória.');
        }

        // Se casado, validar dados completos do cônjuge (nome, cpf, telefone, assinatura) e RG formatado se fornecido
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
            // RG do cônjuge
            if ($rg_conjuge_igual_cpf) {
                $rg_conjuge = $cpf_conjuge; // mantém máscara de CPF no RG do cônjuge
            } else if (!empty($rg_conjuge)) {
                $rg_conjuge = $formatarRg($rg_conjuge);
            }
            if (!empty($rg_conjuge)) {
                $rg_conj_nums = preg_replace('/\D/','', $rg_conjuge);
                if (strlen($rg_conj_nums) < 2) {
                    throw new Exception('O RG do cônjuge deve ter ao menos 2 dígitos.');
                }
            }
        } else {
            // Se não casado, limpar campos de cônjuge para evitar dados órfãos
            $nome_conjuge = $cpf_conjuge = $rg_conjuge = $telefone_conjuge = $assinatura_conjuge = '';
            $rg_conjuge_igual_cpf = 0;
        }

        // Criptografar senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Inserir usuário com todos os campos
        $sql = "INSERT INTO usuarios (
                    nome, email, senha, ativo, cpf, rg, rg_igual_cpf, telefone, tipo, assinatura,
                    endereco_cep, endereco_logradouro, endereco_numero, endereco_complemento,
                    endereco_bairro, endereco_cidade, endereco_estado,
                    casado, nome_conjuge, cpf_conjuge, rg_conjuge, rg_conjuge_igual_cpf, telefone_conjuge, assinatura_conjuge
                ) VALUES (
                    :nome, :email, :senha, :ativo, :cpf, :rg, :rg_igual_cpf, :telefone, :tipo, :assinatura,
                    :endereco_cep, :endereco_logradouro, :endereco_numero, :endereco_complemento,
                    :endereco_bairro, :endereco_cidade, :endereco_estado,
                    :casado, :nome_conjuge, :cpf_conjuge, :rg_conjuge, :rg_conjuge_igual_cpf, :telefone_conjuge, :assinatura_conjuge
                )";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':senha', $senha_hash);
        $stmt->bindValue(':ativo', $ativo, PDO::PARAM_INT);
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
    $stmt->bindValue(':rg_conjuge_igual_cpf', $rg_conjuge_igual_cpf, PDO::PARAM_INT);
    $stmt->bindValue(':telefone_conjuge', $telefone_conjuge);
    $stmt->bindValue(':assinatura_conjuge', $assinatura_conjuge);
        $stmt->execute();

        $mensagem = 'Usuário cadastrado com sucesso!';
        $tipo_mensagem = 'success';

        // Redirecionar após sucesso
        if (defined('PUBLIC_REGISTER')) {
            // Registro público (doador se cadastrando): redireciona para login
            header('Location: ../../../login.php?registered=1');
        } else {
            // Admin cadastrando usuário: redireciona para listagem, independente do tipo
            header('Location: ../../app/views/usuarios/read-usuario.php?success=1');
        }
        exit;

    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}
?>
