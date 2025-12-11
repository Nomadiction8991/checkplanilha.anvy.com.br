<?php
// Se for registro pÃºblico, nÃ£o exige autenticaÃ§Ã£o
if (!defined('PUBLIC_REGISTER')) {
     // AutenticaÃ§Ã£o apenas para admins
}

require_once dirname(__DIR__, 2) . '/bootstrap.php';

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
    
    // Estado civil e cÃ´njuge
    $casado = isset($_POST['casado']) ? 1 : 0;
    $nome_conjuge = trim($_POST['nome_conjuge'] ?? '');
    $cpf_conjuge = trim($_POST['cpf_conjuge'] ?? '');
    $rg_conjuge = trim($_POST['rg_conjuge'] ?? '');
    $rg_conjuge_igual_cpf = isset($_POST['rg_conjuge_igual_cpf']) ? 1 : 0;
    $telefone_conjuge = trim($_POST['telefone_conjuge'] ?? '');
    $assinatura_conjuge = trim($_POST['assinatura_conjuge'] ?? '');
    
    // EndereÃ§o
    $endereco_cep = trim($_POST['endereco_cep'] ?? '');
    $endereco_logradouro = trim($_POST['endereco_logradouro'] ?? '');
    $endereco_numero = trim($_POST['endereco_numero'] ?? '');
    $endereco_complemento = trim($_POST['endereco_complemento'] ?? '');
    $endereco_bairro = trim($_POST['endereco_bairro'] ?? '');
    $endereco_cidade = trim($_POST['endereco_cidade'] ?? '');
    $endereco_estado = trim($_POST['endereco_estado'] ?? '');

    try {
        // ValidaÃ§Ãµes
        if (empty($nome)) {
            throw new Exception('O nome Ã© obrigatÃ³rio.');
        }

        if (empty($email)) {
            throw new Exception('O email Ã© obrigatÃ³rio.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email invÃ¡lido.');
        }

        if (empty($senha)) {
            throw new Exception('A senha Ã© obrigatÃ³ria.');
        }

        if (strlen($senha) < 6) {
            throw new Exception('A senha deve ter no mÃ­nimo 6 caracteres.');
        }

        if ($senha !== $confirmar_senha) {
            throw new Exception('As senhas nÃ£o conferem.');
        }
        
        // Validar CPF (bÃ¡sico: apenas formato)
        if (empty($cpf)) {
            throw new Exception('O CPF Ã© obrigatÃ³rio.');
        }
        
        $cpf_numeros = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf_numeros) !== 11) {
            throw new Exception('CPF invÃ¡lido. Deve conter 11 dÃ­gitos.');
        }
        
        // FunÃ§Ã£o para formatar RG (todos menos Ãºltimo + '-' + Ãºltimo)
        $formatarRg = function($valor){
            $d = preg_replace('/\D/','', $valor);
            if (strlen($d) <= 1) return $d; // um dÃ­gito sem hÃ­fen
            return substr($d,0,-1) . '-' . substr($d,-1);
        };
        if ($rg_igual_cpf) {
            // Se RG igual CPF, mantÃ©m exatamente o CPF informado (com mÃ¡scara) para RG
            $rg = $cpf;
        } else {
            $rg = $formatarRg($rg);
        }
        $rg_numeros = preg_replace('/\D/','', $rg);
        if (strlen($rg_numeros) < 2) {
            throw new Exception('O RG Ã© obrigatÃ³rio e deve ter ao menos 2 dÃ­gitos.');
        }

        // Validar telefone (bÃ¡sico: formato)
        if (empty($telefone)) {
            throw new Exception('O telefone Ã© obrigatÃ³rio.');
        }
        
        $telefone_numeros = preg_replace('/\D/', '', $telefone);
        if (strlen($telefone_numeros) < 10 || strlen($telefone_numeros) > 11) {
            throw new Exception('Telefone invÃ¡lido.');
        }

        // Verificar se email jÃ¡ existe
        $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE email = :email');
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('Este email jÃ¡ estÃ¡ cadastrado.');
        }
        
        // Verificar se CPF jÃ¡ existe
        $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE cpf = :cpf');
        $stmt->bindValue(':cpf', $cpf);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('Este CPF jÃ¡ estÃ¡ cadastrado.');
        }

        // EndereÃ§o obrigatÃ³rio (CEP, logradouro, numero, bairro, cidade, estado)
        if (empty($endereco_cep) || empty($endereco_logradouro) || empty($endereco_numero) || empty($endereco_bairro) || empty($endereco_cidade) || empty($endereco_estado)) {
            throw new Exception('Todos os campos de endereÃ§o (CEP, logradouro, nÃºmero, bairro, cidade e estado) sÃ£o obrigatÃ³rios.');
        }

        // Assinatura obrigatÃ³ria
        if (empty($assinatura)) {
            throw new Exception('A assinatura do usuÃ¡rio Ã© obrigatÃ³ria.');
        }

        // Se casado, validar dados completos do cÃ´njuge (nome, cpf, telefone, assinatura) e RG formatado se fornecido
        if ($casado) {
            if (empty($nome_conjuge)) {
                throw new Exception('O nome do cÃ´njuge Ã© obrigatÃ³rio.');
            }
            $cpf_conjuge_num = preg_replace('/\D/','', $cpf_conjuge);
            if (strlen($cpf_conjuge_num) !== 11) {
                throw new Exception('CPF do cÃ´njuge invÃ¡lido.');
            }
            $tel_conj_num = preg_replace('/\D/','', $telefone_conjuge);
            if (strlen($tel_conj_num) < 10 || strlen($tel_conj_num) > 11) {
                throw new Exception('Telefone do cÃ´njuge invÃ¡lido.');
            }
            if (empty($assinatura_conjuge)) {
                throw new Exception('A assinatura do cÃ´njuge Ã© obrigatÃ³ria.');
            }
            // RG do cÃ´njuge
            if ($rg_conjuge_igual_cpf) {
                $rg_conjuge = $cpf_conjuge; // mantÃ©m mÃ¡scara de CPF no RG do cÃ´njuge
            } else if (!empty($rg_conjuge)) {
                $rg_conjuge = $formatarRg($rg_conjuge);
            }
            if (!empty($rg_conjuge)) {
                $rg_conj_nums = preg_replace('/\D/','', $rg_conjuge);
                if (strlen($rg_conj_nums) < 2) {
                    throw new Exception('O RG do cÃ´njuge deve ter ao menos 2 dÃ­gitos.');
                }
            }
        } else {
            // Se nÃ£o casado, limpar campos de cÃ´njuge para evitar dados Ã³rfÃ£os
            $nome_conjuge = $cpf_conjuge = $rg_conjuge = $telefone_conjuge = $assinatura_conjuge = '';
            $rg_conjuge_igual_cpf = 0;
        }

        // Criptografar senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Inserir usuÃ¡rio com todos os campos
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

        $mensagem = 'UsuÃ¡rio cadastrado com sucesso!';
        $tipo_mensagem = 'success';

        // Redirecionar apÃ³s sucesso
        if (defined('PUBLIC_REGISTER')) {
            // Registro pÃºblico (doador se cadastrando): redireciona para login
            header('Location: ../../../login.php?registered=1');
        } else {
            // Admin cadastrando usuÃ¡rio: redireciona para listagem, independente do tipo
            header('Location: ../../app/views/usuarios/usuarios_listar.php?success=1');
        }
        exit;

    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}
?>


