<?php

require_once 'vendor/autoload.php';

use Hardsystem\TotalExpressAPI\TotalExpressAPI;

// Teste simples da API
$user = '';
$pass = '';

try {
    $totalAPI = new TotalExpressAPI($user, $pass, 'production', true);
    
    $params = [
        'tipo_servico' => TotalExpressAPI::TIPO_SERVICO_EXP,
        'cep_destino' => '11035-040',
        'peso' => 0.47,
        'valor_declarado' => 139.40
    ];
    
    $resultado = $totalAPI->calcularFrete($params);
    
    echo "✅ TESTE APROVADO!\n";
    echo "Prazo: {$resultado['prazo_texto']}\n";
    echo "Valor: {$resultado['valor_formatado']}\n";
    
} catch (Exception $e) {
    echo "❌ TESTE FALHOU: {$e->getMessage()}\n";
}
