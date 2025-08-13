<?php

namespace Hardsystem\TotalExpressAPI;

/**
 * Classe para integração com a API SOAP da Total Express
 * 
 * Esta classe fornece métodos para calcular fretes e interagir com os serviços
 * da Total Express através de sua API SOAP.
 */
class TotalExpressAPI
{
    private $user;
    private $pass;
    private $location;
    private $options;
    private $client;
    private $debug = false;

    /**
     * Tipos de serviço disponíveis
     */
    const TIPO_SERVICO_EXP = 'EXP';  // Expresso
    const TIPO_SERVICO_ESP = 'ESP';  // Especial
    const TIPO_SERVICO_PRM = 'PRM';  // Premium
    const TIPO_SERVICO_STD = 'STD';  // Standard

    /**
     * Tipos de entrega
     */
    const TIPO_ENTREGA_NORMAL = 0;   // Entrega normal
    const TIPO_ENTREGA_GOBACK = 1;   // GoBack
    const TIPO_ENTREGA_RMA = 2;      // RMA

    /**
     * Construtor da classe
     * 
     * @param string $user Usuário da API
     * @param string $pass Senha da API
     * @param string $environment Ambiente (production ou sandbox)
     * @param bool $debug Ativa logs de debug
     */
    public function __construct($user, $pass, $environment = 'production', $debug = false)
    {
        $this->user = $user;
        $this->pass = $pass;
        $this->debug = $debug;
        
        // Define o endpoint baseado no ambiente
        if ($environment === 'sandbox') {
            $this->location = 'https://edi.totalexpress.com.br/webservice_calculo_frete_v2_sandbox.php';
        } else {
            $this->location = 'https://edi.totalexpress.com.br/webservice_calculo_frete_v2.php';
        }

        $this->initializeSoapClient();
    }

    /**
     * Inicializa o cliente SOAP
     */
    private function initializeSoapClient()
    {
        $this->options = [
            'location'        => $this->location,
            'uri'             => 'urn:calcularFrete',
            'login'           => $this->user,
            'password'        => $this->pass,
            'authentication'  => SOAP_AUTHENTICATION_BASIC,
            'trace'           => true,
            'exceptions'      => true,
            'connection_timeout' => 30,
            'features'        => SOAP_SINGLE_ELEMENT_ARRAYS,
        ];

        try {
            $this->client = new \SoapClient(null, $this->options);
            $this->log('Cliente SOAP da Total Express inicializado com sucesso');
        } catch (\SoapFault $e) {
            $this->log('Erro ao inicializar cliente SOAP da Total Express: ' . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Calcula o frete para um destino
     * 
     * @param array $params Parâmetros do cálculo
     * @return array Resultado do cálculo
     * @throws \SoapFault
     */
    public function calcularFrete($params)
    {
        try {
            // Valida os parâmetros obrigatórios
            $this->validarParametrosFrete($params);

            // Prepara os parâmetros para a API
            $soapParams = [
                'calcularFreteRequest' => [
                    'TipoServico'    => $params['tipo_servico'],
                    'CepDestino'     => $this->limparCep($params['cep_destino']),
                    'Peso'           => $this->formatarDecimal($params['peso']),
                    'ValorDeclarado' => $this->formatarDecimal($params['valor_declarado']),
                    'TipoEntrega'    => $params['tipo_entrega'] ?? self::TIPO_ENTREGA_NORMAL,
                ]
            ];

            // Adiciona parâmetros opcionais se fornecidos
            if (isset($params['servico_cod'])) {
                $soapParams['calcularFreteRequest']['ServicoCOD'] = $params['servico_cod'];
            }
            if (isset($params['altura'])) {
                $soapParams['calcularFreteRequest']['Altura'] = $params['altura'];
            }
            if (isset($params['largura'])) {
                $soapParams['calcularFreteRequest']['Largura'] = $params['largura'];
            }
            if (isset($params['profundidade'])) {
                $soapParams['calcularFreteRequest']['Profundidade'] = $params['profundidade'];
            }

            $this->log('Calculando frete Total Express', [
                'cep_destino' => $params['cep_destino'],
                'peso' => $params['peso'],
                'tipo_servico' => $params['tipo_servico']
            ]);

            // Faz a chamada SOAP
            $response = $this->client->__soapCall('calcularFrete', $soapParams);

            // Processa a resposta
            $resultado = $this->processarRespostaFrete($response);

            $this->log('Frete calculado com sucesso', [
                'prazo' => $resultado['prazo'],
                'valor' => $resultado['valor']
            ]);

            return $resultado;

        } catch (\SoapFault $e) {
            $this->log('Erro ao calcular frete Total Express', [
                'erro' => $e->getMessage(),
                'request' => isset($this->client) ? $this->client->__getLastRequest() : null,
                'response' => isset($this->client) ? $this->client->__getLastResponse() : null
            ], 'ERROR');

            throw new \Exception('Erro ao calcular frete: ' . $e->getMessage());
        }
    }

    /**
     * Valida os parâmetros obrigatórios para cálculo de frete
     * 
     * @param array $params
     * @throws \InvalidArgumentException
     */
    private function validarParametrosFrete($params)
    {
        $camposObrigatorios = ['tipo_servico', 'cep_destino', 'peso', 'valor_declarado'];
        
        foreach ($camposObrigatorios as $campo) {
            if (!isset($params[$campo]) || empty($params[$campo])) {
                throw new \InvalidArgumentException("Campo obrigatório não informado: {$campo}");
            }
        }

        // Valida CEP
        if (!preg_match('/^\d{8}$/', $this->limparCep($params['cep_destino']))) {
            throw new \InvalidArgumentException('CEP deve conter 8 dígitos numéricos');
        }

        // Valida peso
        if (!is_numeric($params['peso']) || $params['peso'] <= 0) {
            throw new \InvalidArgumentException('Peso deve ser um número positivo');
        }

        // Valida valor declarado
        if (!is_numeric($params['valor_declarado']) || $params['valor_declarado'] < 0) {
            throw new \InvalidArgumentException('Valor declarado deve ser um número não negativo');
        }

        // Valida tipo de serviço
        $tiposValidos = [self::TIPO_SERVICO_EXP, self::TIPO_SERVICO_ESP, self::TIPO_SERVICO_PRM, self::TIPO_SERVICO_STD];
        if (!in_array($params['tipo_servico'], $tiposValidos)) {
            throw new \InvalidArgumentException('Tipo de serviço inválido');
        }
    }

    /**
     * Processa a resposta da API de cálculo de frete
     * 
     * @param object $response
     * @return array
     */
    private function processarRespostaFrete($response)
    {
        // Tenta diferentes estruturas de resposta
        $dados = $response->calcularFreteResponse->DadosFrete ?? 
                 $response->DadosFrete ?? 
                 $response ?? null;

        if (!$dados) {
            throw new \Exception('Resposta da API não contém dados válidos');
        }

        $prazo = is_object($dados) ? ($dados->Prazo ?? null) : null;
        $valor = is_object($dados) ? ($dados->ValorServico ?? null) : null;

        return [
            'sucesso' => true,
            'prazo' => $prazo,
            'valor' => $valor,
            'valor_formatado' => $this->formatarMoeda($valor),
            'prazo_texto' => $this->formatarPrazo($prazo)
        ];
    }

    /**
     * Limpa o CEP removendo caracteres não numéricos
     * 
     * @param string $cep
     * @return string
     */
    private function limparCep($cep)
    {
        return preg_replace('/[^0-9]/', '', $cep);
    }

    /**
     * Formata decimal para o padrão da API (vírgula como separador)
     * 
     * @param float $valor
     * @return string
     */
    private function formatarDecimal($valor)
    {
        return number_format($valor, 2, ',', '');
    }

    /**
     * Formata valor monetário para exibição
     * 
     * @param string $valor
     * @return string
     */
    private function formatarMoeda($valor)
    {
        if (!$valor) return 'R$ 0,00';
        
        // Converte vírgula para ponto para formatação
        $valor = str_replace(',', '.', $valor);
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }

    /**
     * Formata prazo para exibição
     * 
     * @param int $prazo
     * @return string
     */
    private function formatarPrazo($prazo)
    {
        if (!$prazo) return 'Prazo não informado';
        
        if ($prazo == 1) {
            return '1 dia útil';
        } else {
            return "{$prazo} dias úteis";
        }
    }

    /**
     * Sistema de log simples
     * 
     * @param string $message
     * @param array $context
     * @param string $level
     */
    private function log($message, $context = [], $level = 'INFO')
    {
        if (!$this->debug) return;
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        
        echo "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";
    }

    /**
     * Obtém informações do último request/response para debug
     * 
     * @return array
     */
    public function getDebugInfo()
    {
        if (!$this->client) {
            return ['error' => 'Cliente SOAP não inicializado'];
        }

        return [
            'last_request' => $this->client->__getLastRequest(),
            'last_response' => $this->client->__getLastResponse(),
            'last_request_headers' => $this->client->__getLastRequestHeaders(),
            'last_response_headers' => $this->client->__getLastResponseHeaders()
        ];
    }
}
