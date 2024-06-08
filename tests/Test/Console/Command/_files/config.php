<?php return [
    'from'      => [__DIR__ . '/log1.jsonl', __DIR__ . '/log2.ltsv'],
    'select'    => [
        'colA',
        'colC' => fn($fields) => strtoupper($fields['colC']),
    ],
    'where'     => fn($fields) => $fields['colA'] < 600,
    'group-by'  => ['colA', fn($fields) => $fields['colB'][0]],
    'offset'    => 1,
    'limit'     => 6,
    'output'    => 'ltsv',
    'nocomment' => null,
];
