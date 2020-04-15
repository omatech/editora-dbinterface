<?php

$data = [
    'attributes_params' => true,//if true use new editora generator
	'truncate_users'=>false,
	'users' => [
		// name, type, default lang, rol_id, O|U (Omatech-super-admin or normal user)
		['omatech', 'Omatech', 'ca', 1, 'O'],
		['test', 'Administrator', 'ca', 2, 'U']
    ],
    'roles' => [
        //['id' => 3, 'name' => 'testrole', 'classes' => '10,20,30'],
    ],

    'languages' => [
		10000 => 'ca',
		20000 => 'es',
		30000 => 'en',
	],
	'groups' => [
        1 => [
                'Principal',
                'caption'=>['Principal', 'Principal', 'Principal'],
                'classes' => [
                    1 => [
                        'Global',
                        'caption'=>['Global'],
                        'attributes'=>['2,200'],
                        'relations' => [
                            1001  => ['main_menu', 'childs'=>'20,21,22', 'caption' =>['Menú principal ca', 'Menú principal', 'Main menu']],
                        ],
                        'editable'=>false
                    ],
                    10 => [
                        'Home',
                        'caption'=>['Home'],
                        'attributes'=>['2,101,102,100,200,201,249,250,251,252,253,254,255,256,257,258,259,260,261,601,602,770'],
                        'relations' => [
                            10001 => ['home_blocks', 'childs'=>'30', 'caption' =>['Blocs destacats', 'Bloques destacados', 'Home blocks']],
                            10002 => ['shortcuts', 'childs'=>'20', 'caption' =>['Accessos directes', 'Accesos directos', 'Shortcuts']],
                            10003 => ['news_highlights', 'childs'=>'21', 'caption' =>['Notícies destacades', 'Notícias destacadas', 'News']],
                        ],
                        'editable'=>true
                    ],
                ],
            ],
		2 => [
                'Secundaris',
                'caption'=>['Secundaris', 'Secundarios', 'Secondary'],
                'classes' => [
                    20 => [
                        'Pages',
                        'caption'=>['Pàgines', 'Páginas', 'Pages'],
                        'attributes'=>['2,200,201,600'],
                        'relations' => [
                            20001 => ['blocks', 'childs'=>'30', 'caption' =>['Bloques', 'Bloques', 'Blocks']],
                        ],
                    ],
                    21 => [
                        'News',
                        'caption'=>['Noticies', 'Noticias', 'News'],
                        'attributes'=>['2,200'],
                        'relations' => [
                            21001 => ['blocks', 'childs'=>'30', 'caption' =>['Bloques', 'Bloques', 'Blocks']],
                            21002 => ['people', 'childs'=>'22', 'caption' =>['Bloques', 'Bloques', 'Blocks']],
                        ],
                    ],
                    22 => [
                        'People',
                        'caption'=>['Persones', 'Personas', 'People'],
                        'attributes'=>['2,200'],
                        'relations' => [
                            22001 => ['blocks', 'childs'=>'30', 'caption' =>['Bloques', 'Bloques', 'Blocks']],
                        ],
                    ]
                ],
            ],
		3 => [
                'Elements',
                'caption'=>['Elements', 'Elementos', 'Elements'],
                'classes' => [
                    30 => [
                        'Blocks',
                        'caption'=>['Blocs', 'Bloques', 'Blocks'],
                        'attributes'=>['201'],
                        'relations' => [
                            30001 => ['links', 'childs'=>'31', 'caption' =>['Bloques', 'Bloques', 'Blocks']],
                            30002 => ['documents', 'childs'=>'32', 'caption' =>['Bloques', 'Bloques', 'Blocks']],
                            30003 => ['people', 'childs'=>'22', 'caption' =>['Bloques', 'Bloques', 'Blocks']],
                        ],
                    ],
                    31 => [
                        'Links',
                        'caption'=>['Links'],
                        'attributes'=>['201'],
                        'relations' => [
                            31001 => ['internal_link', 'childs'=>'1,20,21,22', 'caption' =>['Bloques', 'Bloques', 'Blocks']],
                        ],
                    ],
                    32 => [
                        'Docs',
                        'caption'=>['Docs'],
                        'attributes'=>['201']
                    ]
                ]
            ],
	],

	'attributes_order_string' => [
		101 => ['surname', 'caption'=>['Cognoms', 'Apellidos', 'Surname']],
	],
	'attributes_order_date' => [
		102 => ['order_date', 'caption'=>['Data noticia', 'Fecha noticia', 'News Date']],
	],
	'attributes_string' => [
		100 => ['nom', 'caption'=>['Nom ', 'Nombre', 'Name']],
	],
	'attributes_multi_lang_string' => [
		200 => ['title', 'caption'=>['Títol', 'Título', 'Title'], 'description'=>'una prueba de descripción'],
		201 => ['subtitle', 'caption'=>['Subtítol', 'Subtítulo', 'Subtitle']],
	],
	'attributes_textarea' => [
        249 => ['textarea', 'caption'=>['textarea ca', 'textarea es', 'textarea en']],
    ],
    'attributes_multi_lang_textarea' => [
		250 => ['lang_textarea', 'caption'=>['lang_textarea ca', 'lang_textarea es', 'lang_textarea en']],
	],
	'attributes_text' => [
		251 => ['text', 'caption'=>['text ca', 'text es', 'text en']],
    ],
    'attributes_multi_lang_text' => [
		280 => ['lang_text', 'caption'=>['lang_text ca', 'lang_text es', 'lang_text en']],
	],
	'attributes_date' => [
        252 => ['date', 'caption'=>['date ca', 'date es', 'date en']],
	],

	'attributes_color' => [
        253 => ['color', 'caption'=>['color ca', 'color es', 'color en']],
    ],

	'attributes_num' => [
        254 => ['num', 'caption'=>['num ca', 'num es', 'num en']],
    ],
	'attributes_geolocation' => [
        255=> ['geolocation', 'caption'=>['geolocation ca', 'geolocation es', 'geolocation en']],
    ],
	'attributes_url' => [
        256=> ['url', 'caption'=>['url ca', 'url es', 'url en']],
    ],
    'attributes_multi_lang_url' => [
        257=> ['lang_url', 'caption'=>['lang_url ca', 'lang_url es', 'lang_url en']],
	],
	'attributes_file' => [
        258=> ['file', 'caption'=>['file ca', 'file es', 'file en']],
    ],
    'attributes_multi_lang_file' => [
        259=> ['lang_file', 'caption'=>['lang_file ca', 'lang_file es', 'lang_file en']],
    ],
	'attributes_video' => [
		260=> ['video', 'caption'=>['video ca', 'video es', 'video en']],
	],
    'attributes_multi_lang_video' => [
        261=> ['lang_video', 'caption'=>['lang_video ca', 'lang_video es', 'lang_video en']],
    ],
    'attributes_image' => [
        601 => ['profile_picture', 'caption' =>['Imatge perfil', 'Imágen perfil', 'Profile picture'], 'params'=>['size'=>['150x200']]],
    ],
    'attributes_multi_lang_image' => [
        602 => ['lang_picture', 'caption' =>['Imatge ', 'Imágen ', 'Profile '], 'params'=>['size'=>['700x200']]],
    ],
    'attributes_grid_image' => [
        603 => ['grid_image', 'caption' =>['grid_image ca', 'grid_image es', 'grid_image en'], 'params'=>['size'=>['700x200']]],
    ],
	'attributes_lookup' => [
        770 => ['image_position', 'caption' =>['Posició imatge', 'Posición imágen', 'Image position'],
                'params'=>['lookup'=>[
                    7001 => ['left', 'Esquerra', 'Izquierda', 'Left'],
                    7002 => ['right', 'Dreta', 'Derecha', 'Right']
                ]]]
	],

];



