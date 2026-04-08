<?php
// app/core/Pagination.php

#[\AllowDynamicProperties]
class Pagination {
    
    public string $base_url       = ''; 
    public int $total_rows        = 0; 
    public int $per_page          = 10; 
    public int $num_links         = 2; 
    public int $cur_page          = 0;
    
    // Configurações de URL
    public bool $page_query_string= true;
    public string $query_string_segment = 'page';
    
    // HTML Elements (Padrão Bootstrap/CI3 style)
    public string $full_tag_open  = '<ul class="pagination">';
    public string $full_tag_close = '</ul>';
    
    public string $first_link     = '&lsaquo; First';
    public string $first_tag_open = '<li class="page-item">';
    public string $first_tag_close= '</li>';
    
    public string $last_link      = 'Last &rsaquo;';
    public string $last_tag_open  = '<li class="page-item">';
    public string $last_tag_close = '</li>';
    
    public string $next_link      = '&gt;';
    public string $next_tag_open  = '<li class="page-item">';
    public string $next_tag_close = '</li>';
    
    public string $prev_link      = '&lt;';
    public string $prev_tag_open  = '<li class="page-item">';
    public string $prev_tag_close = '</li>';
    
    public string $cur_tag_open   = '<li class="page-item active"><a href="#" class="page-link" onclick="return false;">';
    public string $cur_tag_close  = '</a></li>';
    
    public string $num_tag_open   = '<li class="page-item">';
    public string $num_tag_close  = '</li>';

    public string $attributes     = 'class="page-link"';

    public function __construct(array $params = []) {
        if (!empty($params)) {
            $this->initialize($params);
        }
    }

    public function initialize(array $params = []): self {
        foreach ($params as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
        return $this;
    }

    public function create_links(): string {
        if ($this->total_rows == 0 || $this->per_page == 0) {
            return '';
        }

        $num_pages = (int) ceil($this->total_rows / $this->per_page);

        if ($num_pages == 1) {
            return '';
        }

        // Descobrindo a página atual
        if ($this->page_query_string) {
            $this->cur_page = (int) ($_GET[$this->query_string_segment] ?? 1);
        }

        if ($this->cur_page < 1) {
            $this->cur_page = 1;
        } elseif ($this->cur_page > $num_pages) {
            $this->cur_page = $num_pages;
        }

        $output = '';
        $base_url = rtrim($this->base_url, '/');

        // Render First
        if ($this->first_link !== '' && $this->cur_page > ($this->num_links + 1)) {
            $output .= $this->first_tag_open . '<a href="' . $this->build_url($base_url, 1) . '" ' . $this->attributes . '>' . $this->first_link . '</a>' . $this->first_tag_close;
        }

        // Render Prev
        if ($this->prev_link !== '' && $this->cur_page !== 1) {
            $i = $this->cur_page - 1;
            $output .= $this->prev_tag_open . '<a href="' . $this->build_url($base_url, $i) . '" ' . $this->attributes . '>' . $this->prev_link . '</a>' . $this->prev_tag_close;
        }

        // Render Pages (Middle Numbers)
        $start = (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - $this->num_links : 1;
        $end   = (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;

        for ($loop = $start; $loop <= $end; $loop++) {
            if ($this->cur_page === $loop) {
                $output .= $this->cur_tag_open . $loop . $this->cur_tag_close;
            } else {
                $output .= $this->num_tag_open . '<a href="' . $this->build_url($base_url, $loop) . '" ' . $this->attributes . '>' . $loop . '</a>' . $this->num_tag_close;
            }
        }

        // Render Next
        if ($this->next_link !== '' && $this->cur_page < $num_pages) {
            $i = $this->cur_page + 1;
            $output .= $this->next_tag_open . '<a href="' . $this->build_url($base_url, $i) . '" ' . $this->attributes . '>' . $this->next_link . '</a>' . $this->next_tag_close;
        }

        // Render Last
        if ($this->last_link !== '' && ($this->cur_page + $this->num_links) < $num_pages) {
            $i = $num_pages;
            $output .= $this->last_tag_open . '<a href="' . $this->build_url($base_url, $i) . '" ' . $this->attributes . '>' . $this->last_link . '</a>' . $this->last_tag_close;
        }

        // Remove double slashes except http://
        $output = preg_replace('#([^:])//+#', '\\1/', $output);

        return $this->full_tag_open . $output . $this->full_tag_close;
    }

    private function build_url(string $base_url, int $page): string {
        // Ignorar o page 1 para a primeira página deixando o link mais limpo (opcional, mantendo padrão pra simplificar)
        // Usa query_string
        if ($this->page_query_string) {
            $get = $_GET;
            if ($page == 1) {
                unset($get[$this->query_string_segment]);
            } else {
                $get[$this->query_string_segment] = $page;
            }
            
            if (empty($get)) {
                return $base_url; // url base pura
            }
            // Verifica se a base url ja tem query (?)
            $separator = (strpos($base_url, '?') !== false) ? '&' : '?';
            
            // Retira a querystring existente da base url temporariamente, senao vai duplicar
            $url_parts = parse_url($base_url);
            $clean_base = $url_parts['path'] ?? $base_url;
            if(isset($url_parts['scheme']) && isset($url_parts['host'])) {
                $clean_base = $url_parts['scheme'] . '://' . $url_parts['host'] . (isset($url_parts['port']) ? ':' . $url_parts['port'] : '') . $clean_base;
            }

            return $clean_base . '?' . http_build_query($get);
            
        } else {
            // Usa segments na URL padrão (ex: /posts/index/2)
            if ($page == 1) {
                return $base_url;
            }
            return rtrim($base_url, '/') . '/' . $page;
        }
    }
}
