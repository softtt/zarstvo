<?php

require_once _PS_MODULE_DIR_.'simple_faq/models/Question.php';

class AdminSimpleFaqController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'question';
        $this->className = 'Question';
        $this->lang = false;
        $this->list_no_link = false;

        $this->list_simple_header = false;
        $this->show_toolbar = false;

        $this->bootstrap = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Удалить выбраные'),
                'confirm' => $this->l('Удалить выбраные?'),
                'icon' => 'icon-trash'
            )
        );

        // Single record form fields setting
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Вопрос')
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Имя клиента:'),
                    'name' => 'customer_name',
                    'required' => true,
                    'col' => 5,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Название товара'),
                    'name' => 'product_name',
                    'required' => true,
                    'col' => 5,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Email'),
                    'name' => 'email',
                    'col' => 5,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Вопрос'),
                    'name' => 'question',
                    'col' => 5,
                    'required' => true,
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Ответ'),
                    'name' => 'answer',
                    'rows' => 8,
                    'col' => 5,
                    'required' => false,
                    'autoload_rte' => true,
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Показывать:'),
                    'name' => 'active',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Включено')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Выключено')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Сохранить и остаться'),
                'class' => 'btn btn-default',
                'stay' => true,
            )
        );

        // Questions list columns settings
        $this->fields_list = array(
            'id_question' => array(
                'title' => $this->l('ID'),
                'align' => 'left',
                'width' => 30
            ),
            'id_product' => array(
                'title' => $this->l('ID Товара'),
                'width' => 30
            ),
            'product_name' => array(
                'title' => $this->l('Название тоара'),
                'width' => 30
            ),
            'customer_name' => array(
                'title' => $this->l('Клиент'),
                'width' => 'auto'
            ),
            'email' => array(
                'title' => $this->l('Email'),
                'width' => 'auto'
            ),
            'question' => array(
                'type' => 'textarea',
                'title' => $this->l('Вопрос'),
                'width' => 'auto',
                'search' => false,
            ),
            'answer' => array(
                'type' => 'textarea',
                'title' => $this->l('Ответ'),
                'width' => 'auto',
                'search' => false,
                'maxlength' => 100,
                'callback' => 'getDescriptionClean'
            ),
            'active' => array(
                'title' => $this->l('Показывать вопрос'),
                'width' => 25,
                'active' => 'status',
                'align' => 'center',
                'type' => 'bool',
                'orderby' => true
            ),
        );

        parent::__construct();
    }

    public static function getDescriptionClean($description)
    {
        return strip_tags(stripslashes($description));
    }
}
