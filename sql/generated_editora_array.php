<?php
		$data = [
'truncate_users'=>false,
'users'=>[
	['omatech','Omatech super-admin','ca',1,'O'],		
	['test','Administrator','ca',2,'U']
	],
'languages'=>[
	10000 => 'ca',
	20000 => 'es',
	30000 => 'en'
	],
'groups'=>[
	'Principal' => 1,
	'Secundaris' => 2,
	'Noticies' => 3,
	'Ofertes' => 4,
	'Blocs' => 5
	],
'classes'=>[
	'Principal' => [1 => ['Global','Global'],
		10 => ['Home','Home']
		],


	'Secundaris' => [25 => ['Page','Pàgina'],
		23 => ['SectionApplications','Secció Aplicacions'],
		20 => ['Applications','Aplicacions'],
		24 => ['SectionSpaces','Secció Espais'],
		21 => ['Spaces','Espais'],
		22 => ['Doc_link','Docs links'],
		26 => ['Shortcut','Access directe']
		],


	'Noticies' => [30 => ['News','Notícies'],
		31 => ['NewsCategory','Categoria notícies'],
		32 => ['Redactor','Redactor'],
		33 => ['SectionNews','Secció notícies']
		],


	'Ofertes' => [40 => ['Offers','Ofertes'],
		41 => ['OffersCategory','Categoria ofertes'],
		42 => ['SectionOffers','Secció ofertes']
		],


	'Blocs' => [50 => ['BlockApplications','Bloc aplicacions'],
		51 => ['BlockSpaces','Bloc espais'],
		52 => ['BlockOffers','Bloc ofertes'],
		53 => ['BlockPage','Bloc pàgina']
		],

	],
'attributes_string'=>[
	100 => ['nom','Nom','Nombre','Name']
	],
'attributes_date'=>[
	710 => ['data_noticia','Data Noticia','Data Noticia','Data Noticia']
	],
'attributes_url'=>[
	740 => ['link_extern','Link Extern','Link Extern','Link Extern']
	],
'attributes_video'=>[
	760 => ['video','Video','Video','Video']
	],
'attributes_image'=>[
	600 => ['imatge_pagina','Imatge pàgina','Imágen Página','Page Image'],		
	601 => ['imatge_graella','Imatge Graella','Imatge Graella','Imatge Graella'],		
	602 => ['imatge_bloc','Imatge Bloc','Imatge Bloc','Imatge Bloc']
	],
'attributes_lookup'=>[
	770 => ['icon,70','Icona','Icono','Icon']
	],
'lookups'=>[
	'70,icon' => [7001 => ['mdi-phone-log','Telèfon','Teléfono','Phone'],
		7002 => ['mdi-brush','Pincell','Pincel','Brush'],
		7003 => ['mdi-math-compass','Compas','Compas','Compass'],
		7004 => ['mdi-cellphone-android','Smartphone','Smartphone','Smartphone']
		],

	],
'attributes_multi_lang_string'=>[
	200 => ['titol','Títol','Título','Title'],		
	201 => ['subtitol','Subtítol','Subtítulo','Subtitle'],		
	202 => ['text_link','Text de l\'enllaç','Texto Del Enlace','Text Link'],		
	203 => ['text_adjunts','Text Adjunts aaa','Text Adjunts Aaa','Text Adjunts Aaa']
	],
'attributes_multi_lang_textarea'=>[
	400 => ['text','Text','Text','Text']
	],
'attributes_multi_lang_file'=>[
	700 => ['fitxer','Fitxer','Fitxer','Fitxer']
	],
'images_sizes'=>[
	600 => '780x',
	601 => '300x200',
	602 => '780x'
	],
'relations'=>[
	1001 => '1,23,24,25,33,42',
	10001 => '10,50,51,52',
	10002 => '10,26',
	10003 => '10,31',
	10004 => '10,30',
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
	],
'relation_names'=>[
	1001 => ['Menú principal','main_menu'],		
	10001 => ['Blocs destacats','home_blocks'],		
	10002 => ['Accessos directes','shortcuts'],		
	10003 => ['Categories notícies destacades','news_categories'],		
	10004 => ['Notícies destacades','news_highlights'],		
	21001 => ['Pàgines','pages'],		
	23001 => ['Destacats','highlights'],		
	24001 => ['Destacats','highlights'],		
	25001 => ['Adjunts','attachments'],		
	25002 => ['Blocs','blocks'],		
	30001 => ['Redactor','redactor'],		
	30002 => ['Categories','categories'],		
	30003 => ['Adjunts','attachments'],		
	33001 => ['Notícies destacades','highlights_news'],		
	33002 => ['Categories destacades','highlights_categories'],		
	40001 => ['Categories','categories'],		
	42001 => ['Categories destacades','highlights_categories'],		
	50001 => ['Aplicacions destacades','highlights'],		
	50002 => ['Link veure mes','calltoaction'],		
	51001 => ['Espais destacats','highlights'],		
	51002 => ['Link veure mes','calltoaction'],		
	52001 => ['Ofertes destacades','highlights'],		
	52002 => ['Link veure mes','calltoaction']
	],
'attributes_classes'=>[
	1 => '2,200',
	10 => '2,200',
	20 => '740,200,201',
	21 => '740,200,201',
	22 => '200,700,200,700-745',
	23 => '2,200',
	24 => '2,200',
	25 => '600,2,200,400,203',
	26 => '770,770-740,200,201',
	30 => '710,600,710,600-601,2,200,400,203',
	31 => 200,
	32 => 100,
	33 => '2,200',
	40 => '601,601-740,200,601,601-740,200-745',
	41 => 200,
	42 => '2,200',
	50 => '200,202',
	51 => '200,202',
	52 => '200,202',
	53 => '602,602-760,400'
	]
];
