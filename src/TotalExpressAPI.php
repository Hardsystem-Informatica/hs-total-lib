<?php

namespace Hardsystem\TotalExpressAPI;

/**
 * Classe para integração com a API SOAP da Total Express
 * 
 * Esta classe fornece métodos para calcular fretes e rastrear encomendas
 * da Total Express através de sua API SOAP.
 */
class TotalExpressAPI
{
    private $user;
    private $pass;
    private $location;
    private $locationRastreamento;
    private $options;
    private $client;
    private $clientRastreamento;
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
     * Status de rastreamento
     */
    const STATUS_PENDENTE = 'PENDENTE';
    const STATUS_EM_TRANSITO = 'EM_TRANSITO';
    const STATUS_ENTREGUE = 'ENTREGUE';
    const STATUS_DEVOLVIDO = 'DEVOLVIDO';
    const STATUS_EXTRAVIADO = 'EXTRAVIADO';

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
        
        // Define os endpoints baseado no ambiente
        if ($environment === 'sandbox') {
            $this->location = 'https://edi.totalexpress.com.br/webservice_calculo_frete_v2_sandbox.php';
            $this->locationRastreamento = 'https://edi.totalexpress.com.br/webservice_rastreamento_sandbox.php';
        } else {
            $this->location = 'https://edi.totalexpress.com.br/webservice_calculo_frete_v2.php';
            $this->locationRastreamento = 'https://edi.totalexpress.com.br/webservice_rastreamento.php';
        }

        $this->initializeSoapClient();
        $this->initializeRastreamentoClient();
    }

    /**
     * Inicializa o cliente SOAP para cálculo de frete
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
     * Inicializa o cliente SOAP para rastreamento
     */
    private function initializeRastreamentoClient()
    {
        $optionsRastreamento = [
            'location'        => $this->locationRastreamento,
            'uri'             => 'urn:rastreamento',
            'login'           => $this->user,
            'password'        => $this->pass,
            'authentication'  => SOAP_AUTHENTICATION_BASIC,
            'trace'           => true,
            'exceptions'      => true,
            'connection_timeout' => 30,
            'features'        => SOAP_SINGLE_ELEMENT_ARRAYS,
        ];

        try {
            $this->clientRastreamento = new \SoapClient(null, $optionsRastreamento);
            $this->log('Cliente SOAP de rastreamento da Total Express inicializado com sucesso');
        } catch (\SoapFault $e) {
            $this->log('Erro ao inicializar cliente SOAP de rastreamento: ' . $e->getMessage(), 'ERROR');
            // Não lança exceção aqui pois o rastreamento é opcional
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
     * Rastreia uma encomenda pelo código de rastreamento
     * 
     * @param string $codigoRastreamento Código de rastreamento da encomenda
     * @return array Dados do rastreamento
     * @throws \Exception
     */
    public function rastrearEncomenda($codigoRastreamento)
    {
        if (!$this->clientRastreamento) {
            throw new \Exception('Cliente de rastreamento não disponível');
        }

        try {
            // Valida o código de rastreamento
            $this->validarCodigoRastreamento($codigoRastreamento);

            $this->log('Rastreando encomenda', [
                'codigo' => $codigoRastreamento
            ]);

            // Prepara os parâmetros para a API
            $soapParams = [
                'rastreamentoRequest' => [
                    'CodigoRastreamento' => $codigoRastreamento
                ]
            ];

            // Faz a chamada SOAP
            $response = $this->clientRastreamento->__soapCall('rastreamento', $soapParams);

            // Processa a resposta
            $resultado = $this->processarRespostaRastreamento($response);

            $this->log('Rastreamento realizado com sucesso', [
                'codigo' => $codigoRastreamento,
                'status' => $resultado['status']
            ]);

            return $resultado;

        } catch (\SoapFault $e) {
            $this->log('Erro ao rastrear encomenda', [
                'erro' => $e->getMessage(),
                'codigo' => $codigoRastreamento,
                'request' => isset($this->clientRastreamento) ? $this->clientRastreamento->__getLastRequest() : null,
                'response' => isset($this->clientRastreamento) ? $this->clientRastreamento->__getLastResponse() : null
            ], 'ERROR');

            throw new \Exception('Erro ao rastrear encomenda: ' . $e->getMessage());
        }
    }

    /**
     * Rastreia múltiplas encomendas de uma vez
     * 
     * @param array $codigosRastreamento Array com códigos de rastreamento
     * @return array Array com resultados do rastreamento
     */
    public function rastrearMultiplasEncomendas($codigosRastreamento)
    {
        if (!is_array($codigosRastreamento) || empty($codigosRastreamento)) {
            throw new \InvalidArgumentException('Array de códigos de rastreamento inválido');
        }

        $resultados = [];
        
        foreach ($codigosRastreamento as $codigo) {
            try {
                $resultados[] = [
                    'codigo' => $codigo,
                    'sucesso' => true,
                    'dados' => $this->rastrearEncomenda($codigo)
                ];
            } catch (\Exception $e) {
                $resultados[] = [
                    'codigo' => $codigo,
                    'sucesso' => false,
                    'erro' => $e->getMessage()
                ];
            }
        }

        return $resultados;
    }

    /**
     * Valida o código de rastreamento
     * 
     * @param string $codigo
     * @throws \InvalidArgumentException
     */
    private function validarCodigoRastreamento($codigo)
    {
        if (empty($codigo)) {
            throw new \InvalidArgumentException('Código de rastreamento não informado');
        }

        // Remove espaços e caracteres especiais
        $codigo = preg_replace('/[^A-Z0-9]/', '', strtoupper($codigo));
        
        if (strlen($codigo) < 8) {
            throw new \InvalidArgumentException('Código de rastreamento inválido');
        }
    }

    /**
     * Processa a resposta da API de rastreamento
     * 
     * @param object $response
     * @return array
     */
    private function processarRespostaRastreamento($response)
    {
        // Tenta diferentes estruturas de resposta
        $dados = $response->rastreamentoResponse->DadosRastreamento ?? 
                 $response->DadosRastreamento ?? 
                 $response ?? null;

        if (!$dados) {
            throw new \Exception('Resposta da API de rastreamento não contém dados válidos');
        }

        // Processa os eventos de rastreamento
        $eventos = [];
        if (isset($dados->Eventos) && is_array($dados->Eventos)) {
            foreach ($dados->Eventos as $evento) {
                $eventos[] = [
                    'data' => $evento->Data ?? null,
                    'hora' => $evento->Hora ?? null,
                    'local' => $evento->Local ?? null,
                    'status' => $evento->Status ?? null,
                    'observacao' => $evento->Observacao ?? null
                ];
            }
        }

        // Determina o status atual
        $statusAtual = $this->determinarStatusAtual($eventos);

        return [
            'sucesso' => true,
            'codigo_rastreamento' => $dados->CodigoRastreamento ?? null,
            'status' => $statusAtual,
            'status_texto' => $this->getStatusTexto($statusAtual),
            'data_entrega' => $dados->DataEntrega ?? null,
            'destinatario' => $dados->Destinatario ?? null,
            'eventos' => $eventos,
            'total_eventos' => count($eventos),
            'ultimo_evento' => !empty($eventos) ? $eventos[0] : null
        ];
    }

    /**
     * Determina o status atual baseado nos eventos
     * 
     * @param array $eventos
     * @return string
     */
    private function determinarStatusAtual($eventos)
    {
        if (empty($eventos)) {
            return self::STATUS_PENDENTE;
        }

        $ultimoEvento = $eventos[0];
        $status = strtoupper($ultimoEvento['status'] ?? '');

        // Mapeia os status da Total Express para os nossos
        if (strpos($status, 'ENTREGUE') !== false) {
            return self::STATUS_ENTREGUE;
        } elseif (strpos($status, 'DEVOLVIDO') !== false) {
            return self::STATUS_DEVOLVIDO;
        } elseif (strpos($status, 'EXTRAVIADO') !== false) {
            return self::STATUS_EXTRAVIADO;
        } elseif (strpos($status, 'EM_TRANSITO') !== false || strpos($status, 'EM ROTA') !== false) {
            return self::STATUS_EM_TRANSITO;
        } else {
            return self::STATUS_PENDENTE;
        }
    }

    /**
     * Retorna o texto do status
     * 
     * @param string $status
     * @return string
     */
    private function getStatusTexto($status)
    {
        $statusMap = [
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_EM_TRANSITO => 'Em Trânsito',
            self::STATUS_ENTREGUE => 'Entregue',
            self::STATUS_DEVOLVIDO => 'Devolvido',
            self::STATUS_EXTRAVIADO => 'Extraviado'
        ];

        return $statusMap[$status] ?? 'Status Desconhecido';
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
