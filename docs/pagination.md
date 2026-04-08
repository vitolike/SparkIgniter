# Paginação (Pagination)

A classe `Pagination` é uma core library nativa do SparkIgniter que permite gerar links numéricos de paginação de forma fácil. Ela mantém a arquitetura, semelhanças e facilidades clássicas encontradas no CodeIgniter 3. E o melhor: já vem padronizada para as classes HTML/CSS do Bootstrap 4 e 5 (`page-item`, `page-link`), tornando o seu uso totalmente *Plug and Play*.

## Como Carregar e Inicializar

Por se tratar de uma classe core, podemos invocá-la em nossos controllers através do **Loader**, da mesma forma que carregamos utilitários padrão do framework.

```php
// Carregar a classe a partir da Controller
$this->load->library('Pagination');

// Array mínimo de configuração
$config = [
    'base_url'   => 'http://localhost/seu-projeto/public/produtos/lista',
    'total_rows' => 200, // Informe o total global de resgistros dessa tabela
    'per_page'   => 10,  // Quantos itens você quer exibir por página
];

// Iniciar e preencher as configs no motor
$this->pagination->initialize($config);

// Gerar a string HTML pronta pra view:
$linksHTML = $this->pagination->create_links();
```

## Opções de Roteamento (URL e Parâmetros)

A Paginação suporta duas arquiteturas principais de controle e formação URLs, definidos pela variável `$config['page_query_string']`.

### O Padrão (Via Query Strings)
Por padrão (`true`), o SparkIgniter trabalha adicionando variáveis no final da URL atual, mesclando de forma isolada sem destruir propriedades que já existiam na sua URL (como filtros de busca em GET).

```php
$config['page_query_string'] = true; // (Ativo por padrão)
$config['query_string_segment'] = 'page'; // O nome lógico do seu parâmetro na URL

$this->pagination->initialize($config);
```

Isto vai gerar links estruturados respeitando variáveis antigas.
> **Exemplo:** `http://localhost/site/lista?filtro=carros&page=2`

### Via Segmentos de URL (Padrão CI3 Clássico)
Se a sua rota for estruturada e tratar o próprio caminho de leitura como a página que será capturada de vez, você pode desligar o uso de Query String:

```php
$config['page_query_string'] = false;
```

Isto criará roteamentos limpos baseados na Base URL informada, emendando puramente a numeração no final.
> **Exemplo:** `http://localhost/site/lista/2`

## Personalize do Seu Jeito (Estilos)

As varíaveis do Bootstrap já vêm definidas no código base, mas você tem ampla liberdade para modificar a renderização visual HTML reescrevendo as propriedades no momento de inicialização.

```php
$config['first_link'] = 'Primeira Página';
$config['last_link'] = 'Última Página';
$config['next_link'] = 'Próximo ->';
$config['prev_link'] = '<- Anterior';

// Mudando cores, classes ou adicionando tags diferentes ao Link Ativo atual
$config['cur_tag_open']   = '<li class="minha-class-diferentona"><a href="#">';
$config['cur_tag_close']  = '</a></li>';

$this->pagination->initialize($config);
```

## Implementação Completa (com QueryBuilder)

No fluxo de vida real, utilizamos os números calculados pela Paginação como nosso referencial de recortes do banco de dados (também conhecido como os blocos de OFFSET e LIMIT do `Query Builder`). A nossa API expõe a página do usuário requisitante através da variável internal `$cur_page`.

Confira o fluxo ideal:

```php
$this->load->library('Pagination');

// 1. Buscar o total REAL de itens da tabela no BD
$totalRows = $this->qb->query("SELECT COUNT(*) as qtd FROM logs")->fetchOne();

// 2. Setup completo da Paginação
$config = [
    'base_url'   => 'http://localhost/site/logs',
    'total_rows' => $totalRows['qtd'],
    'per_page'   => 15 // Cada block renderizado mostrará 15 de cada vez
];
$this->pagination->initialize($config);

// 3. Montar a margem temporal / offset
$paginaAtual = $this->pagination->cur_page;
$limit = $config['per_page'];
$offset = ($paginaAtual - 1) * $limit;

// 4. Buscar apenas a "fatia" do banco de dados referente à view requisitada
$itens = $this->qb->from('logs')
                  ->order_by('id', 'DESC')
                  ->limit($limit, $offset)
                  ->get();

// 5. Inserir os registros limitados e os links mágicos HTML para a sua View
$this->view('tela_de_logs', [
    'registros'       => $itens,
    'links_paginacao' => $this->pagination->create_links()
]);
```
