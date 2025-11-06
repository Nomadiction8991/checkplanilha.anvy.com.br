<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/functions/comum_functions.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Redirecionamento após sucesso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $arquivo_csv = $_FILES['arquivo_csv'] ?? null;
    $posicao_comum = trim($_POST['posicao_comum'] ?? 'D16');
    $posicao_data = trim($_POST['posicao_data'] ?? 'D13');
    $posicao_cnpj = trim($_POST['posicao_cnpj'] ?? 'U5');
    $pulo_linhas = (int)($_POST['pulo_linhas'] ?? 25);
    $mapeamento_codigo = strtoupper(trim($_POST['mapeamento_codigo'] ?? 'A'));
    $mapeamento_complemento = strtoupper(trim($_POST['mapeamento_complemento'] ?? 'D'));
    $mapeamento_dependencia = strtoupper(trim($_POST['mapeamento_dependencia'] ?? 'P'));

    $mensagem = '';
    $tipo_mensagem = '';
    $sucesso = false;

    try {
        // Validações
        if (!$arquivo_csv || $arquivo_csv['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Selecione um arquivo CSV válido.');
        }

        $extensao = strtolower(pathinfo($arquivo_csv['name'], PATHINFO_EXTENSION));
        if ($extensao !== 'csv') {
            throw new Exception('Apenas arquivos CSV são permitidos.');
        }

        // Carregar arquivo
        $planilha = IOFactory::load($arquivo_csv['tmp_name']);
        $aba = $planilha->getActiveSheet();

        // Obter valores das células
        $valor_comum = trim($aba->getCell($posicao_comum)->getCalculatedValue());
        $valor_data = trim($aba->getCell($posicao_data)->getCalculatedValue());
        $valor_cnpj = trim($aba->getCell($posicao_cnpj)->getCalculatedValue());

        if (empty($valor_comum)) {
            throw new Exception('A célula ' . $posicao_comum . ' está vazia.');
        }

        // Converter data
        $data_mysql = null;
        if (!empty($valor_data)) {
            if (is_numeric($valor_data)) {
                $data_mysql = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor_data)->format('Y-m-d');
            } else {
                $ts = strtotime($valor_data);
                if ($ts !== false) {
                    $data_mysql = date('Y-m-d', $ts);
                }
            }
        }

        // Converter CNPJ
        $cnpj_limpo = preg_replace('/[^0-9]/', '', $valor_cnpj);

        // Iniciar transação
        $conexao->beginTransaction();

        // Procesar comum e obter ID (pode criar ou atualizar)
        $dados_comum = [
            'cnpj' => $cnpj_limpo
        ];
        $comum_processado_id = processar_comum($conexao, $valor_comum, $dados_comum);
        if (!$comum_processado_id) {
            throw new Exception('Erro ao processar comum.');
        }

        // Criar nova planilha vinculada ao comum
        $sql_planilha = "INSERT INTO planilhas (comum_id, posicao_cnpj, posicao_comum, posicao_data, pulo_linhas, mapeamento_colunas, data_posicao, ativo) 
                        VALUES (:comum_id, :posicao_cnpj, :posicao_comum, :posicao_data, :pulo_linhas, :mapeamento_colunas, :data_posicao, 1)";
        $stmt = $conexao->prepare($sql_planilha);
        $stmt->bindValue(':comum_id', $comum_processado_id, PDO::PARAM_INT);
        $stmt->bindValue(':posicao_cnpj', $posicao_cnpj);
        $stmt->bindValue(':posicao_comum', $posicao_comum);
        $stmt->bindValue(':posicao_data', $posicao_data);
    $stmt->bindValue(':pulo_linhas', $pulo_linhas);
    $stmt->bindValue(':mapeamento_colunas', "codigo=$mapeamento_codigo;complemento=$mapeamento_complemento;dependencia=$mapeamento_dependencia");
        $stmt->bindValue(':data_posicao', $data_mysql);
        $stmt->execute();
        $id_planilha = $conexao->lastInsertId();

        // Processar linhas do CSV
        $linhas = $aba->toArray();
        $registros_importados = 0;
        $registros_erros = 0;
        $linha_atual = 0;

        function colunaParaIndice($coluna) {
            $coluna = strtoupper($coluna);
            $indice = 0;
            $tamanho = strlen($coluna);
            for ($i = 0; $i < $tamanho; $i++) {
                $indice = $indice * 26 + (ord($coluna[$i]) - ord('A') + 1);
            }
            return $indice - 1;
        }

        $idx_codigo = colunaParaIndice($mapeamento_codigo);
    $idx_complemento = colunaParaIndice($mapeamento_complemento);
        $idx_dependencia = colunaParaIndice($mapeamento_dependencia);

        foreach ($linhas as $linha) {
            $linha_atual++;

            if ($linha_atual <= $pulo_linhas) {
                continue;
            }

            if (empty(array_filter($linha))) {
                continue;
            }

            try {
                $codigo = isset($linha[$idx_codigo]) ? trim($linha[$idx_codigo]) : '';
                if (empty($codigo)) {
                    continue;
                }

                $complemento = isset($linha[$idx_complemento]) ? trim($linha[$idx_complemento]) : '';
                $dependencia = isset($linha[$idx_dependencia]) ? trim($linha[$idx_dependencia]) : '';

                $sql_produto = "INSERT INTO produtos (planilha_id, codigo, descricao_completa, editado_descricao_completa, tipo_ben_id, editado_tipo_ben_id, ben, editado_ben, complemento, editado_complemento, dependencia_id, editado_dependencia_id, chacado, editado, imprimir_etiqueta, imprimir_14_1, observacao, ativo) 
                               VALUES (:planilha_id, :codigo, '', '', 0, 0, '', '', :complemento, '', 0, 0, 0, 0, 0, 0, '', 1)";
                $stmt_prod = $conexao->prepare($sql_produto);
                $stmt_prod->bindValue(':planilha_id', $id_planilha, PDO::PARAM_INT);
                $stmt_prod->bindValue(':codigo', $codigo);
                $stmt_prod->bindValue(':complemento', $complemento);
                if ($stmt_prod->execute()) {
                    $registros_importados++;
                } else {
                    $registros_erros++;
                }
            } catch (Exception $e) {
                $registros_erros++;
                error_log("Erro linha $linha_atual: " . $e->getMessage());
            }
        }

        if ($registros_importados === 0 && $registros_erros > 0) {
            throw new Exception("Nenhum registro foi importado.");
        }

        $conexao->commit();
        $mensagem = "Importação concluída! {$registros_importados} produtos importados.";
        $tipo_mensagem = 'success';
        $sucesso = true;

    } catch (Exception $e) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        $mensagem = "Erro: " . $e->getMessage();
        $tipo_mensagem = 'error';
        error_log("ERRO IMPORTACAO: " . $e->getMessage());
    }

    // Redirecionar com mensagem
    if ($sucesso && !empty($comum_processado_id)) {
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['tipo_mensagem'] = 'success';
        header('Location: ../../app/views/comuns/listar-planilhas.php?comum_id=' . (int)$comum_processado_id);
        exit;
    }
}

// Se for GET, redirecionar de volta
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
    exit;
}
?>
