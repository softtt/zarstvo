<?php
class simple_faqDefaultModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();

        $this->context = Context::getContext();
        include_once $this->module->getLocalPath().'models/Question.php';
    }

    public function initContent()
    {
        parent::initContent();

        if (Tools::isSubmit('action'))
        {
            switch(Tools::getValue('action'))
            {
                case 'add_question':
                    $this->ajaxProcessAddQuestion();
                    break;
            }
        }
    }
    public function ajaxProcessAddQuestion()
    {
        $errors = array();
        $result = false;
        $lang = $this->context->language->id;
        $link = new Link();
        $customer_name = Tools::getValue('customer_name', null);
        $email = Tools::getValue('email', null);
        $question_text = Tools::getValue('question', null);
        $id_product = Tools::getValue('id_product',null);
        $product = new Product($id_product);
        $url = $link->getProductLink($product);

        $product_name = $product->getProductName($id_product,null,$lang);
        $id_image = Tools::getValue('id_image',null);

        if (!$customer_name) {
            $errors[] = 'Не указано ваше имя';
        }

        if (!$email) {
            $errors[] = "Не указан e-mail";
        }

        if (!Validate::isEmail($email)) {
            $errors[] = "Неверный формат e-mail";
        }

        if (!$question_text) {
            $errors[] = "Не указан вопрос";
        }


        if (!count($errors)) {
            $question = new Question();
            $question->customer_name = $customer_name;
            $question->email = $email;
            $question->question = $question_text;
            $question->id_product = $id_product;
            $question->id_image = $id_image;
            $question->product_name = $product_name;
            $question_add_success = $question->add();

            if (!$question_add_success) {
                $errors[] = 'Ошибка при сохранении вопроса';
            } else {
                $success[] = "Вопрос успешно добавлен";
                $this->context->smarty->assign(array(
                    'confirmation' => true,
                ));
                $mailVars = array(
                    '{name}' => $customer_name,
                    '{email}' => $email,
                    '{question_text}' => $question_text,
                    '{product_name}' => $product_name
                );
                
                 Mail::Send(
                    $lang,
                    'new_question_recieved',
                    'Новый вопрос',
                    $mailVars,
                     Configuration::get('FAQ_EMAIL'),
                    null,
                    null,
                    null,
                    null,
                    null, _PS_MAIL_DIR_, false, null
                );
                $result = true;
            }
        }

        die(Tools::jsonEncode(array(
            'result' => $result,
            'errors' => $errors,
            'success' => $success,
            'mail' =>$mail
        )));
    }
}
