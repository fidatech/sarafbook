<?php

/*
| Fonts for Dari and Pashto text. Only the chosen font is sent to the browser, once, and then
| kept by it. English text and numbers always keep the usual font of the system.
|
| FONT_DARI and FONT_PASHTO in .env choose one of the keys of "list".
| faces: [file, format, font-weight]. Licences: public/fonts/LICENSES.txt
*/

return [
    'dari' => env('FONT_DARI', 'vazirmatn'),
    'pashto' => env('FONT_PASHTO', 'vazirmatn'),

    'list' => [
        'vazirmatn' => ['name' => 'Vazirmatn', 'faces' => [['vazirmatn.woff2', 'woff2', '100 900']]],
        'noto-naskh-arabic' => ['name' => 'Noto Naskh Arabic', 'faces' => [['noto-naskh-arabic.woff', 'woff', '400 700']]],
        'noto-kufi-arabic' => ['name' => 'Noto Kufi Arabic', 'faces' => [['noto-kufi-arabic.woff', 'woff', '400 700']]],
    ],
];
