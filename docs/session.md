# Controladora de Sessões (Session)

A API de Manipulação de State (`Session`) encapsula a interatividade hostil default de `$_SESSION` do PHP 8.4 sob a sombra de um wrapper estilo "CodeIgniter" totalmente blindado.

## Proteções Aplicadas Naturalmente

Apenas abrindo a classe, a Core injeta proteção automática (XSS Cookie HttpOnly Flag Set, IP Address check-list, Strict-mode e HTTPS Secure Transfer flags) via init configuration interna do interpretador sem necessidade de editar `php.ini`.

Ela recria a chave via `session_regenerate_id()` para matar _Network sniff fixation_.

## Associações (Adicionando na Session Local Web)

Para salvar algo para a próxima aba, redirecionamento ou permanência de página:

```php
// Único Setter Direto
$this->session->set_userdata('plano_user', 'PREMIUM');

// Setter Múltiplo Array Associativo
$this->session->set_userdata([
    'carrinho_count' => 15,
    'usuario_id'     => 44
]);
```

## Recuperações (Getters Seguros)

Jamais consulte direto a estante crua de `$ _SESSION['chave'];` global.
Sempre opte por `$this->session->userdata('chave');`. (Para não ter Warning Errors se inexistir do servidor).

Verificando presença lógica para if/returns de Views:

```php
if ($this->session->has_userdata('usuario_id')) {
    // Bem vindo de volta. Exibimos a barra superior html
} 
```

Retornar a tabela toda excluindo o token index de varredura core interna local `__session_initialized`.

```php
$todasOsItemsMemoryArray = $this->session->all_userdata();
```

## Revogando Dados (Deletions)

Apagar variáveis da prancha memory do site temporário para resete de fluxos e esvaziamentos de sacolas por exemplo.

```php
$this->session->unset_userdata('plano_user'); // remove de onde injetamos em cima
```

Destruindo TUDO do cliente sem exceções com purga completa de flags do Servidor Linux/Windows e Cookie Expire Date forced para retroatividade global (Ex: Encerrar sessão Log-off).

```php
$this->session->sess_destroy();
```
