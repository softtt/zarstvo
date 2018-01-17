<?php
/**
 * @author    Krystian Podemski <podemski.krystian@gmail.com>
 * @site
 * @copyright  Copyright (c) 2013-2014 Krystian Podemski - www.PrestaHome.com
 * @license    You only can use module, nothing more!
 */
require_once _PS_MODULE_DIR_ . 'ph_simpleblog/ph_simpleblog.php';

class SimpleBlogComment extends ObjectModel
{
    private static $commentHierarchy = array();
    
    public $id_simpleblog_comment;
    public $id_simpleblog_post;
    public $id_parent = 0;
    public $id_customer;
    public $id_guest;
    public $name;
    public $email;
    public $comment;
    public $active = 0;
    public $ip;
    public $date_add;
    public $date_upd;
   
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'simpleblog_comment',
        'primary' => 'id_simpleblog_comment',
        'multilang' => false,
        'fields' => array(   
            'id_simpleblog_comment' =>  array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'id_simpleblog_post'    =>  array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'id_parent'             =>  array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'id_customer'           =>  array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'id_guest'              =>  array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'name'                  =>  array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255),
            'email'                 =>  array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 140),
            'comment'               =>  array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'),
            'active'                =>  array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'ip'                    =>  array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255),
            'date_add'              =>  array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd'              =>  array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        )
    );
        
    public function __construct($id_simpleblog_comment = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id_simpleblog_comment, $id_lang, $id_shop);        
    }

    public static function getComments($id_simpleblog_post, $withHierarchy = true)
    {
        $response = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT id_simpleblog_comment, id_parent
            FROM '._DB_PREFIX_.'simpleblog_comment
            WHERE id_simpleblog_post = '.$id_simpleblog_post.'
            AND active = 1'
            );
        
        if ($withHierarchy)
            return self::renderComments($response);
        else 
            return $response;
    }

    public static function renderComments(&$comments, $parent = 0, $depth = 0)
    {
        foreach ($comments as $key => $comment) {
            if ($comment['id_parent'] == $parent){
                $SimpleBlogComment = new SimpleBlogComment($comment['id_simpleblog_comment']);
                
                self::$commentHierarchy[$comment['id_simpleblog_comment']]['depth'] = $depth;
                self::$commentHierarchy[$comment['id_simpleblog_comment']]['id'] = $SimpleBlogComment->id_simpleblog_comment;
                self::$commentHierarchy[$comment['id_simpleblog_comment']]['name'] = $SimpleBlogComment->name;
                self::$commentHierarchy[$comment['id_simpleblog_comment']]['email'] = $SimpleBlogComment->email;
                self::$commentHierarchy[$comment['id_simpleblog_comment']]['comment'] = $SimpleBlogComment->comment;
                self::$commentHierarchy[$comment['id_simpleblog_comment']]['date_add'] = $SimpleBlogComment->date_add;
                self::$commentHierarchy[$comment['id_simpleblog_comment']]['id_parent'] = $parent;
            
                unset($comments[$key]);
                self::renderComments($comments, $comment['id_simpleblog_comment'], $depth + 1);
            }
        }
        
        reset($comments);
        return self::$commentHierarchy;
    }

    public static function getCommentsCount($id_simpleblog_post)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT COUNT(id_simpleblog_comment)
            FROM '._DB_PREFIX_.'simpleblog_comment
            WHERE id_simpleblog_post = '.$id_simpleblog_post.'
            AND active = 1'
        );
    }

    public static function getLastComments($nb = 3)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT id_simpleblog_comment, id_simpleblog_post, id_customer, name as author, comment
            FROM '._DB_PREFIX_.'simpleblog_comment
            WHERE active = 1
            ORDER BY date_add DESC
            LIMIT ' . $nb
        );
        if (sizeof($result)){
            require_once _PS_MODULE_DIR_ . 'ph_simpleblog/models/SimpleBlogPost.php';

            foreach($result as &$row){
                // formating
                // Это делать не нужно, вывод можно отформатировать в темплейте, лишние операции
                // тут есть проблема, что симплБлогПост может не создаться с ид как строка а не как инт
                $row["id_simpleblog_post"] = (int)$row["id_simpleblog_post"];
                $id_lang = Context::getContext()->language->id; # Get current user language ID from Context
                $post = new SimpleBlogPost($row["id_simpleblog_post"], $id_lang);
                $row['post_link'] = $post->url;
                $row['post_link'] .= '#comment-no-'.$row['id_simpleblog_comment'];
            }
        }
        else {
            return;
        }

        return $result;
    }

}