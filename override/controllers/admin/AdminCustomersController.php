<?php

class AdminCustomersController extends AdminCustomersControllerCore
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->required_database = true;
        $this->required_fields = array('newsletter','optin');
        $this->table = 'customer';
        $this->className = 'Customer';
        $this->lang = false;
        $this->deleted = true;
        $this->explicitSelect = true;

        $this->allow_export = true;

        $this->addRowAction('edit');
        $this->addRowAction('view');
        $this->addRowAction('delete');
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );

        $this->context = Context::getContext();

        $this->default_form_language = $this->context->language->id;

        $titles_array = array();
        $genders = Gender::getGenders($this->context->language->id);
        foreach ($genders as $gender)
            $titles_array[$gender->id_gender] = $gender->name;

        $this->_select = '
        a.date_add, gl.name as title, (
            SELECT SUM(total_paid_real / conversion_rate)
            FROM '._DB_PREFIX_.'orders o
            WHERE o.id_customer = a.id_customer
            '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
            AND o.valid = 1
        ) as total_spent, (
            SELECT c.date_add FROM '._DB_PREFIX_.'guest g
            LEFT JOIN '._DB_PREFIX_.'connections c ON c.id_guest = g.id_guest
            WHERE g.id_customer = a.id_customer
            ORDER BY c.date_add DESC
            LIMIT 1
        ) as connect';
        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'gender_lang gl ON (a.id_gender = gl.id_gender AND gl.id_lang = '.(int)$this->context->language->id.')';
        $this->fields_list = array(
            'id_customer' => array(
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'lastname' => array(
                'title' => $this->l('Last name')
            ),
            'firstname' => array(
                'title' => $this->l('First name')
            ),
            'email' => array(
                'title' => $this->l('Email address')
            ),
        );

        if (Configuration::get('PS_B2B_ENABLE'))
        {
            $this->fields_list = array_merge($this->fields_list, array(
                'company' => array(
                    'title' => $this->l('Company')
                ),
            ));
        }

        $this->fields_list = array_merge($this->fields_list, array(
            'total_spent' => array(
                'title' => $this->l('Sales'),
                'type' => 'price',
                'search' => false,
                'havingFilter' => true,
                'align' => 'text-right',
                'badge_success' => true
            ),
            'active' => array(
                'title' => $this->l('Enabled'),
                'align' => 'text-center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'filter_key' => 'a!active'
            ),
            'newsletter' => array(
                'title' => $this->l('Newsletter'),
                'align' => 'text-center',
                'type' => 'bool',
                'callback' => 'printNewsIcon',
                'orderby' => false
            ),
            'date_add' => array(
                'title' => $this->l('Registration'),
                'type' => 'date',
                'align' => 'text-right'
            ),
            'connect' => array(
                'title' => $this->l('Last visit'),
                'type' => 'datetime',
                'search' => false,
                'havingFilter' => true
            )
        ));

        $this->shopLinkType = 'shop';
        $this->shopShareDatas = Shop::SHARE_CUSTOMER;

        AdminController::__construct();

        // Check if we can add a customer
        if (Shop::isFeatureActive() && (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP))
            $this->can_add_customer = false;
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display) && $this->can_add_customer) {
            $this->page_header_toolbar_btn['import'] = array(
                'href' => self::$currentIndex.'&import&token='.$this->token,
                'desc' => $this->l('Import discounts', null, null, false),
                'icon' => 'process-icon-download'
            );
            $this->page_header_toolbar_btn['new_customer'] = array(
                'href' => self::$currentIndex.'&addcustomer&token='.$this->token,
                'desc' => $this->l('Add new customer', null, null, false),
                'icon' => 'process-icon-new'
            );
        }

        AdminController::initPageHeaderToolbar();
    }

    public function initProcess()
    {
        AdminController::initProcess();

        if (Tools::isSubmit('submitGuestToCustomer') && $this->id_object)
        {
            if ($this->tabAccess['edit'] === '1')
                $this->action = 'guest_to_customer';
            else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }
        elseif (Tools::isSubmit('changeNewsletterVal') && $this->id_object)
        {
            if ($this->tabAccess['edit'] === '1')
                $this->action = 'change_newsletter_val';
            else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }
        elseif (Tools::isSubmit('changeOptinVal') && $this->id_object)
        {
            if ($this->tabAccess['edit'] === '1')
                $this->action = 'change_optin_val';
            else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }
        elseif (Tools::isSubmit('import'))
        {
            $this->importDiscounts();
        }

        // When deleting, first display a form to select the type of deletion
        if ($this->action == 'delete' || $this->action == 'bulkdelete')
            if (Tools::getValue('deleteMode') == 'real' || Tools::getValue('deleteMode') == 'deleted')
                $this->delete_mode = Tools::getValue('deleteMode');
            else
                $this->action = 'select_delete';
    }


    public function renderForm()
    {
        if (!($obj = $this->loadObject(true)))
            return;

        $genders = Gender::getGenders();
        $list_genders = array();
        foreach ($genders as $key => $gender)
        {
            $list_genders[$key]['id'] = 'gender_'.$gender->id;
            $list_genders[$key]['value'] = $gender->id;
            $list_genders[$key]['label'] = $gender->name;
        }

        $years = Tools::dateYears();
        $months = Tools::dateMonths();
        $days = Tools::dateDays();

        $groups = Group::getGroups($this->default_form_language, true);
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Customer'),
                'icon' => 'icon-user'
            ),
            'input' => array(
                array(
                    'type' => 'radio',
                    'label' => $this->l('Social title'),
                    'name' => 'id_gender',
                    'required' => false,
                    'class' => 't',
                    'values' => $list_genders
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('First name'),
                    'name' => 'firstname',
                    'required' => true,
                    'col' => '4',
                    'hint' => $this->l('Invalid characters:').' 0-9!&lt;&gt;,;?=+()@#"°{}_$%:'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Last name'),
                    'name' => 'lastname',
                    'required' => true,
                    'col' => '4',
                    'hint' => $this->l('Invalid characters:').' 0-9!&lt;&gt;,;?=+()@#"°{}_$%:'
                ),
                array(
                    'type' => 'text',
                    'prefix' => '<i class="icon-envelope-o"></i>',
                    'label' => $this->l('Email address'),
                    'name' => 'email',
                    'col' => '4',
                    'required' => true,
                    'autocomplete' => false
                ),
                array(
                    'type' => 'password',
                    'label' => $this->l('Password'),
                    'name' => 'passwd',
                    'required' => ($obj->id ? false : true),
                    'col' => '4',
                    'hint' => ($obj->id ? $this->l('Leave this field blank if there\'s no change.') :
                        sprintf($this->l('Password should be at least %s characters long.'), Validate::PASSWORD_LENGTH))
                ),
                array(
                    'type' => 'birthday',
                    'label' => $this->l('Birthday'),
                    'name' => 'birthday',
                    'options' => array(
                        'days' => $days,
                        'months' => $months,
                        'years' => $years
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Enabled'),
                    'name' => 'active',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                    'hint' => $this->l('Enable or disable customer login.')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Newsletter'),
                    'name' => 'newsletter',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'newsletter_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'newsletter_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                    'hint' => $this->l('This customer will receive your newsletter via email.')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Opt-in'),
                    'name' => 'optin',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'optin_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'optin_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                    'hint' => $this->l('This customer will receive your ads via email.')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Avatar'),
                    'name' => 'avatar',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                    'hint' => $this->l('Enable or disable customer avatar.')
                ),
                array(
                    'type'=>'file',
                    'name'=>'avatarImg',
                    'thumb'=>$obj->getUserAvatarPath(),
                    'title'=>$this->l('Avatar')

                )
            )
        );

        // if we add a customer via fancybox (ajax), it's a customer and he doesn't need to be added to the visitor and guest groups
        if (Tools::isSubmit('addcustomer') && Tools::isSubmit('submitFormAjax'))
        {
            $visitor_group = Configuration::get('PS_UNIDENTIFIED_GROUP');
            $guest_group = Configuration::get('PS_GUEST_GROUP');
            foreach ($groups as $key => $g)
                if (in_array($g['id_group'], array($visitor_group, $guest_group)))
                    unset($groups[$key]);
        }

        $this->fields_form['input'] = array_merge(
            $this->fields_form['input'],
            array(
                array(
                    'type' => 'group',
                    'label' => $this->l('Group access'),
                    'name' => 'groupBox',
                    'values' => $groups,
                    'required' => true,
                    'col' => '6',
                    'hint' => $this->l('Select all the groups that you would like to apply to this customer.')
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Default customer group'),
                    'name' => 'id_default_group',
                    'options' => array(
                        'query' => $groups,
                        'id' => 'id_group',
                        'name' => 'name'
                    ),
                    'col' => '4',
                    'hint' => array(
                        $this->l('This group will be the user\'s default group.'),
                        $this->l('Only the discount for the selected group will be applied to this customer.')
                    )
                )
            )
        );

        // if customer is a guest customer, password hasn't to be there
        if ($obj->id && ($obj->is_guest && $obj->id_default_group == Configuration::get('PS_GUEST_GROUP')))
        {
            foreach ($this->fields_form['input'] as $k => $field)
                if ($field['type'] == 'password')
                    array_splice($this->fields_form['input'], $k, 1);
        }

        if (Configuration::get('PS_B2B_ENABLE'))
        {
            $risks = Risk::getRisks();

            $list_risks = array();
            foreach ($risks as $key => $risk)
            {
                $list_risks[$key]['id_risk'] = (int)$risk->id;
                $list_risks[$key]['name'] = $risk->name;
            }

            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('Company'),
                'name' => 'company'
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('SIRET'),
                'name' => 'siret'
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('APE'),
                'name' => 'ape'
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('Website'),
                'name' => 'website'
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('Allowed outstanding amount'),
                'name' => 'outstanding_allow_amount',
                'hint' => $this->l('Valid characters:').' 0-9',
                'suffix' => $this->context->currency->sign
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('Maximum number of payment days'),
                'name' => 'max_payment_days',
                'hint' => $this->l('Valid characters:').' 0-9'
            );
            $this->fields_form['input'][] = array(
                'type' => 'select',
                'label' => $this->l('Risk rating'),
                'name' => 'id_risk',
                'required' => false,
                'class' => 't',
                'options' => array(
                    'query' => $list_risks,
                    'id' => 'id_risk',
                    'name' => 'name'
                ),
            );
        }

        $this->fields_form['submit'] = array(
            'title' => $this->l('Save'),
        );

        $birthday = explode('-', $this->getFieldValue($obj, 'birthday'));

        $this->fields_value = array(
            'years' => $this->getFieldValue($obj, 'birthday') ? $birthday[0] : 0,
            'months' => $this->getFieldValue($obj, 'birthday') ? $birthday[1] : 0,
            'days' => $this->getFieldValue($obj, 'birthday') ? $birthday[2] : 0,
        );

        // Added values of object Group
        if (!Validate::isUnsignedId($obj->id))
            $customer_groups = array();
        else
            $customer_groups = $obj->getGroups();
        $customer_groups_ids = array();
        if (is_array($customer_groups))
            foreach ($customer_groups as $customer_group)
                $customer_groups_ids[] = $customer_group;

        // if empty $carrier_groups_ids : object creation : we set the default groups
        if (empty($customer_groups_ids))
        {
            $preselected = array(Configuration::get('PS_UNIDENTIFIED_GROUP'), Configuration::get('PS_GUEST_GROUP'), Configuration::get('PS_CUSTOMER_GROUP'));
            $customer_groups_ids = array_merge($customer_groups_ids, $preselected);
        }

        foreach ($groups as $group)
            $this->fields_value['groupBox_'.$group['id_group']] =
                Tools::getValue('groupBox_'.$group['id_group'], in_array($group['id_group'], $customer_groups_ids));

        return AdminController::renderForm();
    }

    public function importDiscounts()
    {
        $import = new Import();
        $import_filename = '';
        $import_file = '';
        foreach (glob(__DIR__ . '/../../../export_import/import/discounts/*.csv') as $file) {
            $filename = substr($file, strrpos($file, '/') + 1);
            if ($filename > $import_filename || $import_filename = '') {
                $import_file = $file;
                $import_filename = $filename;
            } else {
                rename($file, __DIR__ . '/../../../export_import/import/discounts/DELETED/'.$filename);
            }
        }
        if ($import_filename) {
            $import->importDiscounts($import_file);
            rename($file, __DIR__ . '/../../../export_import/import/discounts/IMPORTED/'.$import_filename);
            $import->writeLogToFile();
        }
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminCustomers'));
    }

    public function processSave()
    {
        $customer = parent::processSave();

        if (!is_object($customer)) return $customer;

        $image_uploader = new HelperImageUploader('avatarImg');
        $image_uploader->setAcceptTypes(Customer::$avatarType);
        $files = $image_uploader->process();

        if (isset($files[0])) $file = $files[0];

        if (!count($this->errors) && isset($file) && empty($file['error'])) {
            $error = 0;
            $newPath = $customer->getAvatarPathCreation();

            foreach (Customer::$avatarSize as $size => $a) {

                if (!ImageManager::resize($file['save_path'], $newPath . 'avatar_' . $size . '.jpg', $a[0], $a[1], 'jpg', false, $error)) {
                    switch ($error) {
                        case ImageManager::ERROR_FILE_NOT_EXIST :
                            $this->errors[] = Tools::displayError('An error occurred while copying image, the file does not exist anymore.');
                            break;

                        case ImageManager::ERROR_FILE_WIDTH :
                            $this->errors[] = Tools::displayError('An error occurred while copying image, the file width is 0px.');
                            break;

                        case ImageManager::ERROR_MEMORY_LIMIT :
                            $this->errors[] = Tools::displayError('An error occurred while copying image, check your memory limit.');
                            break;
                        default:
                            $this->errors[] = Tools::displayError('An error occurred while copying image.');
                            break;
                    }
                    break;
                }
            }
            if (!empty($file['error'])) {
                $this->errors[] = $file['error'];
            }

            unlink($file['save_path']);
            //Necesary to prevent hacking
            unset($file['save_path']);
        }

        return $customer;
    }
}
