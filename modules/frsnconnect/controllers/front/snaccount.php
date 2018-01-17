<?php
/**
  * Social Network connect modules
  * frsnconnect 0.15 by froZZen
  */

class FrSnConnectSnaccountModuleFrontController extends ModuleFrontController {
    
    public $ssl = true;
    public $display_column_left = false;
  
    public function init() {
        
	parent::init();

	$this->addCSS($this->module->getPathUri().'frsnconnect.css');
                
        if (!count(Context::getContext()->fr_sn_servlist)) {
            require_once(_PS_MODULE_DIR_.'frsnconnect/SNTools.php'); 

            Context::getContext()->fr_sn_servlist = SNTools::GetSNServiceList(); 
        }       
                
    }

    public function initContent() {
        
	parent::initContent();

	if (!Context::getContext()->customer->isLogged())
            Tools::redirect('index.php?controller=authentication&redirect=module&module=frsconnect&action=snaccount');

	if (Context::getContext()->customer->id) {
            $customer = Context::getContext()->customer;                   
            $serv_list = array();
            $cust_service = array();
            if (!count($customer->sn_service))
                $customer->getCustomerSnService ();   
            $fr_sn_list = Context::getContext()->fr_sn_servlist;
            if (count($customer->sn_service)) {
                foreach ($customer->sn_service as $key=>$value)
                    if (strlen($value) > 0)
                        $cust_service[$key] = array('id' => $value,
                            'id_sn_service' =>  $fr_sn_list[$key]['id_sn_service'],
                            'sn_service_name_full' =>  $fr_sn_list[$key]['sn_service_name_full'],
                            );
                    else
                        $serv_list[$key] = $fr_sn_list[$key];
            }

            $tpl_path = $this->module->getTemplatePath('frsnconnect_form.tpl');
            $this->context->smarty->assign('all_services', $fr_sn_list);
            $this->context->smarty->assign('not_services', $serv_list);
            $this->context->smarty->assign('connect_serv', $cust_service);
            $this->context->smarty->assign('tpl_path', $tpl_path);
                    
            $this->setTemplate('frsnconnect-account.tpl');
	}
        
    }

}