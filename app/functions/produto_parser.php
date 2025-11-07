<?php

// Funções utilitárias para parsing de produtos na importação

function pp_normaliza($str) {
    $str = (string)$str;
    $str = trim($str);
    $str = preg_replace('/\s+/', ' ', $str);
    // remover acentos com iconv (robusto o suficiente aqui)
    $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
    if ($s === false) { $s = $str; }
    $s = strtoupper($s);
    return $s;
}

function pp_colunaParaIndice($coluna) {
    $coluna = strtoupper($coluna);
    $indice = 0;
    $tamanho = strlen($coluna);
    for ($i = 0; $i < $tamanho; $i++) {
        $indice = $indice * 26 + (ord($coluna[$i]) - ord('A') + 1);
    }
    return $indice - 1;
}

function pp_extrair_codigo_prefixo($texto) {
    // retorna [codigo_detectado|null, texto_sem_prefixo]
    $codigo_detectado = null;
    if (preg_match('/^\s*(\d{1,3})(?:[\.,]\d+)?\s*\-\s*/u', $texto, $m)) {
        $codigo_detectado = (int)$m[1];
        $texto = preg_replace('/^\s*' . preg_quote($m[0], '/') . '/u', '', $texto);
    } else if (preg_match('/^\s*OT-?\d+\s*\-\s*/iu', $texto)) {
        $texto = preg_replace('/^\s*OT-?\d+\s*\-\s*/iu', '', $texto);
    }
    return [$codigo_detectado, trim($texto)];
}

function pp_construir_aliases_tipos(array $tipos_bens) {
    $tipos_aliases = [];
    foreach ($tipos_bens as $tb) {
        $desc = (string)$tb['descricao'];
        $aliases = array_filter(array_map('trim', preg_split('/\s*\/\s*/', $desc)));
        $aliases[] = $desc; // inclui completo
        $aliases_norm = array_unique(array_map('pp_normaliza', $aliases));
        $tipos_aliases[] = [
            'id' => (int)$tb['id'],
            'codigo' => (int)$tb['codigo'],
            'descricao' => $desc,
            'aliases' => $aliases_norm,
        ];
    }
    return $tipos_aliases;
}

function pp_detectar_tipo($texto, $codigo_detectado, array $tipos_aliases) {
    $tipo = ['id'=>0,'codigo'=>null,'descricao'=>null,'alias_usado'=>null];
    $texto_norm = pp_normaliza($texto);
    if ($codigo_detectado !== null) {
        foreach ($tipos_aliases as $tb) {
            if ((int)$tb['codigo'] === (int)$codigo_detectado) {
                $tipo = ['id'=>$tb['id'],'codigo'=>$tb['codigo'],'descricao'=>$tb['descricao'],'alias_usado'=>null];
                return [$tipo, $texto];
            }
        }
    }
    // por alias
    $melhor = null;
    foreach ($tipos_aliases as $tb) {
        foreach ($tb['aliases'] as $alias_norm) {
            if ($alias_norm !== '' && strpos($texto_norm, $alias_norm) === 0) {
                $len = strlen($alias_norm);
                if (!$melhor || $len > $melhor['len']) {
                    $melhor = ['len'=>$len,'tb'=>$tb,'alias'=>$alias_norm];
                }
            }
        }
    }
    if ($melhor) {
        // não remover o alias do texto; apenas registrar o tipo e o alias usado
        $tipo = ['id'=>$melhor['tb']['id'],'codigo'=>$melhor['tb']['codigo'],'descricao'=>$melhor['tb']['descricao'],'alias_usado'=>$melhor['alias']];
    }
    return [$tipo, trim($texto)];
}

function pp_extrair_ben_complemento($texto, array $tipo_aliases = null) {
    $texto = trim($texto);
    // Regra 1: separador explicito " - "
    if (preg_match('/^(.+?)\s+\-\s+(.+)$/u', $texto, $m)) {
        return [trim($m[1]), trim($m[2])];
    }
    // Regra 2: alias no início
    if ($tipo_aliases && !empty($tipo_aliases)) {
        $texto_norm = pp_normaliza($texto);
        $melhor = null;
        foreach ($tipo_aliases as $alias_norm) {
            if ($alias_norm !== '' && strpos($texto_norm, $alias_norm) === 0) {
                $len = strlen($alias_norm);
                if (!$melhor || $len > $melhor['len']) { $melhor = ['len'=>$len,'alias'=>$alias_norm]; }
            }
        }
        if ($melhor) {
            $ben_chars = 0;
            for ($i=0; $i<mb_strlen($texto); $i++) {
                $parte = mb_substr($texto, 0, $i+1);
                if (pp_normaliza($parte) === $melhor['alias']) { $ben_chars = $i+1; break; }
            }
            $ben = trim(mb_substr($texto, 0, $ben_chars));
            $resto = trim(mb_substr($texto, $ben_chars));
            // remover separadores e outros aliases sequenciais
            $resto = preg_replace('/^[\s\-–—\/]+/u', '', $resto);
            $resto_norm = pp_normaliza($resto);
            $continua = true;
            while ($continua && $resto !== '') {
                $continua = false;
                foreach ($tipo_aliases as $alias_norm) {
                    if ($alias_norm === '' || $alias_norm === $melhor['alias']) continue;
                    if (strpos($resto_norm, $alias_norm) === 0) {
                        // remove esse alias
                        $alias_chars = 0;
                        for ($i=0; $i<mb_strlen($resto); $i++) {
                            $parte = mb_substr($resto, 0, $i+1);
                            if (pp_normaliza($parte) === $alias_norm) { $alias_chars = $i+1; break; }
                        }
                        if ($alias_chars > 0) {
                            $resto = trim(mb_substr($resto, $alias_chars));
                            $resto = preg_replace('/^[\s\-–—\/]+/u', '', $resto);
                            $resto_norm = pp_normaliza($resto);
                            $continua = true;
                            break;
                        }
                    }
                }
            }
            return [$ben, trim($resto)];
        }
    }
    // Regra 3: fallback -> tudo é BEN
    return [$texto, ''];
}

function pp_remover_ben_do_complemento($ben, $complemento) {
    if ($ben === '' || $complemento === '') return $complemento;
    $comp = $complemento;
    $ben_q = preg_quote($ben, '/');
    // Remover apenas BEN no início seguido de separador, preservando ocorrências internas para evitar perda de informação
    $comp = preg_replace('/^' . $ben_q . '(\s+|\/|\-|:)+/u', '', $comp);
    $comp = trim(preg_replace('/\s+/', ' ', $comp));
    return $comp;
}

function pp_aplicar_sinonimos($ben, $complemento, $tipo_desc, array $config) {
    $ben_norm = pp_normaliza($ben);
    $comp_norm = pp_normaliza($complemento);
    $tipo_norm = pp_normaliza((string)$tipo_desc);
    // regras por tipo
    if (!empty($config['synonyms'])) {
        foreach ($config['synonyms'] as $tipo_key => $map) {
            if (pp_normaliza($tipo_key) === $tipo_norm) {
                foreach ($map as $from => $to) {
                    if (strpos($comp_norm, pp_normaliza($from)) !== false) {
                        $ben = $to; $ben_norm = pp_normaliza($ben); break 2;
                    }
                }
            }
        }
    }
    // regras globais
    if (!empty($config['global_synonyms'])) {
        foreach ($config['global_synonyms'] as $from => $to) {
            if (strpos($comp_norm, pp_normaliza($from)) !== false) {
                $ben = $to; $ben_norm = pp_normaliza($ben); break;
            }
        }
    }
    // se ben alterado, limpar novamente do complemento
    $complemento = pp_remover_ben_do_complemento(strtoupper($ben), strtoupper($complemento));
    return [$ben, $complemento];
}

function pp_forcar_ben_em_aliases($ben, $tipo_desc, $alias_usado = null) {
    // Garantir que o BEN seja uma das opções listadas em tipos_bens.descricao (separadas por '/')
    // Compara por forma normalizada, mas retorna a forma em caixa alta como aparece na descrição do tipo
    $tokens = array_map('trim', preg_split('/\s*\/\s*/', (string)$tipo_desc));
    $tokens_upper = array_map(function($t){ return strtoupper($t); }, $tokens);
    $tokens_norm = array_map('pp_normaliza', $tokens);
    $ben_norm = pp_normaliza($ben);

    // 1) Se BEN já pertence aos aliases por normalização, retorna o token correspondente (em upper)
    foreach ($tokens_norm as $i => $t_norm) {
        if ($t_norm !== '' && $t_norm === $ben_norm) {
            return $tokens_upper[$i];
        }
    }
    // 2) Se alias_usado existir, tentar mapear para um dos tokens
    if (!empty($alias_usado)) {
        $alias_norm = pp_normaliza($alias_usado);
        foreach ($tokens_norm as $i => $t_norm) {
            if ($t_norm !== '' && $t_norm === $alias_norm) {
                return $tokens_upper[$i];
            }
        }
    }
    // 3) Fallback: primeiro token válido; se vazio, mantém o BEN em upper
    foreach ($tokens_upper as $tok) {
        if (trim($tok) !== '') return strtoupper($tok);
    }
    return strtoupper($ben);
}

function pp_montar_descricao($qtd, $tipo_codigo, $tipo_desc, $ben, $comp, $dep, array $config) {
    // Formato condicional:
    // - Se BEN vazio: 1x [TIPO] COMPLEMENTO (DEPENDENCIA)
    // - Se BEN preenchido: 1x [TIPO] BEN - COMPLEMENTO (DEPENDENCIA)
    // Não remover informações; manter tudo do complemento original
    $brackets = '?';
    if (!empty($tipo_codigo) && !empty($tipo_desc)) {
        $brackets = sprintf('%d - %s', (int)$tipo_codigo, strtoupper($tipo_desc));
    }
    $ben = strtoupper(trim($ben));
    $comp = strtoupper(trim($comp));
    $dep = strtoupper(trim((string)$dep));
    
    // Montar descrição base
    $desc = sprintf('%dx [%s]', (int)$qtd, $brackets);
    
    // Se BEN preenchido, adiciona BEN - COMPLEMENTO; senão, só COMPLEMENTO
    if ($ben !== '') {
        $desc .= ' ' . $ben;
        if ($comp !== '') {
            $desc .= ' - ' . $comp;
        }
    } else {
        // BEN vazio: vai direto pro complemento
        if ($comp !== '') {
            $desc .= ' ' . $comp;
        } else {
            // nem BEN nem complemento: fallback
            $desc .= ' SEM DESCRICAO';
        }
    }
    
    // Dependência sempre com espaço antes
    if ($dep !== '') {
        $desc .= ' (' . $dep . ')';
    }
    
    return $desc;
}

?>
