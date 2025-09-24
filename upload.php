<?php
require_once 'PlanilhaProcessor.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['planilha'])) {
    $processor = new PlanilhaProcessor();
    
    // Limpar tabela antes de processar
    if ($processor->limparTabela()) {
        // Processar arquivo Excel
        $arquivo = $_FILES['planilha']['tmp_name'];
        
        // Usar PHPExcel ou PhpSpreadsheet para ler o arquivo
        require_once 'vendor/autoload.php';
        
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        $spreadsheet = $reader->load($arquivo);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $linhas_processadas = 0;
        $erros = 0;
        
        // Pular cabeçalhos (ajustar conforme necessidade)
        foreach ($worksheet->getRowIterator(10) as $row) { // Começar da linha 10
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(FALSE);
            
            $dados_linha = [];
            foreach ($cellIterator as $cell) {
                $dados_linha[] = $cell->getValue();
            }
            
            // Mapear para array associativo baseado nas colunas do Excel
            $dados = [
                'Código' => $dados_linha[0] ?? '',
                'Nome' => $dados_linha[2] ?? '',
                'Fornecedor' => $dados_linha[5] ?? '',
                'Localidade' => $dados_linha[8] ?? '',
                'Conta' => $dados_linha[9] ?? '',
                'Nº Documento' => $dados_linha[11] ?? '',
                'Dependência' => $dados_linha[13] ?? '',
                'Dt. Aquisição' => $dados_linha[16] ?? '',
                'Vl. Aquisição' => $dados_linha[18] ?? 0,
                'Vl. Deprec.' => $dados_linha[19] ?? 0,
                'Vl. Atual' => $dados_linha[21] ?? 0,
                'Status' => $dados_linha[25] ?? ''
            ];
            
            if ($processor->processarLinha($dados)) {
                $linhas_processadas++;
            } else {
                $erros++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Processamento concluído: $linhas_processadas linhas processadas, $erros erros"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao limpar tabela'
        ]);
    }
}
?>