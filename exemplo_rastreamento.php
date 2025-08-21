<?php

require_once 'vendor/autoload.php';

use Hardsystem\TotalExpressAPI\TotalExpressAPI;

// Configurações da API
$user = 'distridomquixo-prod';
$pass = 'OLK31qY14K';

try {
    // Inicializa a API
    $totalAPI = new TotalExpressAPI($user, $pass, 'production', true);
    
    echo "=== EXEMPLO DE RASTREAMENTO TOTAL EXPRESS ===\n\n";
    
    // Exemplo 1: Rastreamento de uma encomenda
    echo "1. Rastreamento de uma encomenda:\n";
    
    // Código de exemplo (você deve substituir por um código real)
    $codigoRastreamento = '1234567890'; // Substitua por um código real
    
    try {
        $resultado = $totalAPI->rastrearEncomenda($codigoRastreamento);
        
        echo "   Código: {$resultado['codigo_rastreamento']}\n";
        echo "   Status: {$resultado['status_texto']}\n";
        echo "   Destinatário: {$resultado['destinatario']}\n";
        echo "   Data de Entrega: {$resultado['data_entrega']}\n";
        echo "   Total de Eventos: {$resultado['total_eventos']}\n\n";
        
        if (!empty($resultado['eventos'])) {
            echo "   Eventos:\n";
            foreach ($resultado['eventos'] as $index => $evento) {
                $numero = $index + 1;
                echo "     {$numero}. {$evento['data']} {$evento['hora']} - {$evento['local']}\n";
                echo "        Status: {$evento['status']}\n";
                if ($evento['observacao']) {
                    echo "        Obs: {$evento['observacao']}\n";
                }
                echo "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ Erro: {$e->getMessage()}\n\n";
    }
    
    // Exemplo 2: Rastreamento de múltiplas encomendas
    echo "2. Rastreamento de múltiplas encomendas:\n";
    
    $codigos = [
        '1234567890', // Substitua por códigos reais
        '0987654321',
        '1122334455'
    ];
    
    try {
        $resultados = $totalAPI->rastrearMultiplasEncomendas($codigos);
        
        foreach ($resultados as $resultado) {
            echo "   Código: {$resultado['codigo']}\n";
            
            if ($resultado['sucesso']) {
                $dados = $resultado['dados'];
                echo "   ✅ Status: {$dados['status_texto']}\n";
                echo "   Destinatário: {$dados['destinatario']}\n";
                echo "   Eventos: {$dados['total_eventos']}\n";
            } else {
                echo "   ❌ Erro: {$resultado['erro']}\n";
            }
            echo "\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Erro: {$e->getMessage()}\n\n";
    }
    
    // Exemplo 3: Status disponíveis
    echo "3. Status de rastreamento disponíveis:\n";
    echo "   - " . TotalExpressAPI::STATUS_PENDENTE . ": Pendente\n";
    echo "   - " . TotalExpressAPI::STATUS_EM_TRANSITO . ": Em Trânsito\n";
    echo "   - " . TotalExpressAPI::STATUS_ENTREGUE . ": Entregue\n";
    echo "   - " . TotalExpressAPI::STATUS_DEVOLVIDO . ": Devolvido\n";
    echo "   - " . TotalExpressAPI::STATUS_EXTRAVIADO . ": Extraviado\n\n";
    
    // Exemplo 4: Informações sobre rastreamento
    echo "4. Informações sobre rastreamento:\n";
    echo "   - A API de rastreamento é opcional e pode não estar disponível\n";
    echo "   - Códigos de rastreamento devem ter pelo menos 8 caracteres\n";
    echo "   - Suporte a rastreamento de múltiplas encomendas\n";
    echo "   - Status padronizados para facilitar integração\n\n";
    
    echo "=== EXEMPLO CONCLUÍDO ===\n";
    
} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: {$e->getMessage()}\n";
}
