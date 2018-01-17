<?php

class simple_faqListModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();

        $this->context = Context::getContext();

        include_once $this->module->getLocalPath().'models/Question.php';
    }

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        $questions = Question::getQuestions();

        $this->context->smarty->assign(array(
            'questions' => $questions,
            'email' => $this->context->cookie->email ? $this->context->cookie->email : '',
            'errors' => $this->errors,
        ));

        if ($this->errors) {
            $this->context->smarty->assign(array(
                'post_name' => Tools::getValue('customer_name', null),
                'post_email' => Tools::getValue('email', null),
                'post_question' => Tools::getValue('question', null),
            ));
        }

        if (Tools::getIsset('success'))
            $this->context->smarty->assign('confirmation', true);
        $this->setTemplate('list.tpl');
    }

    /**
     * Validate and save new question
     *
     * @return array
     */
    public function postProcess()
    {
        if (Tools::getIsset('submitNewQuestion')) {

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

            if (!$customer_name){
                $this->context->rdirect_errors[] = Tools::displayError('Не указано ваше имя');
                Tools::redirect($url);
            }


            if (!$email || !Validate::isEmail($email)){
                $this->errors[] = "Не указан e-mail";
            }


            if (!$question_text){
                $this->errors[] = "Не указан вопрос";
            }


            if (!$this->errors) {

                $question = new Question();
                $question->customer_name = $customer_name;
                $question->email = $email;
                $question->question = $question_text;
                $question->id_product = $id_product;
                $question->id_image = $id_image;
                $question->product_name = $product_name;
                $question_add_success = $question->add();

                if (!$question_add_success) {
                    $this->errors[] = 'Ошибка при сохранении вопроса';
                } else {

                    $this->confirmations[] = "Вопрос успешно добавлен";
                    $this->context->smarty->assign(array(
                        'confirmation' => true,
                    ));
                    
                    Tools::redirect($url);
                }
            }
        }
    }
    
}
