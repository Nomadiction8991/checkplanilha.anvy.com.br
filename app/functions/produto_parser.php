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

function pp_normaliza_char($char) {
    // Normaliza um único caractere SEM fazer trim (preserva espaços)
    $char = (string)$char;
    $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $char);
    if ($s === false) { $s = $char; }
    $s = strtoupper($s);
    return $s;
}

function pp_gerar_variacoes($str) {
    // Gera variações de uma string: plural/singular
    // Para frases compostas, aplica variações palavra por palavra nas palavras principais
    $str = trim($str);
    if ($str === '') return [];
    
    $str_norm = pp_normaliza($str);
    $variacoes = [$str_norm];
    
    // Separar em palavras
    $palavras = preg_split('/\s+/', $str_norm);
    
    if (count($palavras) === 1) {
        // Palavra única: aplicar singular <-> plural
        if (substr($str_norm, -1) === 'S' && strlen($str_norm) > 2) {
            // Remove S final (plural -> singular)
            $variacoes[] = substr($str_norm, 0, -1);
        } else {
            // Adiciona S (singular -> plural)
            $variacoes[] = $str_norm . 'S';
        }
    } else {
        // Frase composta: variar apenas a PRIMEIRA palavra substant iva (geralmente a principal)
        $primeira_palavra = $palavras[0];
        $resto = implode(' ', array_slice($palavras, 1));
        
        $primeira_variada = null;
        if (substr($primeira_palavra, -1) === 'S' && strlen($primeira_palavra) > 2) {
            $primeira_variada = substr($primeira_palavra, 0, -1);
        } else {
            $primeira_variada = $primeira_palavra . 'S';
        }
        
        if ($primeira_variada) {
            $variacoes[] = $primeira_variada . ' ' . $resto;
        }
    }
    
    return array_unique($variacoes);
}

function pp_match_fuzzy($str1, $str2) {
    // Compara duas strings considerando variações plural/singular
    $vars1 = pp_gerar_variacoes($str1);
    $vars2 = pp_gerar_variacoes($str2);
    
    foreach ($vars1 as $v1) {
        foreach ($vars2 as $v2) {
            if ($v1 === $v2) return true;
        }
    }
    return false;
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
        // Separar aliases por "/" e adicionar cada um individualmente
        $aliases = array_filter(array_map('trim', preg_split('/\s*\/\s*/', $desc)));
        
        // Gerar variações (plural/singular) para cada alias
        $aliases_expandidos = [];
        foreach ($aliases as $alias) {
            $variacoes = pp_gerar_variacoes($alias);
            $aliases_expandidos = array_merge($aliases_expandidos, $variacoes);
        }
        
        $aliases_norm = array_unique($aliases_expandidos);
        $tipos_aliases[] = [
            'id' => (int)$tb['id'],
            'codigo' => (int)$tb['codigo'],
            'descricao' => $desc,
            'aliases' => $aliases_norm,
            'aliases_originais' => array_map('pp_normaliza', $aliases), // manter originais para validação
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

function pp_extrair_ben_complemento($texto, array $tipo_aliases = null, $aliases_originais = null, $tipo_descricao = null) {
    $texto = trim($texto);
    
    // Regra 0: Remover descrição completa do tipo SE ela aparecer no início E for seguida por um alias repetido
    // Ex: "PRATELEIRA / ESTANTE ESTANTE METÁLICA..." -> "ESTANTE ESTANTE METÁLICA..."
    // Mas NÃO remover em: "PRATELEIRA / ESTANTE ARMÁRIO..." (sem repetição)
    if ($tipo_descricao && !empty($tipo_descricao) && $aliases_originais) {
        $tipo_desc_norm = pp_normaliza($tipo_descricao);
        $texto_norm = pp_normaliza($texto);
        
        // Verificar se texto começa com tipo desc
        if (strpos($texto_norm, $tipo_desc_norm) === 0) {
            // Pegar o texto DEPOIS da descrição do tipo
            $texto_apos_tipo = trim(substr($texto, strlen($tipo_descricao)));
            $texto_apos_tipo = preg_replace('/^[\s\-–—\/]+/u', '', $texto_apos_tipo);
            $texto_apos_norm = pp_normaliza($texto_apos_tipo);
            
            // Verificar se algum alias aparece no início do texto APÓS o tipo desc
            $alias_no_inicio = false;
            foreach ($aliases_originais as $alias_orig) {
                $pattern = '/^' . preg_quote($alias_orig, '/') . '\b/iu';
                if (preg_match($pattern, $texto_apos_norm)) {
                    $alias_no_inicio = true;
                    break;
                }
            }
            
            // Se houver um alias no início após o tipo desc, remover o tipo desc
            if ($alias_no_inicio) {
                $texto = $texto_apos_tipo;
            }
        }
    }
    
    // Regra 1: separador explícito " - "
    if (preg_match('/^(.+?)\s+\-\s+(.+)$/u', $texto, $m)) {
        return [trim($m[1]), trim($m[2])];
    }
    // Regra 2: tentar detectar BEN pelos aliases INDIVIDUAIS
    if ($tipo_aliases && !empty($tipo_aliases)) {
        $texto_norm = pp_normaliza($texto);
        
        // Estratégia inteligente: verificar qual alias aparece REPETIDO no texto
        // Ex: "ESTANTE ESTANTE METALICA" -> ESTANTE aparece 2x (palavras completas)
        // Ex: "QUADRO MUSICAL QUADRO MUSICAL LOUSA" -> QUADRO MUSICAL aparece 2x
        $alias_repetido_norm = null;
        $max_repeticoes = 0;
        
        if ($aliases_originais) {
            foreach ($aliases_originais as $alias_orig) {
                // Contar ocorrências de palavras completas deste alias
                $pattern = '/\b' . preg_quote($alias_orig, '/') . '\b/iu';
                $count = preg_match_all($pattern, $texto_norm, $matches);
                
                if ($count > $max_repeticoes) {
                    $max_repeticoes = $count;
                    $alias_repetido_norm = $alias_orig; // já está normalizado
                }
            }
        }
        
        // Ordenar aliases: repetido primeiro (se repeticoes > 1), depois por tamanho (maior primeiro)
        $aliases_ordenados = $tipo_aliases;
        usort($aliases_ordenados, function($a, $b) use ($alias_repetido_norm, $max_repeticoes) {
            // Alias repetido tem prioridade máxima (se realmente repetido, > 1)
            if ($alias_repetido_norm && $max_repeticoes > 1) {
                if ($a === $alias_repetido_norm) return -1;
                if ($b === $alias_repetido_norm) return 1;
            }
            // Senão, maior primeiro
            return strlen($b) - strlen($a);
        });
        
        // Tentar extrair BEN usando cada alias (com suporte a variações plural/singular)
        foreach ($aliases_ordenados as $alias_norm) {
            if ($alias_norm === '') continue;
            
            // Gerar variações do alias (plural/singular)
            $variacoes_alias = pp_gerar_variacoes($alias_norm);
            
            foreach ($variacoes_alias as $variacao) {
                // Verificar se essa variação aparece no INÍCIO do texto NORMALIZADO como palavra completa
                $pattern = '/^(' . preg_quote($variacao, '/') . ')(\s|$)/iu';
                if (preg_match($pattern, $texto_norm, $m)) {
                    // Match encontrado! Agora precisamos encontrar onde termina no texto original
                    // Estratégia: normalizar o texto original caractere por caractere até acumular
                    // o mesmo comprimento normalizado que o match
                    
                    $match_len = mb_strlen($m[1]);
                    $acumulado_norm = '';
                    $pos_orig = 0;
                    $texto_len = mb_strlen($texto);
                    
                    while (mb_strlen($acumulado_norm) < $match_len && $pos_orig < $texto_len) {
                        $char = mb_substr($texto, $pos_orig, 1);
                        $char_norm = pp_normaliza_char($char);  // Usar versão sem trim
                        $acumulado_norm .= $char_norm;
                        $pos_orig++;
                    }
                    
                    $ben = trim(mb_substr($texto, 0, $pos_orig));
                    $resto = trim(mb_substr($texto, $pos_orig));
                    
                    // Remover separadores iniciais do resto
                    $resto = preg_replace('/^[\s\-–—\/]+/u', '', $resto);
                    
                    // Remover outros aliases que apareçam sequencialmente no início do resto
                    $removeu_algo = true;
                    while ($removeu_algo && $resto !== '') {
                        $removeu_algo = false;
                        $resto_norm = pp_normaliza($resto);
                        
                        foreach ($aliases_ordenados as $outro_alias) {
                            if ($outro_alias === '' || $outro_alias === $alias_norm) continue;
                            
                            $variacoes_outro = pp_gerar_variacoes($outro_alias);
                            foreach ($variacoes_outro as $var_outro) {
                                $pattern_outro = '/^(' . preg_quote($var_outro, '/') . ')(\s|$)/iu';
                                if (preg_match($pattern_outro, $resto_norm, $m2)) {
                                    // Mesmo processo
                                    $match_len2 = mb_strlen($m2[1]);
                                    $acumulado_norm2 = '';
                                    $pos_orig2 = 0;
                                    $resto_len = mb_strlen($resto);
                                    
                                    while (mb_strlen($acumulado_norm2) < $match_len2 && $pos_orig2 < $resto_len) {
                                        $char2 = mb_substr($resto, $pos_orig2, 1);
                                        $char_norm2 = pp_normaliza_char($char2);  // Usar versão sem trim
                                        $acumulado_norm2 .= $char_norm2;
                                        $pos_orig2++;
                                    }
                                    
                                    $resto = trim(mb_substr($resto, $pos_orig2));
                                    $resto = preg_replace('/^[\s\-–—\/]+/u', '', $resto);
                                    $removeu_algo = true;
                                    break 2;
                                }
                            }
                        }
                    }
                    
                    return [$ben, $resto];
                }
            }
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
