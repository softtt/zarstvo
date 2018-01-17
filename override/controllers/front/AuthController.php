<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class AuthController extends AuthControllerCore
{
    /**
     * Initialize auth controller
     * @see FrontController::init()
     */
    public function init()
    {
        FrontController::init();

        // if (!Tools::getIsset('step') && $this->context->customer->isLogged() && !$this->ajax)
        //     Tools::redirect('index.php?controller='.(($this->authRedirection !== false) ? urlencode($this->authRedirection) : 'my-account'));

        if (Tools::getValue('create_account'))
            $this->create_account = true;
    }

    /**
     * Process submit on an account
     */
    protected function processSubmitAccount()
    {
        Hook::exec('actionBeforeSubmitAccount');
        $this->create_account = true;
        if (Tools::isSubmit('submitAccount'))
            $this->context->smarty->assign('email_create', 1);
        // New Guest customer
        if (!Tools::getValue('is_new_customer', 1) && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED'))
            $this->errors[] = Tools::displayError('You cannot create a guest account.');
        if (!Tools::getValue('is_new_customer', 1))
            $_POST['passwd'] = md5(time()._COOKIE_KEY_);
        if ($guest_email = Tools::getValue('guest_email'))
            $_POST['email'] = $guest_email;
        // Checked the user address in case he changed his email address
        $customer = new Customer();
        if (Validate::isEmail($email = Tools::getValue('email')) && !empty($email))
            if (Customer::customerExists($email, false, false))
                $customer = new Customer(Customer::customerExists($email, true, false));
        // Preparing customer
        $lastnameAddress = Tools::getValue('lastname');
        $firstnameAddress = Tools::getValue('firstname');
        $_POST['lastname'] = Tools::getValue('customer_lastname', $lastnameAddress);
        $_POST['firstname'] = Tools::getValue('customer_firstname', $firstnameAddress);
        $addresses_types = array('address');
        if (!Configuration::get('PS_ORDER_PROCESS_TYPE') && Configuration::get('PS_GUEST_CHECKOUT_ENABLED') && Tools::getValue('invoice_address'))
            $addresses_types[] = 'address_invoice';

        $error_phone = false;
        if (Configuration::get('PS_ONE_PHONE_AT_LEAST'))
        {
            if (Tools::isSubmit('submitGuestAccount') || !Tools::getValue('is_new_customer'))
            {
                if (!Tools::getValue('phone'))
                    $error_phone = true;
            }
            elseif (((Configuration::get('PS_REGISTRATION_PROCESS_TYPE') && Configuration::get('PS_ORDER_PROCESS_TYPE'))
                    || (Configuration::get('PS_ORDER_PROCESS_TYPE') && !Tools::getValue('email_create'))
                    || (Configuration::get('PS_REGISTRATION_PROCESS_TYPE') && Tools::getValue('email_create')))
                    && (!Tools::getValue('phone')))
                $error_phone = true;
        }

        if ($error_phone)
            $this->errors['phone'] = Tools::displayError('You must register phone number.');

        $this->errors = array_unique(array_merge($this->errors, $customer->validateController()));

        // Check the requires fields which are settings in the BO
        $this->errors = $this->errors + $customer->validateFieldsRequiredDatabase();

        // Common registration process
        if (!Configuration::get('PS_REGISTRATION_PROCESS_TYPE') && !$this->ajax && !Tools::isSubmit('submitGuestAccount'))
        {
            if (!count($this->errors))
            {
                if (Tools::isSubmit('newsletter'))
                    $this->processCustomerNewsletter($customer);

                $customer->firstname = Tools::ucwords($customer->firstname);
                $customer->birthday = (empty($_POST['years']) ? '' : (int)Tools::getValue('years').'-'.(int)Tools::getValue('months').'-'.(int)Tools::getValue('days'));
                if (!Validate::isBirthDate($customer->birthday))
                    $this->errors[] = Tools::displayError('Invalid date of birth.');

                // New Guest customer
                $customer->active = 1;

                if (!count($this->errors))
                {
                    if (!Customer::customerExists($email, false, false))
                    {
                        $customer->is_guest = (Tools::isSubmit('is_new_customer') ? !Tools::getValue('is_new_customer', 1) : 0);
                        if ($customer->add())
                        {
                            if (!$customer->is_guest)
                                if (!$this->sendConfirmationMail($customer))
                                    $this->errors[] = Tools::displayError('The email cannot be sent.');
                        }
                        else
                            $this->errors[] = Tools::displayError('An error occurred while creating your account.');
                    }
                    else
                    {
                        if(!$customer->transformToCustomer($this->context->language->id, $_POST['passwd'])) {
                            $this->errors[] = Tools::displayError('An error occurred while creating your account.');
                        }
                    }
                }

                if (!count($this->errors))
                {
                    $this->updateContext($customer);

                    $this->context->cart->update();
                    Hook::exec('actionCustomerAccountAdd', array(
                            '_POST' => $_POST,
                            'newCustomer' => $customer
                        ));
                    if ($this->ajax)
                    {
                        $return = array(
                            'hasError' => !empty($this->errors),
                            'errors' => $this->errors,
                            'isSaved' => true,
                            'id_customer' => (int)$this->context->cookie->id_customer,
                            'id_address_delivery' => $this->context->cart->id_address_delivery,
                            'id_address_invoice' => $this->context->cart->id_address_invoice,
                            'token' => Tools::getToken(false)
                        );
                        die(Tools::jsonEncode($return));
                    }

                    $discountCards = DiscountCard::getDiscountCardsByEmail($email);

                    if ($discountCards) {
                        $d = new DiscountCard($discountCards[0]['id_customer_discount_card']);
                        if (!$d->id_customer)
                        {
                            $d->id_customer = $customer->id;
                            $d->accumulated_online_before_offline = $d->virtualAccumulatedSum();
                            if($d->update() && (int)$d->getDiscountPercent() > 0)
                            {
                                if (Validate::isEmail($customer->email))
                                    Mail::Send(
                                        (int)1,
                                        'discount_card_assigned',
                                        Mail::l('Personal discount card is assigned to you account', (int)$order->id_lang),
                                        array(
                                            '{firstname}' => $customer->firstname,
                                            '{discount_code}' => $d->getCardCode(),
                                            '{discount_percent}' => (int)$d->getDiscountPercent(),
                                        ),
                                        $customer->email,
                                        $customer->firstname.' '.$customer->lastname
                                    );
                            }
                        }
                    }

                    if (($back = Tools::getValue('back')) && $back == Tools::secureReferrer($back))
                        Tools::redirect(html_entity_decode($back));
                    Tools::redirect('index.php');
                    // redirection: if cart is not empty : redirection to the cart
                    // if (count($this->context->cart->getProducts(true)) > 0)
                    //     Tools::redirect('index.php?controller=order'.($multi = (int)Tools::getValue('multi-shipping') ? '&multi-shipping='.$multi : ''));
                    // // else : redirection to the account
                    // else
                    //     Tools::redirect('index.php?controller='.(($this->authRedirection !== false) ? urlencode($this->authRedirection) : 'my-account'));
                }
            }
        }
        else // if registration type is in one step, we save the address
        {
            $lastnameAddress = Tools::getValue('customer_lastname');
            $firstnameAddress = Tools::getValue('customer_firstname');
            $post_back = $_POST;
            // Preparing addresses
            foreach ($addresses_types as $addresses_type)
            {
                $$addresses_type = new Address();
                $$addresses_type->id_customer = 1;

                if ($addresses_type == 'address_invoice')
                    foreach ($_POST as $key => &$post)
                        if ($tmp = Tools::getValue($key.'_invoice'))
                            $post = $tmp;

                $this->errors = array_unique(array_merge($this->errors, $$addresses_type->validateController()));
                if ($addresses_type == 'address_invoice')
                    $_POST = $post_back;

                if (!($country = new Country($$addresses_type->id_country)) || !Validate::isLoadedObject($country))
                    $this->errors[] = Tools::displayError('Country cannot be loaded with address->id_country');

                if (!$country->active)
                    $this->errors[] = Tools::displayError('This country is not active.');

                $postcode = $$addresses_type->postcode;
                /* Check zip code format */
                if ($country->zip_code_format && !$country->checkZipCode($postcode))
                    $this->errors['postcode'] = sprintf(Tools::displayError('The Zip/Postal code you\'ve entered is invalid. It must follow this format: %s'), str_replace('C', $country->iso_code, str_replace('N', '0', str_replace('L', 'A', $country->zip_code_format))));
                elseif (empty($postcode) && $country->need_zip_code)
                    $this->errors['postcode'] = Tools::displayError('A Zip / Postal code is required.');
                elseif ($postcode && !Validate::isPostCode($postcode))
                    $this->errors['postcode'] = Tools::displayError('The Zip / Postal code is invalid.');

                // if ($country->need_identification_number && (!Tools::getValue('dni') || !Validate::isDniLite(Tools::getValue('dni'))))
                //     $this->errors[] = Tools::displayError('The identification number is incorrect or has already been used.');
                // elseif (!$country->need_identification_number)
                //     $$addresses_type->dni = null;

                if (Tools::isSubmit('submitAccount') || Tools::isSubmit('submitGuestAccount'))
                    if (!($country = new Country($$addresses_type->id_country, Configuration::get('PS_LANG_DEFAULT'))) || !Validate::isLoadedObject($country))
                        $this->errors[] = Tools::displayError('Country is invalid');
                // $contains_state = isset($country) && is_object($country) ? (int)$country->contains_states: 0;
                // $id_state = isset($$addresses_type) && is_object($$addresses_type) ? (int)$$addresses_type->id_state: 0;
                // if ((Tools::isSubmit('submitAccount') || Tools::isSubmit('submitGuestAccount')) && $contains_state && !$id_state)
                //     $this->errors[] = Tools::displayError('This country requires you to choose a State.');
            }
        }

        if (!@checkdate(Tools::getValue('months'), Tools::getValue('days'), Tools::getValue('years')) && !(Tools::getValue('months') == '' && Tools::getValue('days') == '' && Tools::getValue('years') == ''))
            $this->errors[] = Tools::displayError('Invalid date of birth');

        if (!count($this->errors))
        {
            // if (Customer::customerExists(Tools::getValue('email')))
            //     $this->errors[] = Tools::displayError('An account using this email address has already been registered. Please enter a valid password or request a new one. ', false);
            if (Tools::isSubmit('newsletter'))
                $this->processCustomerNewsletter($customer);

            if (!count($this->errors))
            {
                $customer->active = 1;

                // New Guest customer
                if (Tools::isSubmit('is_new_customer'))
                    $customer->is_guest = !Tools::getValue('is_new_customer', 1);
                else
                    $customer->is_guest = 0;
                if (!Customer::customerExists(Tools::getValue('email'), false, false))
                    if (!$customer->add())
                        $this->errors[] = Tools::displayError('An error occurred while creating your account.');
            }
        }

        if (!count($this->errors))
        {
            foreach ($addresses_types as $addresses_type)
            {
                $$addresses_type->id_customer = (int)$customer->id;
                if ($addresses_type == 'address_invoice')
                    foreach ($_POST as $key => &$post)
                        if ($tmp = Tools::getValue($key.'_invoice'))
                            $post = $tmp;

                $this->errors = array_unique(array_merge($this->errors, $$addresses_type->validateController()));
                if ($addresses_type == 'address_invoice')
                    $_POST = $post_back;
                if (!count($this->errors) && (Configuration::get('PS_REGISTRATION_PROCESS_TYPE') || $this->ajax
                    || Tools::isSubmit('submitGuestAccount')) && !$$addresses_type->add()
                )
                    $this->errors[] = Tools::displayError('An error occurred while creating your address.');
            }

            if (!count($this->errors))
            {
                if (!$customer->is_guest)
                {
                    $this->context->customer = $customer;
                    $customer->cleanGroups();
                    // we add the guest customer in the default customer group
                    $customer->addGroups(array((int)Configuration::get('PS_CUSTOMER_GROUP')));
                    if (!$this->sendConfirmationMail($customer))
                        $this->errors[] = Tools::displayError('The email cannot be sent.');
                }
                else
                {
                    $customer->cleanGroups();
                    // we add the guest customer in the guest customer group
                    $customer->addGroups(array((int)Configuration::get('PS_GUEST_GROUP')));
                }
                $this->updateContext($customer);

                $this->context->cart->id_address_delivery = $address->id;
                $this->context->cart->id_address_invoice = $address->id;
                if (isset($address_invoice) && Validate::isLoadedObject($address_invoice))
                    $this->context->cart->id_address_invoice = (int)$address_invoice->id;

                if ($this->ajax && Configuration::get('PS_ORDER_PROCESS_TYPE'))
                {
                    $delivery_option = array((int)$this->context->cart->id_address_delivery => (int)$this->context->cart->id_carrier.',');
                    $this->context->cart->setDeliveryOption($delivery_option);
                }

                // If a logged guest logs in as a customer, the cart secure key was already set and needs to be updated
                $this->context->cart->update();

                // Avoid articles without delivery address on the cart
                $this->context->cart->autosetProductAddress();

                Hook::exec('actionCustomerAccountAdd', array(
                        '_POST' => $_POST,
                        'newCustomer' => $customer
                    ));
                if ($this->ajax)
                {
                    $return = array(
                        'hasError' => !empty($this->errors),
                        'errors' => $this->errors,
                        'isSaved' => true,
                        'id_customer' => (int)$this->context->cookie->id_customer,
                        'id_address_delivery' => $this->context->cart->id_address_delivery,
                        'id_address_invoice' => $this->context->cart->id_address_invoice,
                        'token' => Tools::getToken(false)
                    );
                    die(Tools::jsonEncode($return));
                }
                // if registration type is in two steps, we redirect to register address
                if (!Configuration::get('PS_REGISTRATION_PROCESS_TYPE') && !$this->ajax && !Tools::isSubmit('submitGuestAccount'))
                    Tools::redirect('index.php?controller=address');

                if (($back = Tools::getValue('back')) && $back == Tools::secureReferrer($back))
                    Tools::redirect(html_entity_decode($back));

                // redirection: if cart is not empty : redirection to the cart
                if (count($this->context->cart->getProducts(true)) > 0)
                    Tools::redirect('index.php?controller=order'.($multi = (int)Tools::getValue('multi-shipping') ? '&multi-shipping='.$multi : ''));
                // else : redirection to the account
                else
                    Tools::redirect('index.php?controller='.(($this->authRedirection !== false) ? urlencode($this->authRedirection) : 'my-account'));
            }
        }

        if (count($this->errors))
        {
            //for retro compatibility to display guest account creation form on authentication page
            if (Tools::getValue('submitGuestAccount'))
                $_GET['display_guest_checkout'] = 1;

            if (!Tools::getValue('is_new_customer'))
                unset($_POST['passwd']);
            if ($this->ajax)
            {
                $return = array(
                    'hasError' => !empty($this->errors),
                    'errors' => $this->errors,
                    'isSaved' => false,
                    'id_customer' => 0
                );
                die(Tools::jsonEncode($return));
            }
            $this->context->smarty->assign('account_error', $this->errors);
        }
    }

    /**
     * Process login
     */
    protected function processSubmitLogin()
    {
        Hook::exec('actionBeforeAuthentication');
        $passwd = trim(Tools::getValue('passwd'));
        $email = trim(Tools::getValue('email'));
        if (empty($email))
            $this->errors[] = Tools::displayError('An email address required.');
        elseif (!Validate::isEmail($email))
            $this->errors[] = Tools::displayError('Invalid email address.');
        elseif (empty($passwd))
            $this->errors[] = Tools::displayError('Password is required.');
        elseif (!Validate::isPasswd($passwd))
            $this->errors[] = Tools::displayError('Invalid password.');
        else
        {
            $customer = new Customer();
            $authentication = $customer->getByEmail(trim($email), trim($passwd));
            if (isset($authentication->active) && !$authentication->active)
                $this->errors[] = Tools::displayError('Your account isn\'t available at this time, please contact us');
            elseif (!$authentication || !$customer->id)
                $this->errors[] = Tools::displayError('Authentication failed.');
            else
            {
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

                if (Configuration::get('PS_CART_FOLLOWING') && (empty($this->context->cookie->id_cart) || Cart::getNbProducts($this->context->cookie->id_cart) == 0) && $id_cart = (int)Cart::lastNoneOrderedCart($this->context->customer->id))
                    $this->context->cart = new Cart($id_cart);
                else
                {
                    $id_carrier = (int)$this->context->cart->id_carrier;
                    $this->context->cart->id_carrier = 0;
                    $this->context->cart->setDeliveryOption(null);
                    $this->context->cart->id_address_delivery = (int)Address::getFirstCustomerAddressId((int)($customer->id), false);
                    $this->context->cart->id_address_invoice = (int)Address::getFirstCustomerAddressId((int)($customer->id), false);
                }
                $this->context->cart->id_customer = (int)$customer->id;
                $this->context->cart->secure_key = $customer->secure_key;

                if ($this->ajax && isset($id_carrier) && $id_carrier && Configuration::get('PS_ORDER_PROCESS_TYPE'))
                {
                    $delivery_option = array($this->context->cart->id_address_delivery => $id_carrier.',');
                    $this->context->cart->setDeliveryOption($delivery_option);
                }

                $this->context->cart->save();
                $this->context->cookie->id_cart = (int)$this->context->cart->id;
                $this->context->cookie->write();
                $this->context->cart->autosetProductAddress();

                Hook::exec('actionAuthentication');

                // Login information have changed, so we check if the cart rules still apply
                CartRule::autoRemoveFromCart($this->context);
                CartRule::autoAddToCart($this->context);

                // Check if there is discount card for given email
                $discountCards = DiscountCard::getDiscountCardsByEmail($customer->email);
                if ($discountCards) {
                    $d = new DiscountCard($discountCards[0]['id_customer_discount_card']);
                    if (!$d->id_customer)
                    {
                        $d->id_customer = $customer->id;
                        $d->accumulated_online_before_offline = $d->virtualAccumulatedSum();
                        if ($d->update() && (int)$d->getDiscountPercent() > 0)
                        {
                            if (Validate::isEmail($customer->email))
                                Mail::Send(
                                    (int)1,
                                    'discount_card_assigned',
                                    Mail::l('Personal discount card is assigned to you account', (int)$order->id_lang),
                                    array(
                                        '{firstname}' => $customer->firstname,
                                        '{discount_code}' => $d->getCardCode(),
                                        '{discount_percent}' => (int)$d->getDiscountPercent(),
                                    ),
                                    $customer->email,
                                    $customer->firstname.' '.$customer->lastname
                                );
                        }

                    }
                }

                if (!$this->ajax)
                {
                    if ($back == Tools::secureReferrer(Tools::getValue('back')))
                        Tools::redirect(html_entity_decode($back));

                    $back = $back ? $back : 'index';
                    Tools::redirect('index.php?controller='.(($this->authRedirection !== false) ? urlencode($this->authRedirection) : $back));
                }
            }
        }
        if ($this->ajax)
        {
            $return = array(
                'hasError' => !empty($this->errors),
                'errors' => $this->errors,
                'token' => Tools::getToken(false)
            );
            die(Tools::jsonEncode($return));
        }
        else
            $this->context->smarty->assign('authentification_error', $this->errors);

    }
}
