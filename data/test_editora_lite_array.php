<?php

$data = array(
	'truncate_users'=>false,
	'users' => array(
		// name, type, default lang, rol_id, O|U (Omatech-super-admin or normal user)
		array('omatech', 'Omatech super-admin', 'ca', 1, 'O'),
		array('test', 'Administrator', 'ca', 2, 'U')
	),
	'languages' => array(
		10000 => 'ca',
		20000 => 'es',
		30000 => 'en'
	),
	'groups' => array(
		'Principal' => 1,
		'Secundaris' => 2,
		'Elements' => 3
	),
	'classes' => array(
		'Principal' => [
			1 => ['Global', 'Global'],
			10 => ['Home', 'Home'],
		],
		'Secundaris' => [
			20 => ['Pages', 'Pàgines', 'Páginas', 'Pages'],
			21 => ['News', 'Noticies', 'Noticias', 'News'],
			22 => ['People', 'Persones', 'Personas', 'People']	
		],
		'Elements' => [
			30 => ['Blocks', 'Blocs', 'Bloques', 'Blocks'],
			31 => ['Links', 'Links'],
			32 => ['Docs', 'Docs']
		]
	),
	'attributes_order_string' => array(
		//id=>array(tag, caption_ca, caption_es, caption_en OR id=>tag
		101 => array('surname', 'Cognoms', 'Apellidos', 'Surname')
	),
	'attributes_order_date' => array(
		//id=>array(tag, caption_ca, caption_es, caption_en OR id=>tag
		102 => array('date', 'Data noticia', 'Fecha noticia', 'News Date')
	),	
	'attributes_string' => array(
		//id=>array(tag, caption_ca, caption_es, caption_en OR id=>tag
		100 => array('nom', 'Nom', 'Nombre', 'Name')
	),
	'attributes_multi_lang_string' => array(
		//id=>array(tag, caption_ca, caption_es, caption_en OR id=>tag
		200 => ['title', 'Títol', 'Título', 'Title'],
		201 => ['subtitle', 'Subtítol', 'Subtítulo', 'Subtitle']
	),
	'attributes_textarea' => array(
	//id=>array(tag, caption_ca, caption_es, caption_en OR id=>tag
	),
	'attributes_text' => array(
		//id=>array(tag, caption_ca, caption_es, caption_en OR id=>tag		
	),
	'attributes_date' => array(
	),
	'attributes_num' => array(),
	'attributes_geolocation' => array(),
	'attributes_url' => array(
		740 => 'url'
	),
	'attributes_file' => array(
		700 => 'file'	
	),
	'attributes_video' => array(
		760 => 'video'
	),
	'attributes_image' => array(
		//id=>array(tag, caption_ca, caption_es, caption_en OR id=>tag
		600 => ['header_picture', 'Imatge capçalera', 'Imágen cabecera', 'Header Picture'],
		601 => ['profile_picture', 'Imatge perfil', 'Imágen perfil', 'Profile picture'],
		602 => ['portrait_picture', 'Imatge retrat', 'Imágen retrato', 'Portrait picture'],
		603 => ['block_image', 'Imatge bloc', 'Imágen Bloque', 'Block Picture']
	),
	'images_sizes' => array(
		600 => '780x',
		601 => '150x150',
		602 => '300x',
		603 => '400x'
	),
	'attributes_lookup' => array(
		770 => ['image_position,70', 'Posició imatge', 'Posición imágen', 'Image position']
	),
	'lookups' => array(
		'70,image_position' => [
			7001 => ['left', 'Esquerra', 'Izquierda', 'Left'],
			7002 => ['right', 'Dreta', 'Derecha', 'Right']
		]
	),

	'attributes_multi_lang_url' => array(
	),
	'attributes_multi_lang_image' => array(
		//id=>array(tag, caption_ca, caption_es, caption_en OR id=>tag		
	),
	'attributes_multi_lang_textarea' => array(
		//id=>array(tag, caption_ca, caption_es, caption_en OR id=>tag
		400 => 'text',
		401 => 'intro',
		402 => 'bio',
		403 => 'quote'
	),
	'attributes_multi_lang_file' => array(
	),


	'relations' => array(
		1001 => '1,20,21,22',
		10001 => '10,30',
		10002 => '10,20',
		10003 => '10,21',
		
		20001 => '20,30',
		
		21001 => '21,30',
		21002 => '21,22',
		
		22001 => '22,30',

		30001 => '30,31',
		30002 => '30,32',
		30003 => '30,22',
		
		31001 => '31,1,20,21,22'
		
	),
	'relation_names' => array(
		1001 => ['Menú principal', 'main_menu'],
		10001 => ['Blocs destacats', 'home_blocks'],
		10002 => ['Accessos directes', 'shortcuts'],
		10003 => ['Notícies destacades', 'news_highlights'],
		
		20001 => ['Blocks', 'blocks'],
		
		21001 => ['Blocks', 'blocks'],
		21002 => ['People', 'people'],
		
		22001 => ['Blocks', 'blocks'],
		
		30001 => ['Links','links'],
		30002 => ['Documents','documents'],
		30003 => ['People','people'],
		
		31001 => ['InternalLink','internal_link']

	),
	// attribute=2 nice_url
	// class_id 1=Home and 10=Global
	'attributes_classes' => array(
		1 => '2,200',
		10 => '2,200',
		20 => '2,200,201,600,401,400',
		21 => '2,201,202,102,600,401,400',
		22 => '2,100,101,402,403,601,602',
		30 => '201,202,400,403,70,603,700',
		31 => '201,740',
		32 => '201,700'
	),
	'roles' => array(
	//array('id' => 3, 'name' => 'testrole', 'classes' => '10,20,30'),
    /*array('id' => 4, 'name' => 'testrolearray',
        'classes' => [
            '10' => ["browseable" => 'N', "status1" => 'N', "status2" => 'N'],
            '20' => [],
            '30' => ["editable" => 'N', "status3" => 'N'],
        ]
    ),*/
	)
);

