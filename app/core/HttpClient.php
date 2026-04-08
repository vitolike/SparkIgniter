<?php
declare(strict_types=1);

/**
 * Http Client - Wrapper cURL para PHP 8.4
 *
 * Oferece métodos simples para requisições GET/POST e manipulação de JSON.
 */

/**
 * Guia de Uso da Classe HttpClient (PHP 8.4)
 *
 * Esta classe facilita a comunicação com APIs externas usando cURL,
 * garantindo tipagem de retorno e boas práticas de segurança.
 *
 * 1. Inicialização:
 * O objeto pode ser criado sem argumentos ou com opções cURL padrão.
 * Ex: $client = new HttpClient();
 * Ex: $client = new HttpClient([CURLOPT_TIMEOUT => 60]);
 *
 * 2. Estrutura de Resposta:
 * Todos os métodos de requisição (get, post, postJson) retornam um array no formato:
 * [
 * 'body'   => (string) O corpo da resposta HTTP,
 * 'status' => (int) O código de status HTTP (200, 404, 500, etc.),
 * 'info'   => (array) Detalhes completos do cURL (tempo, IP, etc.)
 * ]
 *
 * 3. Requisições Comuns (GET, POST):
 *
 * 3.1. GET Simples:
 * $response = $client->get('https://api.site.com/recurso');
 *
 * 3.2. GET com Query Params:
 * $response = $client->get('https://api.site.com/buscar', [
 * 'id' => 123,
 * 'cache' => false
 * ]);
 *
 * 3.3. POST (Form-urlencoded):
 * // Envia dados como application/x-www-form-urlencoded
 * $response = $client->post('https://api.site.com/login', [
 * 'user' => 'admin',
 * 'pass' => '123456'
 * ]);
 *
 * 3.4. POST JSON (Recomendado para APIs):
 * // Envia dados como application/json e codifica o array automaticamente
 * $response = $client->postJson('https://api.site.com/criar', [
 * 'nome' => 'Novo Usuário',
 * 'email' => 'novo@teste.com'
 * ]);
 *
 * 4. Configurações Avançadas:
 *
 * 4.1. Definir Opções Específicas:
 * // Define um header de autorização APENAS para a próxima requisição
 * $response = $client
 * ->setOption(CURLOPT_HTTPHEADER, ['Authorization: Bearer token-xyz'])
 * ->get('https://api.site.com/perfil');
 *
 * 4.2. Obter Informações de Debugging:
 * // Chame após a execução de qualquer método (get/post)
 * $info = $client->getLastInfo();
 * // Ex: echo "Tempo de conexão: " . $info['total_time'];
 *
 * 5. Tratamento de Erros:
 * A classe lança uma RuntimeException se houver falha de rede/transporte do cURL
 * (ex: timeout, DNS não encontrado). Erros HTTP (4xx, 5xx) devem ser tratados
 * verificando a chave $response['status'].
 *
 * try {
 * $response = $client->get('...');
 * if ($response['status'] !== 200) { throw new Exception("HTTP Error"); }
 * } catch (RuntimeException $e) {
 * // Trata erro de cURL (rede/transporte)
 * }
 */
class HttpClient
{
    private array $options = [];
    private ?array $lastInfo = null;

    /**
     * Define opções cURL globais ou inicia o cliente.
     */
    public function __construct(array $defaultOptions = [])
    {
        // Define opções seguras como padrão
        $this->options = $defaultOptions + [
            CURLOPT_RETURNTRANSFER => true, // Retorna o resultado como string
            CURLOPT_FOLLOWLOCATION => true, // Segue redirecionamentos
            CURLOPT_AUTOREFERER => true,    // Define Referer automaticamente
            CURLOPT_TIMEOUT => 30,          // Timeout de 30 segundos
            CURLOPT_SSL_VERIFYPEER => true, // Verifica certificado SSL (ESSENCIAL PARA SEGURANÇA)
            CURLOPT_HTTPHEADER => ['Accept: application/json'], // Aceita JSON por padrão
        ];
    }

    /**
     * Define ou sobrescreve uma única opção cURL.
     */
    public function setOption(int $option, mixed $value): self
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * Executa uma requisição GET.
     * @return array{body: string, status: int, info: array}
     */
    public function get(string $url, array $params = []): array
    {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $this->request($url, [
            CURLOPT_HTTPGET => true,
        ]);
    }

    /**
     * Executa uma requisição POST com dados no formato application/x-www-form-urlencoded.
     */
    public function post(string $url, array $data = []): array
    {
        return $this->request($url, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            // Headers: Não sobrescreve, apenas adiciona se já houver
            CURLOPT_HTTPHEADER => array_merge($this->options[CURLOPT_HTTPHEADER] ?? [], [
                'Content-Type: application/x-www-form-urlencoded'
            ]),
        ]);
    }
    
    /**
     * Executa uma requisição POST com dados no formato JSON.
     */
    public function postJson(string $url, array $data): array
    {
        $json_data = json_encode($data, JSON_THROW_ON_ERROR); // PHP 7.3+ para JSON_THROW_ON_ERROR
        
        return $this->request($url, [
            CURLOPT_CUSTOMREQUEST => 'POST', // Usa CUSTOMREQUEST para garantir que o método seja POST
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_HTTPHEADER => array_merge($this->options[CURLOPT_HTTPHEADER] ?? [], [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json_data)
            ]),
        ]);
    }

    /**
     * Executa o cURL com base nas opções fornecidas.
     */
    protected function request(string $url, array $localOptions = []): array
    {
        $ch = curl_init($url);

        // Combina opções padrão e locais
        $options = $localOptions + $this->options;

        curl_setopt_array($ch, $options);
        
        // Execução
        $body = curl_exec($ch);
        
        // Verifica se houve erro de transporte (rede, timeout, etc.)
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("cURL Error on $url: $error");
        }

        // Obtém informações e status HTTP
        $info = curl_getinfo($ch);
        $this->lastInfo = $info;

        $status = (int)($info['http_code'] ?? 0);
        
        curl_close($ch);

        return [
            'body'   => (string)$body,
            'status' => $status,
            'info'   => $info,
        ];
    }
    
    /**
     * Obtém informações da última requisição.
     */
    public function getLastInfo(): ?array
    {
        return $this->lastInfo;
    }
}