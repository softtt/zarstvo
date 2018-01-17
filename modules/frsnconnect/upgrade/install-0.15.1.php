<?php
/**
  * Social Network connect modules
  * frsnconnect 0.15 by froZZen
  */

if (!defined('_PS_VERSION_'))
  exit;
 
function upgrade_module_0_15_1($object) {

    $sql = '
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'sn_service` (
        `id_sn_service` int(5) unsigned NOT NULL,
        `sn_service_name` varchar(10) COLLATE utf8_unicode_ci NOT NULL COMMENT "name",
        `sn_service_name_full` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT "full name",
        `sn_service_key_id` text COLLATE utf8_unicode_ci NOT NULL,
        `sn_service_key_secret` text COLLATE utf8_unicode_ci NOT NULL,
        `class` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT "class name",
        `active` int(11) NOT NULL DEFAULT "0",
        PRIMARY KEY (`id_sn_service`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT="SN services list"';
    Db::getInstance()->Execute(trim($sql));
                
    $sql = '
        INSERT INTO `'._DB_PREFIX_.'sn_service` 
        (`id_sn_service`, `sn_service_name`, `sn_service_name_full`, `class`) VALUES
        (1, "fb", "Facebook", "FbOAuthSrv"),
        (2, "vk", "VKontakte", "VKOAuthSrv"),
        (3, "ok", "Odnoklassniki", "OkOAuthSrv"),
        (4, "tw", "Twitter", "TwOAuthSrv"),
        (5, "gl", "Google+", "GlOAuthSrv"),
        (6, "ya", "Yandex", "YaOpIDSrv"),
        (7, "mr", "MailRu", "MrOAuthSrv")';
    Db::getInstance()->Execute(trim($sql));

    $sql = '
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'sn_customer` (
        `id_customer` int(10) NOT NULL,
        `id_sn_service` int(10) NOT NULL,
        `sn_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
        KEY `id_customer` (`id_customer`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';
    Db::getInstance()->Execute(trim($sql));
 
    
    return true;
    
}

?>