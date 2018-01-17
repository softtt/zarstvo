<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 24.02.16
 * Time: 19:44
 */
if (!defined('_PS_VERSION_'))
    exit;

class PH_Blog_Column_Custom_SecondOverride extends PH_Blog_Column_Custom_Second {

    public function preparePosts($nb = 6, $from = null)
    {
        if(!Module::isInstalled('ph_simpleblog') || !Module::isEnabled('ph_simpleblog'))
            return false;

        require_once _PS_MODULE_DIR_ . 'ph_simpleblog/models/SimpleBlogComment.php';
        $posts = SimpleBlogComment::getLastComments($nb);

        return $posts;
    }



}
