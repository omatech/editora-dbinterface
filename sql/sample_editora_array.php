<?php

$data = array(
	'localized_attributes' => array(),
	'simple_attributes' => array(),
	'original_localized_attributes' => array(),
	'global_filas' => array(),
	'users' => array(
		array('test', 'testpassword', 'Administrator', 'ca')
	),
	'languages' => array(
		10000 => 'ca',
	),
	'groups' => array(
		'Principal' => 1,
		'Secundaris' => 2,
		'Noticies' => 3,
		'Ofertes' => 4,
		'Blocs' => 5
	),
	'classes' => array(
		'Principal' => [
			1 => ['Global', 'Global'],
			10 => ['Home', 'Home'],
		],
		'Secundaris' => [
			25 => ['Page', 'Pàgina'],
			23 => ['SectionApplications', 'Secció Aplicacions'],
			20 => ['Applications', 'Aplicacions'],
			24 => ['SectionSpaces', 'Secció Espais'],
			21 => ['Spaces', 'Espais'],
			22 => ['Doc_link', 'Docs links'],
			26 => ['Shortcut', 'Access directe']
		],
		'Noticies' => [
			30 => ['News', 'Notícies'],
			31 => ['NewsCategory', 'Categoria notícies'],
			32 => ['Redactor', 'Redactor'],
			33 => ['SectionNews', 'Secció notícies']
		],
		'Ofertes' => [
			40 => ['Offers', 'Ofertes'],
			41 => ['OffersCategory', 'Categoria ofertes'],
			42 => ['SectionOffers', 'Secció ofertes']
		],
		'Blocs' => [
			50 => ['BlockApplications', 'Bloc aplicacions'],
			51 => ['BlockSpaces', 'Bloc espais'],
			52 => ['BlockOffers', 'Bloc ofertes'],
			53 => ['BlockPage', 'Bloc pàgina']
		]
	),
	'attributes_string' => array(
		100 => array('nom', 'nom')
	),
	'attributes_multi_lang_string' => array(
		200 => 'titol',
		201 => 'subtitol',
		202 => 'text_link',
		203 => 'text_adjunts'
	),
	'attributes_multi_lang_textarea' => array(
		400 => 'text'
	),
	'attributes_textarea' => array(),
	'attributes_text' => array(),
	'attributes_multi_lang_image' => array(),
	'attributes_image' => array(
		600 => 'imatge_pagina',
		601 => 'imatge_graella',
		602 => 'imatge_bloc'
	),
	'images_sizes' => array(
		600 => '780x',
		601 => '300x200',
		602 => '780x'
	),
	'attributes_multi_lang_file' => array(
		700 => 'fitxer'
	),
	'attributes_date' => array(
		710 => 'data_noticia'
	),
	'attributes_num' => array(),
	'attributes_geolocation' => array(),
	'attributes_url' => array(
		740 => 'link_extern'
	),
	'attributes_multi_lang_url' => array(
		745 => 'link_extern'
	),
	'attributes_file' => array(),
	'attributes_video' => array(
		760 => 'video'
	),
	'attributes_lookup' => array(
		770 => 'icon,70'
	),
	'lookups' => array(
		'70,icon' => [
			7001 => ['mdi-phone-log', 'Teléfono', 'Phone', 'Telèfon'],
			7002 => ['mdi-brush', 'Pincel', 'Brush', 'Pincell'],
			7003 => ['mdi-math-compass', 'Compas', 'Compass', 'Compas'],
			7004 => ['mdi-cellphone-android', 'Smartphone', 'Smartphone', 'Smartphone'],
		]
	),
	'relations' => array(
		1001 => '1,23,24,25,33,42',
		10003 => '10,31',
		10004 => '10,30',
		10002 => '10,26',
		10001 => '10,50,51,52',
		21001 => '21,25',
		23001 => '23,20',
		24001 => '24,21',
		25001 => '25,22',
		25002 => '25,53',
		30001 => '30,32',
		30002 => '30,31',
		30003 => '30,22',
		33001 => '33,30',
		33002 => '33,31',
		40001 => '40,41',
		42001 => '42,41',
		50001 => '50,20',
		50002 => '50,23',
		51001 => '51,21',
		51002 => '51,24',
		52001 => '52,40',
		52002 => '52,42'
	),
	'relation_names' => array(
		1001 => ['Menú principal', 'main_menu'],
		10001 => ['Blocs destacats', 'home_blocks'],
		10002 => ['Accessos directes', 'shortcuts'],
		10003 => ['Categories notícies destacades', 'news_categories'],
		10004 => ['Notícies destacades', 'news_highlights'],
		21001 => ['Pàgines', 'pages'],
		23001 => ['Destacats', 'highlights'],
		24001 => ['Destacats', 'highlights'],
		25001 => ['Adjunts', 'attachments'],
		25002 => ['Blocs', 'blocks'],
		30001 => ['Redactor', 'redactor'],
		30002 => ['Categories', 'categories'],
		30003 => ['Adjunts', 'attachments'],
		33001 => ['Notícies destacades', 'highlights_news'],
		33002 => ['Categories destacades', 'highlights_categories'],
		40001 => ['Categories', 'categories'],
		42001 => ['Categories destacades', 'highlights_categories'],
		50001 => ['Aplicacions destacades', 'highlights'],
		50002 => ['Link veure mes', 'calltoaction'],
		51001 => ['Espais destacats', 'highlights'],
		51002 => ['Link veure mes', 'calltoaction'],
		52001 => ['Ofertes destacades', 'highlights'],
		52002 => ['Link veure mes', 'calltoaction']
	),
	'attributes_classes' => array(
		1 => '2,200',
		10 => '2,200',
		20 => '740,200,201',
		21 => '740,200,201',
		22 => '200,700-745',
		23 => '2,200',
		24 => '2,200',
		25 => '600,2,200,400,203',
		26 => '770-740,200,201',
		30 => '710,600-601,2,200,400,203',
		31 => '200',
		32 => '100',
		33 => '2,200',
		40 => '601-740,200-745',
		41 => '200',
		42 => '2,200',
		50 => '200,202',
		51 => '200,202',
		52 => '200,202',
		53 => '602-760,400,'
	),
	'roles' => array(
	//array('id' => 3, 'name' => 'testrole', 'classes' => '10,20,30'),
	)
);

