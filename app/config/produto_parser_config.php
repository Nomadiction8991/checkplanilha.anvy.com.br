<?php

return [
    // 'space' = sem hifens entre partes; 'hyphen' = usa ' - ' entre BEN e COMPLEMENTO
    'description_format' => 'space',
    'remove_hyphens' => true,

    // Regras por tipo: chave é a descrição do tipo (como em tipos_bens.descricao)
    'synonyms' => [
        'PRATELEIRA / ESTANTE' => [
            'ARMARIO' => 'PRATELEIRA',
            'ARMÁRIO' => 'PRATELEIRA',
            'ARMARIOS' => 'PRATELEIRA',
            'ARMÁRIOS' => 'PRATELEIRA',
        ],
    ],

    // Regras globais aplicadas a todos os tipos
    'global_synonyms' => [
        // 'GUARDA ROUPA' => 'ARMARIO',
    ],
];
