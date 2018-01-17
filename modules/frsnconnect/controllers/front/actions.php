<?php
/*
* Social Network connect modules
* frsnconnect 0.15 by froZZen
*/

class myAddress extends Address {

    public function __construct() {
        parent::__construct();
        $this->fieldsRequired = array();
   }
   
}


class FrSnConnectActionsModuleFrontController extends ModuleFrontController {

   public $id_sn_service;
   public $display_column_left = false;
   protected $create_account = false;
   protected $js_return = array();




   public function init() {
        
	parent::init();

	$this->id_sn_service = (int)Tools::getValue('id_sn_service');
                
        require_once(_PS_MODULE_DIR_.'frsnconnect/SNTools.php'); 
        if (!count(Context::getContext()->fr_sn_servlist)) 
            Context::getContext()->fr_sn_servlist = SNTools::GetSNServiceList(); 
                
    }

    public function postProcess() {
        
        if (!Tools::isSubmit('process') && Tools::isSubmit('state')) {
            $state = base64_decode(strtr(Tools::getValue('state'), '-_,', '+/='));
            $state = explode('&', $state);
    
            for ($i=0; $i<count($state); $i++) {
                $st = explode ('=', $state[$i]);
                $_REQUEST[trim($st[0])] = trim($st[1]);
                $_GET[trim($st[0])] = trim($st[1]);
            }            
        }
             
        if (Tools::getValue('process') == 'remove') {
            $this->processRemove();
            exit;
        }       
	else if (Tools::getValue('process') == 'accAdd')
            $this->processAccAdd();
	else if (Tools::getValue('process') == 'accAuth')
            $this->processAccAuth();
	else if (Tools::getValue('process') == 'create')
            $this->processCreateAccount();
                
    }

    public function processCreateAccount() {
            
        if (sizeof($_REQUEST)) {
            try {
                $back = Tools::getValue('back'); 

		if (!isset($_POST['email']) || empty($_POST['email'])) 
                    $_POST['email'] = Configuration::get('FRSNCONN_EMPTYEMAIL');
     
		// Checked the user address in case he changed his email address
		if (Validate::isEmail($email = Tools::getValue('email')) && !empty($email) && ($email != Configuration::get('FRSNCONN_EMPTYEMAIL')))
                    if ($id = Customer::customerExists($email, true)) {
                        Hook::exec('actionBeforeAuthentication');
                        $customer = new Customer($id);
                        $customer->updateCustomerSnAccount(Tools::getValue('sn_serv'), Tools::getValue('sn_serv_uid'));
                        if ($this->CustomerLogin($customer, $back))
                            return;
                    }        

		// Preparing customer
		$customer = new Customer();
                
		$_POST['lastname'] = Tools::getValue('customer_lastname');
		$_POST['firstname'] = Tools::getValue('customer_firstname');
		
		$this->errors = array_unique(array_merge($this->errors, $customer->validateController()));

		// Check the requires fields which are settings in the BO
		$this->errors = array_merge($this->errors, $customer->validateFieldsRequiredDatabase());

		if (Configuration::get('PS_ONE_PHONE_AT_LEAST') && !Tools::getValue('phone') && !Tools::getValue('phone_mobile') &&
                        (Configuration::get('PS_REGISTRATION_PROCESS_TYPE') || Configuration::get('PS_GUEST_CHECKOUT_ENABLED')) && $this->ajax)
                    $this->errors[] = Tools::displayError('You must register at least one phone number');
                
		if (!Configuration::get('PS_REGISTRATION_PROCESS_TYPE')  && !$this->ajax  && !Tools::isSubmit('submitGuestAccount'))
		{
                    if (!count($this->errors)) {
                        $customer->birthday = (empty($_POST['years']) ? '' : (int)$_POST['years'].'-'.(int)$_POST['months'].'-'.(int)$_POST['days']);
			$customer->active = 1;
			// New Guest customer
			if (Tools::isSubmit('is_new_customer'))
                            $customer->is_guest = !Tools::getValue('is_new_customer', 1);
			else
                            $customer->is_guest = 0;
			
                        if (!count($this->errors))
                            if (!$customer->add())
                                $this->errors[] = Tools::displayError('An error occurred while creating your account.');
                            else {
                                $customer->updateCustomerSnAccount(Tools::getValue('sn_serv'), Tools::getValue('sn_serv_uid'));
				//$this->errors[] = print_r($customer, true);
                                if (!$customer->is_guest)
                                    if (!$this->sendConfirmationMail($customer))
                                        $this->errors[] = Tools::displayError('Cannot send e-mail');

                                $this->updateContext($customer);
                                                
                                $this->context->cart->update();
				Hook::exec('actionCustomerAccountAdd', array(
                                        '_POST' => $_POST,
					'newCustomer' => $customer
					));
                                              
                                
                                $this->js_return = array(
                                        'hasError' => !empty($this->errors),
                                        'errors' => $this->errors,
					'isSaved' => true,
					'id_customer' => (int)$this->context->cookie->id_customer,
					'id_address_delivery' => $this->context->cart->id_address_delivery,
					'id_address_invoice' => $this->context->cart->id_address_invoice,
					'token' => Tools::getToken(false)
					);
                                if ($this->ajax) {
                                    die(Tools::jsonEncode($this->js_return));
				}

                                // redirection: if cart is not empty : redirection to the cart
				if (count($this->context->cart->getProducts(true)) > 0)
                                    Tools::redirect('index.php?controller=order&multi-shipping='.(int)Tools::getValue('multi-shipping'));
				// else : redirection to the account
				else
                                    Tools::redirect('index.php?controller=my-account');
                                                 
                            }
                    }
                }
		else // if registration type is in one step, we save the address
		{
                    $lastnameAddress = $_POST['lastname'];
                    $firstnameAddress = $_POST['firstname'];
                    // Preparing address
                    $address = new myAddress();

                    $_POST['lastname'] = $lastnameAddress;
                    $_POST['firstname'] = $firstnameAddress;
                    $address->id_customer = 1;
                    $this->errors = array_unique(array_merge($this->errors, $address->validateController()));
		}

                if (!count($this->errors)) {
                    
                    $customer->birthday = (empty($_POST['years']) ? '' : (int)$_POST['years'].'-'.(int)$_POST['months'].'-'.(int)$_POST['days']);
                    if (!count($this->errors)) {
                        $customer->active = 1;
			// New Guest customer
			if (Tools::isSubmit('is_new_customer'))
                            $customer->is_guest = !Tools::getValue('is_new_customer', 1);
			else
                            $customer->is_guest = 0;
			if (!$customer->add())
                            $this->errors[] = Tools::displayError('An error occurred while creating your account.');
			else {
                            $customer->updateCustomerSnAccount(Tools::getValue('sn_serv'), Tools::getValue('sn_serv_uid'));

                            $address->id_customer = (int)$customer->id;
                            $this->errors = array_unique(array_merge($this->errors, $address->validateController()));
                            
                            if (!count($this->errors) && (isset($_POST['alias']) || Configuration::get('PS_REGISTRATION_PROCESS_TYPE') || $this->ajax || Tools::isSubmit('submitGuestAccount')) && !$address->add())
                                $this->errors[] = Tools::displayError('An error occurred while creating your address.');

                            if (!count($this->errors)) {
                                if (!$customer->is_guest) {
                                    $this->context->customer = $customer;
                                    $customer->cleanGroups();
                                    // we add the guest customer in the default customer group
                                    $customer->addGroups(array((int)Configuration::get('PS_CUSTOMER_GROUP')));
                                    if (!$this->sendConfirmationMail($customer) && !$this->ajax)
                                        $this->errors[] = Tools::displayError('Cannot send e-mail');
                                }
				else {
                                    $customer->cleanGroups();
                                    // we add the guest customer in the guest customer group
                                    $customer->addGroups(array((int)Configuration::get('PS_GUEST_GROUP')));
				}
				
                                $this->updateContext($customer);
				$this->context->cart->id_address_delivery = Address::getFirstCustomerAddressId((int)$customer->id);
				$this->context->cart->id_address_invoice = Address::getFirstCustomerAddressId((int)$customer->id);

				// If a logged guest logs in as a customer, the cart secure key was already set and needs to be updated
				$this->context->cart->update();

				// Avoid articles without delivery address on the cart
				$this->context->cart->autosetProductAddress();

				Hook::exec('actionCustomerAccountAdd', array(
                                        '_POST' => $_POST,
					'newCustomer' => $customer
					));
                                                        
                                $this->js_return = array(
                                        'hasError' => !empty($this->errors),
					'errors' => $this->errors,
					'isSaved' => true,
					'id_customer' => (int)$this->context->cookie->id_customer,
					'id_address_delivery' => $this->context->cart->id_address_delivery,
					'id_address_invoice' => $this->context->cart->id_address_invoice,
					'token' => Tools::getToken(false)
					);
                                if ($this->ajax) {
                                    die(Tools::jsonEncode($this->js_return));
                                }
                                               
                                if ($back)
                                    Tools::redirect($back);
                                Tools::redirect('index.php?controller=my-account');
                                // redirection: if cart is not empty : redirection to the cart
                                if (count($this->context->cart->getProducts(true)) > 0)
                                    Tools::redirect('index.php?controller=order&multi-shipping='.(int)Tools::getValue('multi-shipping'));
                                // else : redirection to the account
                                else
                                    Tools::redirect('index.php?controller=my-account');
                                              
                            }
			}
                    }
                } 
                
               $this->js_return = array(
                        'hasError' => !empty($this->errors),
			'errors' => $this->errors,
			);
                if ($this->ajax && count($this->errors)) {
                    die(Tools::jsonEncode($this->js_return));
		}

            } catch (Exception $exc) {
                $this->errors[] =  $exc->getMessage();
            }

        } 
    }        

    public function processAccAuth() {
 
        if (Tools::isSubmit('state')) {
            $_REQUEST[$_REQUEST['state']] = 1;
            $_GET[$_REQUEST['state']] = 1;
        }

        if (sizeof($_REQUEST)) {
            try {
                
                $js = Tools::getValue('js');
                $back = Tools::getValue('back');
                
                $sn_servl = Context::getContext()->fr_sn_servlist;
            
                if (isset($sn_servl)) {
                    $sn_id = 0;
                    foreach ($sn_servl AS $key=>$sn_serv) {
                        if (array_key_exists('snLogin_'.$key, $_REQUEST) ) { 
                            $sn_id = $sn_serv['id_sn_service'];
                            break;
                        }
                    }
                }                   
            
                if (isset($sn_id) AND $sn_id > 0) {
               
                
                    require_once(_PS_MODULE_DIR_.'frsnconnect/SNTools.php'); 

                    $service = SNTools::GetSNServiceID($sn_id);
                    if (isset($service) AND $service->authenticate()) {
                    
                        $sn_user_id = $service->id;
                        //$sn_user_id = '000001';
                    
                        Hook::exec('actionBeforeAuthentication');
                        // find customer
                        $customer = new Customer();
                        $authentication = false;
                        
                        if (isset($service->email) AND (!empty($service->email)) AND (Validate::isEmail($service->email))) {
                        //$email = '';
                        //if (!empty($email)) {
                            $authentication = $customer->getByEmail($service->email);
                            //$authentication = $customer->getByEmail($email);
                            if ($authentication OR $customer->id) 
                                $customer->updateCustomerSnAccount($sn_id, $sn_user_id);
                        }
                    
                        if (!$authentication)
                            $authentication = $customer->getBySNId($sn_id, $sn_user_id);

                        if ($authentication OR $customer->id) {
                            if ($this->CustomerLogin($customer, $back))
                                return;
                        }
                        else {
                        
                            // registration new user 
                        
                            $_POST['sn_serv'] = $sn_id;
                            $_POST['sn_serv_uid']= $sn_user_id;
                        
                            $_POST['firstname'] = (isset($service->firstname)) ? $service->firstname : $service->name;
                            $_POST['lastname'] = (isset($service->lastname)) ? $service->lastname : $service->name;
                            $_POST['id_gender'] = $service->id_gender;
                            //$_POST['firstname'] = 'Firstname';
                            //$_POST['lastname'] = 'Lastname';
                            //$_POST['id_gender'] = '1';
                            if (!isset($service->email) OR (empty($service->email))) { 
                            //if (!isset($email) OR (empty($email))) { 
                                $service->email = Configuration::get('FRSNCONN_EMPTYEMAIL');
                                $email = Configuration::get('FRSNCONN_EMPTYEMAIL');
                                self::$smarty->assign('fr_email_warning', 1);
                            }
                            $_POST['email'] = $service->email;
                            //$_POST['email'] = $email;
                            $_POST['customer_lastname'] = $_POST['lastname'];
                            $_POST['customer_firstname'] = $_POST['firstname'];
//                            $_POST['passwd'] = Tools::encrypt(Tools::passwdGen(5));
                            $_POST['passwd'] = Tools::passwdGen(5);

                            if ($service->birthday)
                                $birthday = explode('-', $service->birthday);
                            else
                                $birthday = array('', '', '');
                            $_POST['years'] = $birthday[0];
                            $_POST['months'] = $birthday[1];
                            $_POST['days'] = $birthday[2];
                        
                            $PS_REGISTRATION_PROCESS_TYPE = (int)Configuration::get('PS_REGISTRATION_PROCESS_TYPE') || $this->ajax || $js;
                            $onr_phone_at_least = (int)Configuration::get('PS_ONE_PHONE_AT_LEAST');
                        
                            if ($PS_REGISTRATION_PROCESS_TYPE) {
                                $_POST['id_country'] = (isset($service->id_country)) ? (int)$service->id_country : (int)(Configuration::get('PS_COUNTRY_DEFAULT'));
                                //$_POST['id_country'] = (int)(Configuration::get('PS_COUNTRY_DEFAULT'));
                                //$_POST['city'] = $service->city;
                                $_POST['city'] = (isset($service->city)) ? $service->city : ' ';
                                //$_POST['city'] = 'Spb';
                                if ($onr_phone_at_least) {
                                    $_POST['phone_mobile'] = (isset($service->phone_mobile)) ? $service->phone_mobile : '+7';
                                    $_POST['phone'] = (isset($service->phone)) ? $service->phone : '';
                                    //$_POST['phone_mobile'] = '';
                                    //$_POST['phone'] = '';
                                }
                                $_POST['alias'] = 'My address';
                                $_POST['address1'] = ' ';
                            }
                            else 
                                //if (true) 
                                if (isset($service->id_country)||isset($service->city)||isset($service->phone_mobile)||isset($service->phone)) 
                                {
                                    $_POST['alias'] = 'My address';
                                    $_POST['id_country'] = (isset($service->id_country)) ? (int)$service->id_country : (int)(Configuration::get('PS_COUNTRY_DEFAULT'));
                                    //$_POST['id_country'] = (int)(Configuration::get('PS_COUNTRY_DEFAULT'));
                                    $_POST['city'] = $service->city;
                                    //$_POST['city'] = 'Spb';
                                    $_POST['phone_mobile'] = (isset($service->phone_mobile)) ? $service->phone_mobile : '';
                                    $_POST['phone'] = (isset($service->phone)) ? $service->phone : '';
                                    //$_POST['phone_mobile'] = '';
                                    //$_POST['phone'] = '';
                                    $_POST['address1'] = ' ';
                                }
                        
                            $_POST['email_create'] = 1;
                            $this->create_account = true;
                            self::$smarty->assign('email_create', 1);
                            $_POST['is_new_customer'] = 1;
                            if ($back)
                                self::$smarty->assign('back', $back);
                            if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES'))
				$countries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
                            else
				$countries = Country::getCountries($this->context->language->id, true);
                            $this->context->smarty->assign(array(
                                'sl_country' => (int)Tools::getValue('id_country', Configuration::get('PS_COUNTRY_DEFAULT')),
                                'countries' => $countries,
                                'genders' => Gender::getGenders(),
				'PS_GUEST_CHECKOUT_ENABLED' => Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
				'PS_REGISTRATION_PROCESS_TYPE' => $PS_REGISTRATION_PROCESS_TYPE,
 				'onr_phone_at_least' => $onr_phone_at_least,
                                'inOrderOpc' => ($this->ajax || $js),
                                )); 
                            $this->assignDate();
                        
                            $tpl_form_path = $this->getTemplatePath().'frsnconnect-createaccount-form.tpl';
                            $this->context->smarty->assign('tpl_form_path', $tpl_form_path);
                        
                        }

                        if($service->errors) 
                            $this->errors = $service->errors;
                        //else
                            //Tools::redirect($url);
                    
                        //$this->errors[] = Tools::displayError($sn_id);
                        //$this->errors[] = Tools::displayError('back = '.$back);
                    }
                    else {
                        if(isset($service) AND ($service->errors))
                            $this->errors = $service->errors;
                    
                        $this->errors[] = Tools::displayError('Invalid Social Network connect');
                    }
                }
                
            } catch (Exception $exc) {
                $this->errors[] =  $exc->getMessage();
            }
        }
            
    }
 
    public function processAccAdd() {
 
        if (Tools::isSubmit('state')) {
            $_REQUEST[$_REQUEST['state']] = 1;
            $_GET[$_REQUEST['state']] = 1;
        }
            
        if (sizeof($_REQUEST)) {
            try {

                $customer = Context::getContext()->customer;                   
                if (!count($customer->sn_service))
                    $customer->getCustomerSnService ();   

                $sn_servl = Context::getContext()->fr_sn_servlist;
                if (isset($sn_servl)) {
                    $sn_id = 0;
                    foreach ($sn_servl AS $key=>$sn_serv) {
                        if (array_key_exists('snLogin_'.$key, $_REQUEST) ) { 
                            $sn_id = $sn_serv['id_sn_service'];
                            break;
                        }
                    }
                }                   
            
                if (isset($sn_id) AND $sn_id > 0) {
                
                    $link = Context::getContext()->link;    
                    $url = $link->getModuleLink('frsnconnect', 'snaccount');
                
                    require_once(_PS_MODULE_DIR_.'frsnconnect/SNTools.php'); 

                    $service = SNTools::GetSNServiceID($sn_id);
                
                    if (isset($service) AND $service->authenticate()) {
                        $sn_user_id = $service->id;
                        //$sn_user_id = '00000';
                        $customer->addCustomerSnAccount($sn_id, $sn_user_id);

                        if($service->errors) 
                            $this->errors = $service->errors;
                        else
                            Tools::redirect($url);
                    }
                    else {
                        if(isset($service) AND ($service->errors)) 
                            $this->errors = $service->errors;
                    
                        $this->errors[] = Tools::displayError('Invalid Social Network connect');
                    }
                }
                
            } catch (Exception $exc) {
                $this->errors[] =  $exc->getMessage();
            }
        }

    }

    public function processRemove() {
        
        $customer = Context::getContext()->customer;                   
        $customer->deleteCustomerSnAccount($this->id_sn_service);

        $serv_list = array();

        if (!count($customer->sn_service))
            $customer->getCustomerSnService ();   

        $fr_sn_list = Context::getContext()->fr_sn_servlist;
        if (count($customer->sn_service)) {
            foreach ($customer->sn_service as $key=>$value)
                if (strlen($value) == 0)
                    $serv_list[$key] = $fr_sn_list[$key];
        }
	
        $this->context->smarty->assign('not_services', $serv_list);
        
        $html = $this->module->display($this->module->getLocalPath().'frsnconnect.php', 'frsnconnect_form.tpl');

        $return = array(
            'hasError' => false,
            'form' => $html
            );
        die(Tools::jsonEncode($return));
                                                                        
    }
        
    protected function sendConfirmationMail(Customer $customer) {
        
	return Mail::Send(
            $this->context->language->id,
            'account',
            Mail::l('Welcome!'),
            array(
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
		'{email}' => $customer->email,
		'{passwd}' => Tools::getValue('passwd')),
		$customer->email,
		$customer->firstname.' '.$customer->lastname
            );
        
    }

    protected function updateContext($customer) {
        
        $this->context->customer = $customer;
        $this->context->smarty->assign('confirmation', 1);
        $this->context->cookie->id_customer = (int)$customer->id;
        $this->context->cookie->customer_lastname = $customer->lastname;
        $this->context->cookie->customer_firstname = $customer->firstname;
        $this->context->cookie->passwd = $customer->passwd;
        $this->context->cookie->logged = 1;
        // if register process is in two steps, we display a message to confirm account creation
        if (!Configuration::get('PS_REGISTRATION_PROCESS_TYPE'))
            $this->context->cookie->account_created = 1;
        $customer->logged = 1;
        $this->context->cookie->email = $customer->email;
        $this->context->cookie->is_guest = !Tools::getValue('is_new_customer', 1);
        // Update cart address
        $this->context->cart->secure_key = $customer->secure_key;

    }

    protected function CustomerLogin($customer, $redirect) {
            
        $this->context->cookie->id_compare = isset($this->context->cookie->id_compare) ? $this->context->cookie->id_compare: CompareProduct::getIdCompareByIdCustomer($customer->id);
        $this->context->cookie->id_customer = (int)($customer->id);
        $this->context->cookie->customer_lastname = $customer->lastname;
        $this->context->cookie->customer_firstname = $customer->firstname;
        $this->context->cookie->logged = 1;
        $customer->logged = 1;
        $this->context->cookie->is_guest = $customer->isGuest();
        $this->context->cookie->passwd = $customer->passwd;
        $this->context->cookie->email = $customer->email;

        // Add customer to the context
        $this->context->customer = $customer;

        if (Configuration::get('PS_CART_FOLLOWING') && (empty($this->context->cookie->id_cart) || Cart::getNbProducts($this->context->cookie->id_cart) == 0))
            $this->context->cookie->id_cart = (int)Cart::lastNoneOrderedCart($this->context->customer->id);

        // Update cart address
        $this->context->cart->id = $this->context->cookie->id_cart;
        $this->context->cart->setDeliveryOption(null);
        $this->context->cart->id_address_delivery = Address::getFirstCustomerAddressId((int)($customer->id));

        $this->context->cart->id_address_invoice = Address::getFirstCustomerAddressId((int)($customer->id));
        $this->context->cart->secure_key = $customer->secure_key;
        $this->context->cart->update();
        $this->context->cart->autosetProductAddress();

        Hook::exec('actionAuthentication');

        // Login information have changed, so we check if the cart rules still apply
        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);

        $this->js_return = array(
                'hasError' => !empty($this->errors),
                'errors' => $this->errors,
                'token' => Tools::getToken(false),
                'isLogin' => true,
                );
        
        if (Tools::getValue('js')) 
            return true;
        
        if (!$this->ajax) {
            if ($redirect)
                Tools::redirect(html_entity_decode($redirect));
            Tools::redirect('index.php?controller=my-account');
        }
        else {
            die(Tools::jsonEncode($this->js_return));
        }
        
    }
        
    protected function assignDate() {
        
	// Generate years, months and days
	if (isset($_POST['years']) && is_numeric($_POST['years']))
            $selectedYears = (int)($_POST['years']);
	$years = Tools::dateYears();
	if (isset($_POST['months']) && is_numeric($_POST['months']))
            $selectedMonths = (int)($_POST['months']);
	$months = Tools::dateMonths();

	if (isset($_POST['days']) && is_numeric($_POST['days']))
            $selectedDays = (int)($_POST['days']);
	$days = Tools::dateDays();

	$this->context->smarty->assign(array(
            'years' => $years,
            'sl_year' => (isset($selectedYears) ? $selectedYears : 0),
            'months' => $months,
            'sl_month' => (isset($selectedMonths) ? $selectedMonths : 0),
            'days' => $days,
            'sl_day' => (isset($selectedDays) ? $selectedDays : 0)
            ));
        
    }
        
    public function initContent() {
        
        if ($this->create_account) {
            $this->js_return = array(
                    'hasError' => !empty($this->errors),
                    'errors' => $this->errors,
                    'token' => Tools::getToken(false),
                    'isLogin' => false,
                    'page' => $this->context->smarty->fetch($this->getTemplatePath().'frsnconnect-createaccount-form.tpl'),
                    );
            if ($this->ajax) {
               die(Tools::jsonEncode($this->js_return));
            }                        
        
            if (Tools::getValue('js')) {
                $this->context->smarty->assign('json', Tools::jsonEncode($this->js_return));
                $this->display_column_right = false;
                $this->display_header = false;
                $this->display_footer = false;
            
                $this->setTemplate('frsnconnect-popup.tpl');
                return;
            }
            else {    
                parent::initContent();
                $this->setTemplate('frsnconnect-create_account.tpl');
            }    
        }    
        else {
            if (!count($this->js_return))
                $this->js_return = array(
                    'hasError' => !empty($this->errors),
                    'errors' => $this->errors,
                );

            if (Tools::getValue('js')) {
                $this->context->smarty->assign('json', Tools::jsonEncode($this->js_return));
                $this->display_column_right = false;
                $this->display_header = false;
                $this->display_footer = false;
            
                $this->setTemplate('frsnconnect-popup.tpl');
                return;
            }
            else {
                parent::initContent();
                $this->setTemplate('frsnconnect-errors.tpl');
            }     
        }
        
    }
        
    public function setMedia() {
        
	parent::setMedia();
	$this->addCSS(_THEME_CSS_DIR_.'authentication.css');
    
    }
       
}
