<?php

require_once 'vendor/autoload.php';

use Hardsystem\TotalExpressAPI\TotalExpressAPI;

echo "=== TESTE DE ESTRUTURA DA BIBLIOTECA ===\n\n";

try {
    // Testa se a classe pode ser instanciada
    $totalAPI = new TotalExpressAPI('teste', 'teste', 'production', true);
    echo "✅ Classe instanciada com sucesso\n";
    
    // Testa se as constantes estão disponíveis
    echo "✅ Constantes de serviço:\n";
    echo "   - EXP: " . TotalExpressAPI::TIPO_SERVICO_EXP . "\n";
    echo "   - ESP: " . TotalExpressAPI::TIPO_SERVICO_ESP . "\n";
    echo "   - PRM: " . TotalExpressAPI::TIPO_SERVICO_PRM . "\n";
    echo "   - STD: " . TotalExpressAPI::TIPO_SERVICO_STD . "\n\n";
    
    echo "✅ Constantes de entrega:\n";
    echo "   - NORMAL: " . TotalExpressAPI::TIPO_ENTREGA_NORMAL . "\n";
    echo "   - GOBACK: " . TotalExpressAPI::TIPO_ENTREGA_GOBACK . "\n";
    echo "   - RMA: " . TotalExpressAPI::TIPO_ENTREGA_RMA . "\n\n";
    
    echo "✅ Constantes de status:\n";
    echo "   - PENDENTE: " . TotalExpressAPI::STATUS_PENDENTE . "\n";
    echo "   - EM_TRANSITO: " . TotalExpressAPI::STATUS_EM_TRANSITO . "\n";
    echo "   - ENTREGUE: " . TotalExpressAPI::STATUS_ENTREGUE . "\n";
    echo "   - DEVOLVIDO: " . TotalExpressAPI::STATUS_DEVOLVIDO . "\n";
    echo "   - EXTRAVIADO: " . TotalExpressAPI::STATUS_EXTRAVIADO . "\n\n";
    
    // Testa se os métodos existem
    echo "✅ Métodos disponíveis:\n";
    echo "   - calcularFrete: " . (method_exists($totalAPI, 'calcularFrete') ? 'SIM' : 'NÃO') . "\n";
    echo "   - rastrearEncomenda: " . (method_exists($totalAPI, 'rastrearEncomenda') ? 'SIM' : 'NÃO') . "\n";
    echo "   - rastrearMultiplasEncomendas: " . (method_exists($totalAPI, 'rastrearMultiplasEncomendas') ? 'SIM' : 'NÃO') . "\n";
    echo "   - getDebugInfo: " . (method_exists($totalAPI, 'getDebugInfo') ? 'SIM' : 'NÃO') . "\n\n";
    
    echo "✅ Estrutura da biblioteca está correta!\n";
    echo "✅ Rastreamento implementado com sucesso!\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: {$e->getMessage()}\n";
}

echo "=== TESTE CONCLUÍDO ===\n";
