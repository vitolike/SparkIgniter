# Gerador de Entidades (IdGenerator)

Esta biblioteca cuida de gerenciar, criptografar e emitir Strings de Identificação Únicas ou aleatoriedades seguras utilizadas pelo seu framework.

```php
// Instanciação manual
$id = new IdGenerator();

// Ou use via acesso de escopo
$this->idGen->uuid();
```

## Funções Disponíveis

### `guid()`
Puxa GUID para sistemas Windows baseados em COM. Ou invoca alternativas com V4 em formato High Hexadecimal.
*(Exemplo: DB45C1A2-89B7...)*

### `uuid()` (Padrão e Recomendado)
Emite Universal Unique Identifier v4 criptograficamente seguro via `openssl_random_pseudo_bytes` ou `random`.
*(Exemplo: 550e8400-e29b-41d4-a716-446655440000)*

### `traceid()`
String única de apoio (Trace ID). Formato fixo: **LLLLLLDDXXXX** (6 Letras aleatórias, 2 Dígitos randômicos e 4 de Sufixo fixo final).
*(Exemplo: AXQZPT458921)* Ideal para recibos.

### `tokenHex(int $length)`
Gera um token puramente hexadecimal aleatório do seu tamanho solicitado par. Ideal para Sessões do banco e senhas geradas pelo backend.
```php
$token = $this->idGen->tokenHex(40);
```

### `tokenBase64(int $bytes)`
Retorna Strings codificadas em BASE64 puras _URL_SAFE_ (Sem `+ / =`).

### `hashShortUrl(int $length = 8)`
Gera pequenos shorts como encurtadores (Ex: Bitly). Cuidado, não deve ser usado limitadores rigorosos em senhas devido a pequena variação.

## Exemplo Prático Completo

Se você precisar gerar um ID único antes de salvar uma transação e também gerar um token de confirmação aleatório que será enviado por e-mail:

```php
public function criarTransacao() {
    // Digamos que a sua Model de Transações use UUID primário e não Inteiro auto-increment...
    $novo_uuid = $this->idGen->uuid();
    $token_email = $this->idGen->tokenHex(32); 
    
    // Inserindo
    $this->db->insert('transacoes', [
        'id'     => $novo_uuid,
        'valor'  => 500.00,
        'status' => 'pendente',
        'token'  => $token_email
    ]);

    // O token_email (ex: a8b4cdef12...) seria mandado na URL do usuário.
    // O id (ex: 550e8400-e29b...) será usado em interações com Gateways como Stripe ou Pagar.me.
}
```
