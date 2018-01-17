<?php
/**
* Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
*
* @category  Front Office Features
* @package   Yandex Payment Solution
* @author    Yandex.Money <cms@yamoney.ru>
* @copyright © 2015 NBCO Yandex.Money LLC
* @license   https://money.yandex.ru/doc.xml?id=527052
*/

class Metrika
{
    public $url = 'https://oauth.yandex.ru/';
    public $url_api = 'https://api-metrika.yandex.ru/management/v1/';
    public $client_id;
    public $state;
    public $errors;
    public $number;
    public $client_secret;
    public $code;
    public $token;
    public $context;
    
    public function __construct()
    {
        $this->context = Context::getContext();
        $this->client_id = Configuration::get('YA_METRIKA_ID_APPLICATION');
        $this->number = Configuration::get('YA_METRIKA_NUMBER');
        $this->client_secret = Configuration::get('YA_METRIKA_PASSWORD_APPLICATION');
        $this->state = 'Test_1';
        $this->token = Configuration::get('YA_METRIKA_TOKEN') ? Configuration::get('YA_METRIKA_TOKEN') : '';
        $this->module = Module::getInstanceByName('yamodule');
    }
    
    public function run()
    {
        $this->code = Tools::getValue('code');
        $error = Tools::getValue('error');
        if ($error == '') {
            if (empty($this->token)) {
                $this->errors = 'Пустой Токен!';
                return false;
            } else {
                return true;
            }
        } else {
            $this->errors = 'error #'.$error.' error description: '.Tools::getValue('error_description');
            return false;
        }
    }
    
    public function getToken($type = 'def')
    {
        $params = array(
            'grant_type' => 'authorization_code',
            'client_id' => ($type == 'pokupki')?Configuration::get('YA_POKUPKI_ID'):$this->client_id,
            'client_secret' => ($type == 'pokupki')?Configuration::get('YA_POKUPKI_PW'):$this->client_secret,
            'code' => $this->code
        );
        $response = $this->post($this->url.'token', array(), $params, 'POST');
        $data = Tools::jsonDecode($response->body);
        if ($response->status_code == 200) {
            $this->token = $data->access_token;
            if ($type == 'metrika') {
                Configuration::updateValue('YA_METRIKA_TOKEN', $this->token);
            } elseif ($type == 'pokupki') {
                Configuration::updateValue('YA_POKUPKI_YATOKEN', $this->token);
            }
        } else {
            $this->errors = 'error #'.$response->status_code
                .' error description: '.$data->error_description.' '.$data->error;
        }
    }
    
    public function getCode()
    {
        $params = array(
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'state' => $this->state
        );
        $params = http_build_query($params);
        Tools::redirect($this->url.'authorize?'.$params);
    }
    
    // Все счётчики
    public function getAllCounters()
    {
        return $this->sendResponse('counters', array(), array(), 'GET');
    }
    
    // Конкретный счётчик
    public function getCounter()
    {
        return $this->sendResponse('counter/'.$this->number, array(), array(), 'GET');
    }
    
    // Проверка кода счётчика
    public function getCounterCheck()
    {
        return $this->sendResponse('counter/'.$this->number.'/check', array(), array(), 'GET');
    }
    
    // Все цели счётчика
    public function getCounterGoals()
    {
        return $this->sendResponse('counter/'.$this->number.'/goals', array(), array(), 'GET');
    }
    
    // Конкретная цель
    public function getCounterGoal($goal)
    {
        return $this->sendResponse('counter/'.$this->number.'/goal/'.$goal, array(), array(), 'GET');
    }
    
    // Добавление цели
    public function addCounterGoal($params)
    {
        return $this->sendResponse('counter/'.$this->number.'/goals', array(), $params, 'POSTJSON');
    }
    
    // Удаление цели
    public function deleteCounterGoal($goal)
    {
        return $this->sendResponse('counter/'.$this->number.'/goal/'.$goal, array(), array(), 'DELETE');
    }
    
    // Редактирование счётчика
    public function editCounter()
    {
        $params = array(
            'counter' => array(
                'goals_remove' => 0,
                'code_options' => array(
                    'clickmap' => Configuration::get('YA_METRIKA_SET_CLICKMAP') ? 1 : 0,
                    'external_links' => Configuration::get('YA_METRIKA_SET_OUTLINK') ? 1 : 0,
                    'visor' => Configuration::get('YA_METRIKA_SET_WEBVIZOR') ? 1 : 0,
                    'denial' => Configuration::get('YA_METRIKA_SET_OTKAZI') ? 1 : 0,
                    'track_hash' => Configuration::get('YA_METRIKA_SET_HASH') ? 1 : 0,
                )
            )
        );

        if (count($params)) {
            return $this->sendResponse('counter/'.$this->number, array(), $params, 'PUT');
        }
    }
    
    public function sendResponse($to, $headers, $params, $type, $pretty = 1)
    {
        $response = $this->post(
            $this->url_api.$to.'?pretty='.$pretty.'&oauth_token='.$this->token,
            $headers,
            $params,
            $type
        );

        $data = Tools::jsonDecode($response->body);
        if ($response->status_code == 200) {
            return $data;
        } else {
            $this->module->logSave($response->body);
        }
    }
    
    public static function post($url, $headers, $params, $type)
    {
        $curlOpt = array(
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLINFO_HEADER_OUT => 1,
            CURLOPT_USERAGENT => 'php-market',
        );
        
        switch (Tools::strtoupper($type)) {
            case 'DELETE':
                $curlOpt[CURLOPT_CUSTOMREQUEST] = "DELETE";
                break;
            case 'GET':
                if (!empty($params)) {
                    $url .= (strpos($url, '?')===false ? '?' : '&') . http_build_query($params);
                }
                break;
            case 'PUT':
                $headers[] = 'Content-Type: application/x-yametrika+json';
                $body = Tools::jsonEncode($params);
                $fp = fopen('php://temp/maxmemory:256000', 'w');
                if (!$fp) {
                    throw new PrestaShopException('Could not open temp memory data');
                }
                fwrite($fp, $body);
                fseek($fp, 0);
                $curlOpt[CURLOPT_PUT] = 1;
                $curlOpt[CURLOPT_BINARYTRANSFER] = 1;
                $curlOpt[CURLOPT_INFILE] = $fp; // file pointer
                $curlOpt[CURLOPT_INFILESIZE] = Tools::strlen($body);
                break;
            case 'POST':
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $curlOpt[CURLOPT_HTTPHEADER] = $headers;
                $curlOpt[CURLOPT_POST] = true;
                $curlOpt[CURLOPT_POSTFIELDS] = http_build_query($params);
                break;
            case 'POSTJSON':
                $headers[] = 'Content-Type: application/x-yametrika+json';
                $curlOpt[CURLOPT_HTTPHEADER] = $headers;
                $curlOpt[CURLOPT_POST] = true;
                $curlOpt[CURLOPT_POSTFIELDS] = Tools::jsonEncode($params);
                break;
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOpt);
        $rbody = curl_exec($curl);
        $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // Tools::d(curl_getinfo($curl, CURLINFO_HEADER_OUT));
        curl_close($curl);
        $result = new stdClass();
        $result->status_code = $rcode;
        $result->body = $rbody;
        return $result;
    }
}
