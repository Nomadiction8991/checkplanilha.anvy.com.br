#!/bin/bash

# Script para adicionar autenticação em todos os arquivos PHP que precisam

# Lista de diretórios/arquivos que precisam de autenticação
CRUD_FILES=$(find /home/weverton/Documentos/Github/checkplanilha.anvy.com.br/CRUD -name "*.php" -not -name "conexao.php" -not -name "Relatorio141Generator.php" -type f)
VIEW_FILES=$(find /home/weverton/Documentos/Github/checkplanilha.anvy.com.br/app/views -name "*.php" -not -name "app-wrapper.php" -type f)
VIEW_FILES="$VIEW_FILES $(find /home/weverton/Documentos/Github/checkplanilha.anvy.com.br/VIEW -name "*.php" -type f)"

# Combinar todas as listas
ALL_FILES="$CRUD_FILES $VIEW_FILES"

# Contador
UPDATED=0
SKIPPED=0

for file in $ALL_FILES; do
    # Verificar se já tem require auth.php ou session_start
    if grep -q "require.*auth\.php" "$file" || grep -q "require_once.*auth\.php" "$file"; then
        echo "SKIP: $file (já tem autenticação)"
        ((SKIPPED++))
        continue
    fi
    
    # Calcular profundidade relativa à raiz
    DEPTH=$(echo "$file" | sed 's|/home/weverton/Documentos/Github/checkplanilha.anvy.com.br/||' | tr -cd '/' | wc -c)
    
    # Construir caminho relativo para auth.php
    if [ $DEPTH -eq 0 ]; then
        AUTH_PATH="auth.php"
    else
        AUTH_PATH=$(printf '../%.0s' $(seq 1 $DEPTH))"auth.php"
    fi
    
    # Criar backup
    cp "$file" "$file.bak"
    
    # Adicionar require auth.php após a primeira tag <?php
    sed -i '0,/<?php/s|<?php|<?php\nrequire_once '"'$AUTH_PATH'"'; // Autenticação|' "$file"
    
    echo "UPDATED: $file (depth: $DEPTH, path: $AUTH_PATH)"
    ((UPDATED++))
done

echo ""
echo "========================================="
echo "Resumo:"
echo "Arquivos atualizados: $UPDATED"
echo "Arquivos ignorados: $SKIPPED"
echo "========================================="
echo "Backups criados com extensão .bak"
