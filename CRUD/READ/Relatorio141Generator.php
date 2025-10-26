<?php
/**
 * Gerador de Relatórios 14.1
 * 
 * Classe helper para preencher o template do Relatório 14.1
 * com dados da planilha e produtos automaticamente
 */

class Relatorio141Generator {
    
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Gera relatório para uma planilha específica
     * 
     * @param int $id_planilha ID da planilha
     * @return array Dados formatados para o template
     */
    public function gerarRelatorio($id_planilha) {
        // Buscar dados da planilha
        $planilha = $this->buscarPlanilha($id_planilha);
        
        if (!$planilha) {
            throw new Exception("Planilha não encontrada");
        }
        
        // Buscar produtos da planilha
        $produtos = $this->buscarProdutos($id_planilha);
        
        // Formatar dados para o template
        return [
            'cnpj' => $planilha['cnpj'] ?? '',
            'numero_relatorio' => $planilha['numero_relatorio'] ?? $id_planilha,
            'casa_oracao' => $planilha['casa_oracao'] ?? '',
            'produtos' => $produtos
        ];
    }
    
    /**
     * Busca dados da planilha
     */
    private function buscarPlanilha($id_planilha) {
        $sql = "SELECT 
                    p.*,
                    p.cnpj,
                    p.numero_relatorio,
                    p.casa_oracao
                FROM planilhas p 
                WHERE p.id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id_planilha]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca produtos da planilha
     */
    private function buscarProdutos($id_planilha) {
        $sql = "SELECT 
                    p.codigo,
                    p.descricao,
                    p.obs,
                    p.marca,
                    p.modelo,
                    p.num_serie,
                    p.ano_fabric
                FROM produtos p
                WHERE p.id_planilha = :id_planilha
                ORDER BY p.codigo";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_planilha' => $id_planilha]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Renderiza o template preenchido
     */
    public function renderizar($id_planilha) {
        $dados = $this->gerarRelatorio($id_planilha);
        
        // Extrair variáveis para o template
        extract($dados);
        
        // Incluir o template
        ob_start();
        include __DIR__ . '/../app/views/planilhas/relatorio-14-1-template.php';
        return ob_get_clean();
    }
    
    /**
     * Gera relatório em branco para preenchimento manual
     */
    public function gerarEmBranco($num_paginas = 1) {
        $produtos = array_fill(0, $num_paginas, [
            'codigo' => '',
            'descricao' => '',
            'obs' => ''
        ]);
        
        return [
            'cnpj' => '',
            'numero_relatorio' => '',
            'casa_oracao' => '',
            'produtos' => $produtos
        ];
    }
}
