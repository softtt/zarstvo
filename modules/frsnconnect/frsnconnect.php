<?php
/**
  * Social Network connect modules
  * frsnconnect 0.15 by froZZen
  */
  
if (!defined('_PS_VERSION_'))
	exit;

require_once(dirname(__FILE__).'/SNTools.php'); 

class FRSnConnect extends Module {
	
    private $fr_sn_servlist = array();
    
    public function __construct() {
        
        $this->name = 'frsnconnect';
	$this->tab = 'social_networks';
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
        $this->version = '0.15.1';
	$this->author = 'froZZen';
	$this->need_instance = 0;
	$this->module_key = '311a7edeaa66b94049e25d931cf1ec1b';

	parent::__construct();

	$this->displayName = $this->l('Social Network Connection');
	$this->description = $this->l('Adds a block for customer to login or register via Social Networks');
        
    }

    public function install() {
        
        if (!parent::install()
            || !$this->registerHook('displayMyAccountBlock')
            || !$this->registerHook('displayCustomerAccount')
            || !$this->registerHook('displayFooter')
	    || !Configuration::updateValue('FRSNCONN_EMPTYEMAIL', 'test@mail.com')
            )
            return false;
  
        include dirname(__FILE__).'/upgrade/install-0.15.1.php';
        upgrade_module_0_15_1($this);
       

        return true;
        
    }
    
    public function uninstall()	{

        if (!parent::uninstall()
	    || !Configuration::deleteByName('FRSNCONN_EMPTYEMAIL')
                ) 
            return false;
        
        Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.'sn_customer`, `'._DB_PREFIX_.'sn_service`');
        
        return true;
        
    }    

    public function hookDisplayFooter($params) {
    
//        if (basename($_SERVER['REDIRECT_URL']) == 'authentication' ||
//            basename($_SERVER['REDIRECT_URL']) == 'quick-order')
//        {
        $currcontroller = strtolower(get_class($this->context->controller));
        if ($currcontroller == 'authcontroller' ||
            $currcontroller == 'orderopccontroller')
        {
        
            $this->context->controller->addCSS($this->_path.'frsnconnect.css');

            
            if (!count(Context::getContext()->fr_sn_servlist)) {
                require_once(_PS_MODULE_DIR_.'frsnconnect/SNTools.php'); 

                Context::getContext()->fr_sn_servlist = SNTools::GetSNServiceList();
            }
            
            $fr_sn_list = Context::getContext()->fr_sn_servlist;
            $tpl_path = $this->getTemplatePath('frsnconnect_form.tpl');
            $auth_css_path = _THEME_CSS_DIR_.'authentication.css';

            $this->context->smarty->assign('not_services', $fr_sn_list);
            $this->context->smarty->assign('tpl_path', $tpl_path);
            $this->context->smarty->assign('auth_css_path', $auth_css_path);
            $this->context->smarty->assign('auth', 1);

            $tplname = '';
//            if (basename($_SERVER['REDIRECT_URL']) == 'authentication')         
            if ($currcontroller == 'authcontroller')
                $tplname = 'frsnconnect-top.tpl';
//            elseif (basename($_SERVER['REDIRECT_URL']) == 'quick-order')         
            elseif ($currcontroller == 'orderopccontroller')
                $tplname = 'frsnconnect-top-opc.tpl';
  
            $html = $this->display(__FILE__, $tplname);

            $html = str_replace("\n", "", $html);
      
            $this->context->smarty->assign('html', $html);
    
            return $this->display(__FILE__, $tplname);
        }
	
    }

    public function hookDisplayCustomerAccount($params) {
		
        $this->smarty->assign('in_footer', false);
	
        return $this->display(__FILE__, 'frsnconnect-my-account.tpl');
	
    }

    public function hookDisplayMyAccountBlock($params) {
        
	$this->smarty->assign('in_footer', true);
        
	return $this->display(__FILE__, 'frsnconnect-my-account.tpl');
        
    }
        
    public function getContent() {

        $this->fr_sn_servlist = SNTools::GetSNServiceListSetup();
            
        $output = '<h2>'.$this->displayName.'</h2>';
	if (Tools::isSubmit('submitFrSnSetup')) {
            foreach($this->fr_sn_servlist as $serv) {
                if (key_exists($serv['id_sn_service'], $_REQUEST)) {
                    $updTab = array(
                        'sn_service_name_full' => pSQL($_REQUEST[$serv['id_sn_service']]['sn_service_name_full']),
                        'sn_service_key_id' => pSQL($_REQUEST[$serv['id_sn_service']]['sn_service_key_id']),
                        'sn_service_key_secret' => pSQL($_REQUEST[$serv['id_sn_service']]['sn_service_key_secret']),
                        'active' => (int)pSQL($_REQUEST[$serv['id_sn_service']]['active'])
                    );
                    $result = Db::getInstance()->autoExecute(_DB_PREFIX_.'sn_service', $updTab, 'UPDATE', '`id_sn_service` = '.(int)$serv['id_sn_service']);
                    if (!$result)
                        $errors[] = $this->l('Failed to save the changes to '.$serv['sn_service_name_full']);  
                }
            }
  
            Configuration::updateValue('FRSNCONN_EMPTYEMAIL', Tools::getValue('FRSNCONN_EMPTYEMAIL'));
	
            if (isset($errors) AND sizeof($errors))
                $output .= $this->displayError(implode('<br />', $errors));
            else
                $output .= $this->displayConfirmation($this->l('Settings updated'));

            unset($this->fr_sn_servlist);
            $this->fr_sn_servlist = SNTools::GetSNServiceListSetup();
        }
	return $output.$this->displayForm();
    }
	
    public function displayForm() {
        $output = '
            <form action="'.$_SERVER['REQUEST_URI'].'" method="post">
                <fieldset>
                    <legend><img src="'.$this->_path.'img/logo.gif" alt="" title="" />'.$this->l('Settings').'</legend>
                        <table  class="table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>'.$this->l('sn_service_name').'</th>
                                    <th>'.$this->l('sn_service_name_full').'</th>
                                    <th>'.$this->l('sn_service_key_id').'</th>
                                    <th>'.$this->l('sn_service_key_secret').'</th>
                                    <th>'.$this->l('active').'</th>
                                </tr>
                            </thead>
                            <tbody>';
        foreach($this->fr_sn_servlist as $serv) {
            $output .= '
                <tr>
                    <td>'.$serv['sn_service_name'].'</td>
                    <td>
                        <input type="text" size="20" name="'.$serv['id_sn_service'].'[sn_service_name_full]" value="'.$serv['sn_service_name_full'].'" />
                    </td>
                    <td>
                        <input type="text" size="20" name="'.$serv['id_sn_service'].'[sn_service_key_id]" value="'.$serv['sn_service_key_id'].'" />
                    </td>
                    <td>
                        <input type="text" size="40" name="'.$serv['id_sn_service'].'[sn_service_key_secret]" value="'.$serv['sn_service_key_secret'].'" />
                    </td>
                    <td>
                        <input type="radio" value="1" '.($serv['active'] == '1' ? "checked='checked'" : '').' name="'.$serv['id_sn_service'].'[active]" />
                        <label class="t"><img title="'.$this->l('On').'" alt="'.$this->l('On').'" src="../img/admin/enabled.gif"></label>
                        <input type="radio" value="0" '.($serv['active'] == '0' ? "checked='checked'" : '').' name="'.$serv['id_sn_service'].'[active]" />
                        <label class="t"><img title="'.$this->l('Off').'" alt="'.$this->l('Off').'" src="../img/admin/disabled.gif"></label>
                    </td>
                </tr>';
        }
	
        $output .= '
                </tbody>
            </table>
            <br />
            <label>'.$this->l('Default e-mail').'</label>
            <div class="margin-form">
                <input type="text" name="FRSNCONN_EMPTYEMAIL" size="20" value="'.Tools::getValue('FRSNCONN_EMPTYEMAIL', Configuration::get('FRSNCONN_EMPTYEMAIL')).'" />
            </div>
            <p>
                <center>
                    <input type="submit" name="submitFrSnSetup" value="'.$this->l('Save').'" class="button" />
                </center>
            </p>
        </fieldset>';

	$output .= '
            <br/ >
            <fieldset>
                <legend><img src="'.$this->_path.'img/comment.gif" /> '.$this->l('Guide').'</legend>
		<h2></h2>
		<p>
                Facebook register application: <a href="https://developers.facebook.com/apps/">https://developers.facebook.com/apps/</a>
                </p>
                <p>
                VKontakte register application: <a href="http://vkontakte.ru/editapp?act=create&site=1">http://vkontakte.ru/editapp?act=create&site=1</a>
                </p>
                <p>
  Odnoklassniki register application: <a href="http://dev.odnoklassniki.ru/wiki/pages/viewpage.action?pageId=12878032">http://dev.odnoklassniki.ru/wiki/pages/viewpage.action?pageId=12878032</a><br /> 
   <a href="http://www.odnoklassniki.ru/dk?st.cmd=appsInfoMyDevList&st._aid=Apps_Info_MyDev">http://www.odnoklassniki.ru/dk?st.cmd=appsInfoMyDevList&st._aid=Apps_Info_MyDev</a>
   <br />Need VALUABLE ACCESS
   <br />Field <b>sn_service_key_secret</b> must be: <i>client_secret<b>;</b>client_public</i>
                </p>
                
                <p>
Twitter register application: <a href="https://dev.twitter.com/apps/new">https://dev.twitter.com/apps/new</a>
                </p>

                <p>
Google register application: <a href="https://code.google.com/apis/console/">https://code.google.com/apis/console/</a>
                </p>


                <p>
MailRu register application: <a href="http://api.mail.ru/sites/my/add">http://api.mail.ru/sites/my/add</a>
                </p>

 
                <p>
                <b>NOTE:</b> <i>'.$this->l('If you want to remove this module, you MUST first uninstall it!').'</i>
                </p>
                <p>
                <b>Support, feedback and issues:</b> <a href="mailto:frozzen@pisem.net">frozzen(at)pisem.net</a>
                </p>
            </fieldset>';

	$output .= '
            </form>';
	return $output;
    }
    
}

?>
