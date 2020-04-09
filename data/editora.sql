SET FOREIGN_KEY_CHECKS=0;
-- ----------------------------
-- Table structure for omp_attributes
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `caption` varchar(200) NOT NULL DEFAULT '',
  `description` text,
  `tag` varchar(100) NOT NULL DEFAULT '',
  `type` varchar(1) NOT NULL DEFAULT 'S',
  `lookup_id` int DEFAULT NULL,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  `max_length` int DEFAULT '0',
  `img_width` int DEFAULT NULL,
  `img_height` int DEFAULT NULL,
  `language` varchar(3) NOT NULL DEFAULT 'ALL',
  `caption_ca` varchar(100) DEFAULT NULL,
  `caption_es` varchar(100) DEFAULT NULL,
  `caption_en` varchar(100) DEFAULT NULL,
  `params` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `omp_attributes_u1` (`name`) USING BTREE,
  	KEY `omp_attributes_n1` (`tag`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_class_attributes
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_class_attributes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `class_id` int unsigned DEFAULT '0',
  `atri_id` int DEFAULT '0',
  `rel_id` int unsigned DEFAULT '0',
  `tab_id` int DEFAULT '-1',
  `fila` int unsigned DEFAULT '0',
  `columna` int unsigned DEFAULT '0',
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  `img_width` int DEFAULT NULL,
  `img_height` int DEFAULT NULL,
  `caption_position` varchar(10) NOT NULL DEFAULT 'left',
  `ordre_key` int unsigned DEFAULT NULL,
  `mandatory` varchar(1) DEFAULT 'N',
  `detail` varchar(1) DEFAULT 'N',
  PRIMARY KEY (`id`),
  KEY `omp_class_attributes_n1` (`class_id`) USING BTREE,
  KEY `omp_class_attributes_n2` (`atri_id`) USING BTREE,
  KEY `omp_class_attributes_n3` (`rel_id`) USING BTREE,
  KEY `omp_class_attributes_n4` (`tab_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_class_groups
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_class_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `caption` varchar(200) NOT NULL DEFAULT '',
  `caption_ca` varchar(200) DEFAULT NULL,
  `caption_es` varchar(200) DEFAULT NULL,
  `caption_en` varchar(200) DEFAULT NULL,
  `ordering` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_classes
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_classes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text,
  `tag` varchar(200) NOT NULL DEFAULT '',
  `grp_id` int NOT NULL DEFAULT '1',
  `grp_order` int DEFAULT NULL,
  `name_ca` varchar(100) DEFAULT NULL,
  `name_es` varchar(100) DEFAULT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `recursive_clone` char(20) DEFAULT 'N',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE,
	KEY `omp_classes_n1` (`tag`) USING BTREE,
	KEY `omp_classes_n2` (`grp_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_instances
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_instances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL DEFAULT '0',
  `key_fields` varchar(250) DEFAULT NULL,
  `status` varchar(1) NOT NULL DEFAULT 'P', /*(Pending Validated Ok)*/
  `publishing_begins` datetime DEFAULT CURRENT_TIMESTAMP,
  `publishing_ends` datetime DEFAULT NULL,
  `creation_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `default_draw` varchar(50) DEFAULT NULL,
  `nice_url` varchar(250) DEFAULT NULL,
  `external_id` varchar(250) DEFAULT NULL,
  `batch_id` varchar(250) DEFAULT NULL,
  `order_string` varchar(250) DEFAULT NULL,
  `order_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `omp_instances_n1` (`class_id`) USING BTREE,
  KEY `omp_instances_n2` (`publishing_begins`,`publishing_ends`),
  KEY `omp_instances_n3` (`status`),
  KEY `omp_instances_n4` (`key_fields`),
  KEY `omp_instances_n5` (`external_id`),
  KEY `omp_instances_n6` (`batch_id`),
  KEY `omp_instances_n7` (`order_string`),
  KEY `omp_instances_n8` (`order_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_instances_backup
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_instances_backup` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inst_id` int NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  `xml_cache` text CHARACTER SET utf8mb4 NOT NULL,
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `omp_instances_backup_n1` (`inst_id`,`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_instances_cache
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_instances_cache` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inst_id` int NOT NULL DEFAULT '0',
  `language` varchar(10) NOT NULL DEFAULT '',
  `xml_cache_r` text NOT NULL,
  `xml_cache_d` text NOT NULL,
  `search_field` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `omp_instances_cache_u1` (`inst_id`,`language`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_lookups
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_lookups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `type` enum('L','R','C') NOT NULL DEFAULT 'L',
  `default_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_lookups_values
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_lookups_values` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lookup_id` int NOT NULL DEFAULT '0',
  `ordre` int NOT NULL DEFAULT '0',
  `value` varchar(255) NOT NULL DEFAULT '',
  `value_es` varchar(255) NOT NULL DEFAULT '',
  `value_en` varchar(255) DEFAULT '',
  `value_ca` varchar(255) NOT NULL DEFAULT '',
  `caption_es` varchar(255) NOT NULL DEFAULT '',
  `caption_en` varchar(255) NOT NULL DEFAULT '',
  `caption_ca` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `omp_lookups_values_n1` (`lookup_id`,`ordre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_niceurl
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_niceurl` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inst_id` int DEFAULT NULL,
  `language` varchar(3) DEFAULT NULL,
	`niceurl` varchar(255) character set latin1 DEFAULT NULL ,
  PRIMARY KEY (`id`),
  UNIQUE KEY `omp_niceurl_u1` (`niceurl`,`language`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_relation_instances
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_relation_instances` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `rel_id` int unsigned DEFAULT '0',
  `parent_inst_id` int unsigned DEFAULT '0',
  `child_inst_id` int unsigned DEFAULT '0',
  `weight` double DEFAULT NULL,
  `relation_date` datetime DEFAULT NULL,
  `clone_session` varchar(255) DEFAULT NULL,
  `cloned_instance` varchar(255) DEFAULT NULL,
  `external_id` varchar(110) DEFAULT NULL,
  `batch_id` varchar(110) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `omp_relation_instances_n1` (`child_inst_id`) USING BTREE,
  KEY `omp_relation_instances_n2` (`parent_inst_id`) USING BTREE,
  KEY `omp_relation_instances_n3` (`external_id`) USING BTREE,
  KEY `omp_relation_instances_n4` (`batch_id`) USING BTREE,
  KEY `omp_relation_instances_n5` (`rel_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ----------------------------
-- Table structure for omp_relations
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_relations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `caption` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `language` varchar(3) NOT NULL DEFAULT 'ALL',
  `tag` varchar(100) NOT NULL DEFAULT '',
  `parent_class_id` int NOT NULL DEFAULT '0',
  `child_class_id` int NOT NULL DEFAULT '0',
  `multiple_child_class_id` varchar(255) DEFAULT NULL,
  `order_type` varchar(1) NOT NULL DEFAULT 'M',
  `join_icon` char(1) DEFAULT 'Y',
  `create_icon` char(1) DEFAULT 'N',
  `join_massive` char(1) DEFAULT 'N',
  `massive_file` varchar(255) DEFAULT NULL,
  `caption_ca` varchar(255) DEFAULT NULL,
  `caption_es` varchar(255) DEFAULT NULL,
  `caption_en` varchar(255) DEFAULT NULL,
  `autocomplete` char(1) DEFAULT 'N',
  PRIMARY KEY (`id`),
  KEY `omp_relations_n1` (`parent_class_id`) USING BTREE,
  KEY `omp_relations_n2` (`child_class_id`) USING BTREE,
  KEY `omp_relations_n3` (`name`),
  KEY `omp_relations_n4` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_roles
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rol_name` varchar(50) NOT NULL DEFAULT '',
  `enabled` char(1) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_roles_classes
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_roles_classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL DEFAULT '0',
  `rol_id` int NOT NULL DEFAULT '0',
  `browseable` char(1) DEFAULT NULL,
  `insertable` char(1) DEFAULT NULL,
  `editable` char(1) DEFAULT NULL,
  `deleteable` char(1) DEFAULT NULL,
  `permisos` char(1) DEFAULT NULL,
  `status1` char(1) DEFAULT NULL,
  `status2` char(1) DEFAULT NULL,
  `status3` char(1) DEFAULT NULL,
  `status4` char(1) DEFAULT NULL,
  `status5` char(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `omp_roles_classes_n1` (`class_id`) USING BTREE,
  KEY `omp_roles_classes_n2` (`rol_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_search
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_search` (
  `id` int NOT NULL AUTO_INCREMENT,
  `text` text,
  `inst_id` int NOT NULL,
  `class_id` int NOT NULL,
  `atri_id` int NOT NULL,
  `language` varchar(10) NOT NULL,
  `title` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `omp_search_f1` (`text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_static_text
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_static_text` (
  `id` int NOT NULL AUTO_INCREMENT,
  `text_key` varchar(110) NOT NULL DEFAULT '',
  `language` varchar(3) DEFAULT NULL,
  `text_value` text,
  PRIMARY KEY (`id`),
  KEY `omp_static_text_n1` (`text_key`,`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ----------------------------
-- Table structure for omp_tabs
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_tabs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `name_ca` varchar(100) DEFAULT NULL,
  `name_es` varchar(100) DEFAULT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `ordering` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_user_instances
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_user_instances` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `inst_id` int NOT NULL DEFAULT '0',
  `tipo_acceso` enum('A','F') DEFAULT 'A',
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `omp_user_instances_n1` (`user_id`,`tipo_acceso`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for omp_users
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(100) NOT NULL DEFAULT '',
  `complete_name` text,
  `rol_id` int NOT NULL DEFAULT '0',
  `language` varchar(3) DEFAULT 'es',
  `tipus` enum('U','O') NOT NULL DEFAULT 'U',
  `hashed_password` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `omp_users_u1` (`username`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ----------------------------
-- Table structure for omp_values
-- ----------------------------
CREATE TABLE IF NOT EXISTS `omp_values` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inst_id` int NOT NULL DEFAULT '0',
  `atri_id` int NOT NULL DEFAULT '0',
  `text_val` text,
  `date_val` datetime DEFAULT NULL,
  `num_val` double DEFAULT NULL,
  `img_info` varchar(20) DEFAULT NULL,
  `json_val` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `omp_values_n1` (`inst_id`,`atri_id`) USING BTREE,
  KEY `omp_values_n2` (`date_val`) USING BTREE,
  KEY `omp_values_n3` (`num_val`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
