<?php

if (!defined('_PS_ADMIN_DIR_'))
    define('_PS_ADMIN_DIR_', getcwd());
include(_PS_ADMIN_DIR_.'/../config/config.inc.php');

if (isset($_GET['secure_key']))
{
    $secureKey = md5(_COOKIE_KEY_.Configuration::get('PS_SHOP_NAME'));
    if (!empty($secureKey) && $secureKey === $_GET['secure_key'])
    {

        $import = new Import();
        $import_filename = '';
        $import_file = '';
        foreach (glob(__DIR__ . '/../export_import/import/discounts/*.csv') as $file) {
            $filename = substr($file, strrpos($file, '/') + 1);
            if ($filename > $import_filename || $import_filename = '') {
                $import_file = $file;
                $import_filename = $filename;
            } else {
                rename($file, __DIR__ . '/../export_import/import/discounts/DELETED/'.$filename);
            }
        }
        if ($import_filename) {
            $import->importDiscounts($import_file);
            rename($file, __DIR__ . '/../export_import/import/discounts/IMPORTED/'.$import_filename);
            $import->writeLogToFile();
        }

        echo 'CRON DISCOUNTS UPLOAD FINISHED';
    }
}
