# Total Express API - Biblioteca PHP

Uma biblioteca PHP para integração com a API SOAP da Total Express, permitindo calcular fretes de forma simples e eficiente.

## 📋 Características

- ✅ Cálculo de frete via API SOAP da Total Express
- ✅ Suporte a diferentes tipos de serviço (EXP, ESP, PRM, STD)
- ✅ Validação automática de parâmetros
- ✅ Formatação automática de valores monetários
- ✅ Sistema de logs para debug
- ✅ Tratamento de erros robusto
- ✅ Suporte a parâmetros opcionais (dimensões, COD, etc.)

## 🚀 Instalação

### Via Composer

```bash
composer require hardsystem/total-express-api
```

### Manual

1. Baixe os arquivos da biblioteca
2. Inclua o arquivo `src/TotalExpressAPI.php` no seu projeto
3. Configure suas credenciais da API

## 📖 Uso Básico

```php
<?php

use Hardsystem\TotalExpressAPI\TotalExpressAPI;

// Inicializa a API
$totalAPI = new TotalExpressAPI('seu_usuario', 'sua_senha');

// Parâmetros do cálculo
$params = [
    'tipo_servico' => TotalExpressAPI::TIPO_SERVICO_EXP,
    'cep_destino' => '11035-040',
    'peso' => 0.47,
    'valor_declarado' => 139.40,
    'tipo_entrega' => TotalExpressAPI::TIPO_ENTREGA_NORMAL
];

// Calcula o frete
$resultado = $totalAPI->calcularFrete($params);

echo "Prazo: " . $resultado['prazo_texto'] . "\n";
echo "Valor: " . $resultado['valor_formatado'] . "\n";
```

## 🔧 Configuração

### Construtor

```php
$totalAPI = new TotalExpressAPI(
    $user,           // string - Usuário da API
    $pass,           // string - Senha da API
    $environment,    // string - 'production' ou 'sandbox' (opcional)
    $debug           // bool - Ativa logs de debug (opcional)
);
```

### Parâmetros Obrigatórios

| Campo | Tipo | Descrição | Exemplo |
|-------|------|-----------|---------|
| `tipo_servico` | string | Tipo do serviço | `TotalExpressAPI::TIPO_SERVICO_EXP` |
| `cep_destino` | string | CEP de destino | `'11035-040'` |
| `peso` | float | Peso em kg | `0.47` |
| `valor_declarado` | float | Valor declarado | `139.40` |

### Parâmetros Opcionais

| Campo | Tipo | Descrição | Padrão |
|-------|------|-----------|--------|
| `tipo_entrega` | int | Tipo de entrega | `TIPO_ENTREGA_NORMAL` |
| `servico_cod` | bool | Serviço COD | `false` |
| `altura` | int | Altura em cm | - |
| `largura` | int | Largura em cm | - |
| `profundidade` | int | Profundidade em cm | - |

## 📊 Tipos de Serviço

| Constante | Descrição |
|-----------|-----------|
| `TIPO_SERVICO_EXP` | Expresso |
| `TIPO_SERVICO_ESP` | Especial |
| `TIPO_SERVICO_PRM` | Premium |
| `TIPO_SERVICO_STD` | Standard |

## 🚚 Tipos de Entrega

| Constante | Valor | Descrição |
|-----------|-------|-----------|
| `TIPO_ENTREGA_NORMAL` | 0 | Entrega normal |
| `TIPO_ENTREGA_GOBACK` | 1 | GoBack |
| `TIPO_ENTREGA_RMA` | 2 | RMA |

## 📤 Resposta da API

A API retorna um array com as seguintes informações:

```php
[
    'sucesso' => true,
    'prazo' => 4,                    // Prazo em dias
    'valor' => '17,68',              // Valor do frete
    'valor_formatado' => 'R$ 17,68', // Valor formatado
    'prazo_texto' => '4 dias úteis'  // Prazo formatado
]
```

## 🔍 Exemplos de Uso

### Exemplo 1: Cálculo Básico

```php
$params = [
    'tipo_servico' => TotalExpressAPI::TIPO_SERVICO_EXP,
    'cep_destino' => '11035-040',
    'peso' => 0.47,
    'valor_declarado' => 139.40
];

$resultado = $totalAPI->calcularFrete($params);
```

### Exemplo 2: Com Dimensões

```php
$params = [
    'tipo_servico' => TotalExpressAPI::TIPO_SERVICO_STD,
    'cep_destino' => '20040-020',
    'peso' => 2.5,
    'valor_declarado' => 500.00,
    'altura' => 30,
    'largura' => 40,
    'profundidade' => 50
];

$resultado = $totalAPI->calcularFrete($params);
```

### Exemplo 3: Com Serviço COD

```php
$params = [
    'tipo_servico' => TotalExpressAPI::TIPO_SERVICO_ESP,
    'cep_destino' => '01310-100',
    'peso' => 1.2,
    'valor_declarado' => 250.00,
    'servico_cod' => true
];

$resultado = $totalAPI->calcularFrete($params);
```

## 🐛 Debug e Logs

Para ativar logs de debug:

```php
$totalAPI = new TotalExpressAPI('usuario', 'senha', 'production', true);
```

Para obter informações de debug da última requisição:

```php
$debugInfo = $totalAPI->getDebugInfo();
print_r($debugInfo);
```

## ⚠️ Tratamento de Erros

A biblioteca lança exceções em caso de erro:

```php
try {
    $resultado = $totalAPI->calcularFrete($params);
} catch (InvalidArgumentException $e) {
    echo "Erro de validação: " . $e->getMessage();
} catch (Exception $e) {
    echo "Erro da API: " . $e->getMessage();
}
```

## 🔒 Segurança

- As credenciais são enviadas via autenticação básica HTTPS
- A biblioteca não armazena credenciais em logs
- Validação rigorosa de parâmetros de entrada

## 📝 Requisitos

- PHP 7.4 ou superior
- Extensão SOAP habilitada
- Conexão com internet para acessar a API da Total Express

## 🤝 Contribuição

Para contribuir com o projeto:

1. Faça um fork do repositório
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo LICENSE para mais detalhes.

## 📞 Suporte

Para suporte técnico ou dúvidas sobre a API da Total Express, consulte a documentação oficial da empresa.

---

**Desenvolvido com ❤️ para facilitar a integração com a Total Express**
