<?php

require_once 'vendor/autoload.php';

use Hardsystem\TotalExpressAPI\TotalExpressAPI;

// Configurações da API
$user = '';
$pass = '';

try {
    // Inicializa a API
    $totalAPI = new TotalExpressAPI($user, $pass, 'production');
    
    echo "=== EXEMPLO DE USO DA API TOTAL EXPRESS ===\n\n";
    
    // Exemplo 1: Cálculo básico de frete
    echo "1. Cálculo básico de frete:\n";
    $params = [
        'tipo_servico' => TotalExpressAPI::TIPO_SERVICO_EXP,
        'cep_destino' => '11035-040',
        'peso' => 0.47,
        'valor_declarado' => 139.40,
        'tipo_entrega' => TotalExpressAPI::TIPO_ENTREGA_NORMAL
    ];
    
    $resultado = $totalAPI->calcularFrete($params);
    
    echo "   CEP Destino: {$params['cep_destino']}\n";
    echo "   Peso: {$params['peso']} kg\n";
    echo "   Valor Declarado: R$ {$params['valor_declarado']}\n";
    echo "   Tipo Serviço: {$params['tipo_servico']}\n";
    echo "   Resultado:\n";
    echo "     - Prazo: {$resultado['prazo_texto']}\n";
    echo "     - Valor: {$resultado['valor_formatado']}\n\n";
    
    // Exemplo 2: Cálculo com dimensões
    echo "2. Cálculo com dimensões:\n";
    $params2 = [
        'tipo_servico' => TotalExpressAPI::TIPO_SERVICO_STD,
        'cep_destino' => '20040-020',
        'peso' => 2.5,
        'valor_declarado' => 500.00,
        'tipo_entrega' => TotalExpressAPI::TIPO_ENTREGA_NORMAL,
        'altura' => 30,
        'largura' => 40,
        'profundidade' => 50
    ];
    
    $resultado2 = $totalAPI->calcularFrete($params2);
    
    echo "   CEP Destino: {$params2['cep_destino']}\n";
    echo "   Peso: {$params2['peso']} kg\n";
    echo "   Dimensões: {$params2['altura']}x{$params2['largura']}x{$params2['profundidade']} cm\n";
    echo "   Valor Declarado: R$ {$params2['valor_declarado']}\n";
    echo "   Tipo Serviço: {$params2['tipo_servico']}\n";
    echo "   Resultado:\n";
    echo "     - Prazo: {$resultado2['prazo_texto']}\n";
    echo "     - Valor: {$resultado2['valor_formatado']}\n\n";
    
    // Exemplo 3: Cálculo com serviço COD
    echo "3. Cálculo com serviço COD:\n";
    $params3 = [
        'tipo_servico' => TotalExpressAPI::TIPO_SERVICO_ESP,
        'cep_destino' => '01310-100',
        'peso' => 1.2,
        'valor_declarado' => 250.00,
        'tipo_entrega' => TotalExpressAPI::TIPO_ENTREGA_NORMAL,
        'servico_cod' => true
    ];
    
    $resultado3 = $totalAPI->calcularFrete($params3);
    
    echo "   CEP Destino: {$params3['cep_destino']}\n";
    echo "   Peso: {$params3['peso']} kg\n";
    echo "   Valor Declarado: R$ {$params3['valor_declarado']}\n";
    echo "   Tipo Serviço: {$params3['tipo_servico']} (com COD)\n";
    echo "   Resultado:\n";
    echo "     - Prazo: {$resultado3['prazo_texto']}\n";
    echo "     - Valor: {$resultado3['valor_formatado']}\n\n";
    
    // Exemplo 4: Lista de tipos de serviço disponíveis
    echo "4. Tipos de serviço disponíveis:\n";
    echo "   - EXP: Expresso\n";
    echo "   - ESP: Especial\n";
    echo "   - PRM: Premium\n";
    echo "   - STD: Standard\n\n";
    
    // Exemplo 5: Lista de tipos de entrega
    echo "5. Tipos de entrega disponíveis:\n";
    echo "   - 0: Entrega normal\n";
    echo "   - 1: GoBack\n";
    echo "   - 2: RMA\n\n";
    
    echo "=== EXEMPLO CONCLUÍDO ===\n";
    
} catch (InvalidArgumentException $e) {
    echo "Erro de validação: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    
    // Se houver informações de debug disponíveis
    if (isset($totalAPI)) {
        $debug = $totalAPI->getDebugInfo();
        if (isset($debug['error'])) {
            echo "Debug: " . $debug['error'] . "\n";
        }
    }
}
