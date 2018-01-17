<?php

class Question extends ObjectModel
{
    public $id;
    public $id_question;

    /** @var string Name of customer who leaved question */
    public $customer_name;

    /** @var int Product id */
    public $id_product;

    /** @var string product_name */
    public $product_name;

    /** @var int Image id */
    public $id_image;

    /** @var string Customer email */
    public $email;

    /** @var string Question text */
    public $question;

    /** @var string Question answer */
    public $answer;

    /** @var string Question status */
    public $active = false;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'question',
        'primary' => 'id_question',
        'fields' => array(
            'customer_name' => array('type' => self::TYPE_STRING),
            'id_product' => array('type' => self::TYPE_INT),
            'id_image' => array('type' => self::TYPE_INT),
            'product_name' => array('type' => self::TYPE_STRING, 'size' => 128),
            'email' =>         array('type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 128),
            'question' =>      array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'size' => 65535, 'required' => true),
            'answer' =>        array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'size' => 65535, 'required' => false),
            'active' =>        array('type' => self::TYPE_BOOL),
        )
    );

    public static function getQuestions()
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'question` WHERE active = 1 AND answer != ""';
        $result = Db::getInstance()->executeS($sql);

        return $result;
    }
}
