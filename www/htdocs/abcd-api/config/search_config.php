<?php
// Mapeia os parâmetros da API para os prefixos de índice do CISIS
return [
    'marc' => [
        // API param => CISIS Index Prefix
        'author'   => 'AU_', // Campo 100
        'title'    => 'TT_', // Campo 245
        'subject'  => 'MA_', // Campo 650
        'words'  => 'TW_', // Campo 650
        // Adicione outros campos que você indexou. Ex: 'publisher' => 'ED_'
    ],
    'dubcore' => [
        'title'    => 'TI_', // Assumindo que o índice para o campo 1 é TI_
        'creator'  => 'AU_', // Assumindo que o índice para o campo 2 é AU_
        'words'  => 'TW_', // Campo 650
    ]
];