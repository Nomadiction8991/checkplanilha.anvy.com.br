<?php
require_once '../CRUD/conexao.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $linhas_pular = (int)($_POST['linhas_pular'] ?? 25);
    $comum = trim($_POST['comum'] ?? 'D16');
    
    // Mapeamento simplificado
    $mapeamento = [
        'codigo' => strtoupper($_POST['codigo'] ?? 'A'),
        'nome' => strtoupper($_POST['nome'] ?? 'D'),
        'dependencia' => strtoupper($_POST['dependencia'] ?? 'P'),
    ];

    $mensagem = '';
    $tipo_mensagem = '';

    try {
        if (empty($comum)) {
            throw new Exception('O campo comum é obrigatório.');
        }

        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Selecione um arquivo CSV válido.');
        }

        $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
        $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));

        if ($extensao !== 'csv') {
            throw new Exception('Apenas arquivos CSV são permitidos.');
        }

        // Iniciar transação
        $conexao->beginTransaction();

        // Inserir a planilha na tabela planilhas
        $sql_planilha = "INSERT INTO planilhas (comum) VALUES (:comum)";
        $stmt_planilha = $conexao->prepare($sql_planilha);
        $stmt_planilha->bindValue(':comum', $comum);
        $stmt_planilha->execute();
        $id_planilha = $conexao->lastInsertId();

        // Salvar configurações de mapeamento na tabela config_planilha
        $mapeamento_string = '';
        foreach ($mapeamento as $coluna_banco => $letra_planilha) {
            $mapeamento_string .= "{$coluna_banco}={$letra_planilha};";
        }
        $mapeamento_string = rtrim($mapeamento_string, ';');

        $sql_config = "INSERT INTO config_planilha (id_planilha, pulo_linhas, mapeamento_colunas, comum) 
                      VALUES (:id_planilha, :pulo_linhas, :mapeamento_colunas, :comum)";
        $stmt_config = $conexao->prepare($sql_config);
        $stmt_config->bindValue(':id_planilha', $id_planilha);
        $stmt_config->bindValue(':pulo_linhas', $linhas_pular);
        $stmt_config->bindValue(':mapeamento_colunas', $mapeamento_string);
        $stmt_config->bindValue(':comum', $comum);
        $stmt_config->execute();

        // Processar o arquivo CSV
        $planilha = IOFactory::load($arquivo_tmp);
        $aba_ativa = $planilha->getActiveSheet();
        $linhas = $aba_ativa->toArray();

        $registros_importados = 0;
        $registros_erros = 0;
        $linha_atual = 0;
        $erro_detalhado = '';

        // Função para converter letra da coluna para índice numérico
        function colunaParaIndice($coluna) {
            $coluna = strtoupper($coluna);
            $indice = 0;
            $tamanho = strlen($coluna);
            
            for ($i = 0; $i < $tamanho; $i++) {
                $indice = $indice * 26 + (ord($coluna[$i]) - ord('A') + 1);
            }
            
            return $indice - 1;
        }

        // Função para corrigir encoding dos textos
        function corrigirEncoding($texto) {
            if (empty($texto)) return $texto;
            
            $encoding = mb_detect_encoding($texto, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            
            if ($encoding !== 'UTF-8') {
                $texto = mb_convert_encoding($texto, 'UTF-8', $encoding);
            }
            
            return $texto;
        }

        foreach ($linhas as $linha) {
            $linha_atual++;

            // Pular linhas iniciais
            if ($linha_atual <= $linhas_pular) {
                continue;
            }

            // Verificar se a linha está vazia
            if (empty(array_filter($linha, function($v) { return $v !== null && $v !== ''; }))) {
                continue;
            }

            try {
                // Obter valores baseado no mapeamento simplificado
                $indice_codigo = colunaParaIndice($mapeamento['codigo']);
                $codigo = isset($linha[$indice_codigo]) ? trim($linha[$indice_codigo]) : '';
                
                // Pular linha se não tiver código
                if (empty($codigo)) {
                    continue;
                }

                // Obter outros valores com correção de encoding
                $nome = isset($linha[colunaParaIndice($mapeamento['nome'])]) ? corrigirEncoding(trim($linha[colunaParaIndice($mapeamento['nome'])])) : '';
                $dependencia = isset($linha[colunaParaIndice($mapeamento['dependencia'])]) ? corrigirEncoding(trim($linha[colunaParaIndice($mapeamento['dependencia'])])) : '';

                // Inserir o produto (apenas campos necessários)
                $sql_produto = "INSERT INTO produtos 
                    (codigo, nome, dependencia, id_planilha) 
                VALUES 
                    (:codigo, :nome, :dependencia, :id_planilha)";

                $stmt_produto = $conexao->prepare($sql_produto);
                $stmt_produto->bindValue(':codigo', $codigo);
                $stmt_produto->bindValue(':nome', $nome);
                $stmt_produto->bindValue(':dependencia', $dependencia);
                $stmt_produto->bindValue(':id_planilha', $id_planilha);

                if ($stmt_produto->execute()) {
                    $registros_importados++;
                } else {
                    $registros_erros++;
                    $errorInfo = $stmt_produto->errorInfo();
                    $erro_detalhado = "Erro SQL: " . $errorInfo[2] . " na linha " . $linha_atual;
                    error_log($erro_detalhado);
                }

            } catch (Exception $e) {
                $registros_erros++;
                $erro_detalhado = "Erro na linha {$linha_atual}: " . $e->getMessage();
                error_log($erro_detalhado);
            }
        }

        if ($registros_importados === 0 && $registros_erros > 0) {
            throw new Exception("Nenhum registro foi importado. Erro: " . $erro_detalhado);
        }

        // Confirmar transação
        $conexao->commit();

        $mensagem = "Importação concluída com sucesso!<br>
                    Comum: {$comum}<br>
                    Registros importados: {$registros_importados}<br>
                    Erros: {$registros_erros}";
        
        if (!empty($erro_detalhado)) {
            $mensagem .= "<br><br>Detalhes do último erro:<br>" . $erro_detalhado;
        }
        
        $tipo_mensagem = 'success';

    } catch (Exception $e) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        $mensagem = "Erro na importação: " . $e->getMessage();
        $tipo_mensagem = 'error';
        
        error_log("ERRO IMPORTACAO: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());
    }
}
?>