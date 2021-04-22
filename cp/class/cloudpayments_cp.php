<?php

  class cloudpayments_cp {
    const client_id = "app.5c37113e.........00665819";
    const client_secret = "81YPYGL4eBZ3QXsn..............mgpNt6wgogEINK";

    const handler_name = "dev_cloudpayments_cp";

    public function callB24Method(array $auth, $method, $params) ///OK
    {
      //  print_r($auth);
      $c = curl_init('https://' . $auth['domain'] . '/rest/' . $method . '.json');
      //  echo 'https://' . $auth['domain'] . '/rest/' . $method . '.json';
      self::Log('curl_init');
      self::Log('https://' . $auth['domain'] . '/rest/' . $method . '.json');
      $params["auth"] = $auth["access_token"];
      self::Log($params);

      curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($c, CURLOPT_POST, true);
      curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($params));   //http_build_query
      $response = curl_exec($c);
      $response = json_decode($response, true);

      if($response['error_description'] == "Access denied"):
        self::GetNewAccessToken($auth, $auth['domain']);
      endif;
      //
      return $response;
    }

    public function addEventUninstall($auth) ///OK
    {
      self::Log('addEventUninstall');
      $params = array(
        "event" => "ONAPPUNINSTALL",
        "handler" => "https://devbitrix24.tk/api/cp/uninstall.php"
      );
      return self::callB24Method($auth, "event.bind", $params);
    }

    public function domenLog($domen, $str, $domenLog) {
      if($domen == $domenLog) {
        $file = $_SERVER['DOCUMENT_ROOT'] . '/log_2021.txt';
        $current = file_get_contents($file);
        $current .= date("d-m-Y H:i:s") . print_r($str, 1) . "\n";
        file_put_contents($file, $current . "\n");
      }
    }

    public function addStatus($auth, $params) {
      $status_list = self::callB24Method($auth, "crm.invoice.status.list", array());
      foreach($status_list['result'] as $res):
        if($res['NAME'] == 'Возврат CP')
          return false;
      endforeach;
      self::Log('addStatus1');
      return self::callB24Method($auth, "crm.invoice.status.add", $params);
    }

    public function install($auth) //OK
    {
      self::Log('install');
      $events = array(
        "event" => "onCrmInvoiceSetStatus",
        "handler" => "https://devbitrix24.tk/api/cp/index.php?event=status_update"
      );
      self::addEvent($auth, $events);
      $params1 = array("fields" => array("NAME" => "Возврат CP", "SORT" => 99));
      $res = self::addStatus($auth, $params1);

      self::Log('auth');
      self::Log($auth);
      $res = self::addEventUninstall($auth);
      if($PAY_ID = self::getPaymentCP($auth)):
        self::uninstall($auth, $PAY_ID);
      endif;

      $status_list = self::callB24Method($auth, "crm.invoice.status.list", array());
      $STATUS_OPTIONS = array();
      $i = 0;
      foreach($status_list['result'] as $stat):
        $i++;
        $STATUS_OPTIONS[$stat['STATUS_ID']] = $stat['NAME'];//$stat['STATUS_ID'] $stat['NAME'];
      endforeach;
      self::Log("____status_list____");
      self::Log($status_list);
      $params = array(
        'NAME' => self::handler_name,                     // Название обработчика
        'SORT' => 100,                                 // Сортировка
        'CODE' => self::handler_name,
        'SETTINGS' => array(                                // Настройки обработчика
          //  'CURRENCY' => array('RUB','USD','EUR'),                        // Список валют, которые поддерживает обработчик
          'FORM_DATA' => array(                           // Настройки формы
            'ACTION_URI' => 'https://devbitrix24.tk/api/cp/index.php?event=widjet&domain=' . $_REQUEST['auth']['domain'],
            // URL, на который будет отправляться форма
            'METHOD' => 'POST',
            // Метод отправки формы
            'PARAMS' => array(                           // Карта соответствия полей между полями формы и параметрами обработчика: массив вида array(код_поля =>> код_параметра_обработчика)
              'publicID' => 'PUBLIC_ID',
              'invoiceNumber' => 'PAYMENT_ID',
              'Sum' => 'PAYMENT_SHOULD_PAY',
              'customer' => 'PAYMENT_BUYER_ID',
              'SECRET_KEY' => 'SECRET_KEY',
              'PAYMENT_ID' => 'PAYMENT_ID',
              'WIDGET_LANG' => 'WIDGET_LANG',
              'CHECKONLINE' => 'CHECKONLINE',
              'TYPE_NALOG' => 'TYPE_NALOG',
              'VAT' => 'VAT',
              'METHOD' => 'METHOD',
              'OBJECT' => 'OBJECT',
              'currency' => 'currency',
              'skin' => 'skin',
              'USER_EMAIL' => 'USER_EMAIL',
              'USER_PHONE' => 'USER_PHONE',
              'DATE_INSERT' => 'DATE_INSERT',
              'STATUS_REFUND' => 'STATUS_REFUND',
              'STATUS_PAY' => 'STATUS_PAY'
            ),
          ),
          'CODES' => array(
            'USER_EMAIL' => array(
              'NAME' => 'EMAIL контакта',
              'SORT' => 1000,
              'GROUP' => 'PAYMENT',
              'DEFAULT' => array(
                'PROVIDER_KEY' => 'REQUISITE',
                'PROVIDER_VALUE' => 'RQ_EMAIL|1'
              )
            ),
            'USER_PHONE' => array(
              'NAME' => 'Телефон контакта',
              'SORT' => 1000,
              'GROUP' => 'PAYMENT',
              'DEFAULT' => array(
                'PROVIDER_KEY' => 'REQUISITE',
                'PROVIDER_VALUE' => 'RQ_PHONE|1'
              )
            ),
            'PUBLIC_ID' => array(                     // Код параметра
              'NAME' => 'Номер магазина',               // Название параметра
              'DESCRIPTION' => 'Номер магазина',         // Описание параметра
              'SORT' => 100,                        // Сортировка
            ),
            'SECRET_KEY' => array(
              'NAME' => 'Секретный ключ.',
              'DESCRIPTION' => 'Секретный ключ',
              'SORT' => 300,
            ),
            'STATUS_PAY' => array(
              'NAME' => 'Статус оплаты',
              'DESCRIPTION' => '',
              'SORT' => 302,
              'GROUP' => 'PAYMENT',
              'TYPE' => 'SELECT',
              'INPUT' => array(
                'TYPE' => 'ENUM',
                'OPTIONS' => $STATUS_OPTIONS,
              ),
            ),
            'STATUS_REFUND' => array(
              'NAME' => 'Статус возврата',
              'DESCRIPTION' => '',
              'SORT' => 303,
              'GROUP' => 'PAYMENT',
              "TYPE" => "SELECT",
              "INPUT" => array(
                'TYPE' => 'ENUM',
                'OPTIONS' => $STATUS_OPTIONS,
              ),
            ),
            "WIDGET_LANG" => array(
              "NAME" => "Язык виджета",
              "DESCRIPTION" => "",
              'SORT' => 304,
              'GROUP' => 'PAYMENT',
              "TYPE" => "SELECT",
              "INPUT" => array(
                'TYPE' => 'ENUM',
                'OPTIONS' => array(
                  'ru-RU' => "Русский MSK",
                  'en-US' => "Английский CET",
                  'de-DE' => "Немецкий CET",
                  "lv" => "Латышский CET",
                  "az" => "Азербайджанский AZT",
                  "kk" => "Русский ALMT",
                  "kk-KZ" => "Казахский ALMT",
                  "uk" => "Украинский EET",
                  "pl" => "Польский CET",
                  "pt" => "Португальский CET"
                ),
              ),
            ),
            "currency" => array(
              "NAME" => "Валюта",
              "DESCRIPTION" => "",
              'SORT' => 305, 
              'GROUP' => 'PAYMENT',
              "TYPE" => "SELECT",
              "INPUT" => array(
                'TYPE' => 'ENUM',
                'OPTIONS' => array(
                  "RUB" => "Российский рубль",
                  "EUR" => "Евро",
                  "USD" => "Доллар США",
                  "GBP" => "Фунт стерлингов",
                  "UAH" => "Украинская гривна",
                  "BYN" => "Белорусский рубль",
                  "KZT" => "Казахский тенге",
                  "AZN" => "Азербайджанский манат	",
                  "CHF" => "Швейцарский франк",
                  "CZK" => "Чешская крона",
                  "CAD" => "Канадский доллар",
                  "PLN" => "Польский злотый",
                  "EK" => "Шведская крона",
                  "TRY" => "Турецкая лира",
                  "CNY" => "Китайский юань",
                  "INR" => "Индийская рупия",
                  "BRL" => "Бразильский реал",
                  "ZAL" => "Южноафриканский рэнд",
                  "UZS" => "Узбекский сум",
                ),
              ),
            ),
            "skin" => array(
              "NAME" => "Дизайн виджета",
              "DESCRIPTION" => "",
              'SORT' => 306, 
              'GROUP' => 'PAYMENT',
              "TYPE" => "SELECT",
              "INPUT" => array(
                'TYPE' => 'ENUM',
                'OPTIONS' => array(
                  "classic" => "classic",
                  "modern" => "modern",
                  "mini" => "mini"
                ),
              ),
            ),
            "CHECKONLINE" => array(
              "NAME" => "Использовать функционал онлайн касс",
              "DESCRIPTION" => "",
              'SORT' => 399,
              'GROUP' => 'PAYMENT',
              "INPUT" => array(
                'TYPE' => 'Y/N'
              ),
            ),
            "TYPE_NALOG" => array(
              "NAME" => "Система налогообложения",
              "DESCRIPTION" => "",
              'SORT' => 402,
              'GROUP' => 'PAYMENT',
              "TYPE" => "SELECT",
              "INPUT" => array(
                'TYPE' => 'ENUM',
                'OPTIONS' => array(
                  '0' => "Общая система налогообложения",
                  '1' => "Упрощенная система налогообложения (Доход)",
                  "2" => "Упрощенная система налогообложения (Доход минус Расход)",
                  "3" => "Единый налог на вмененный доход",
                  "4" => "Единый сельскохозяйственный налог",
                  "5" => "Патентная система налогообложения"
                ),
              ),
            ),
            "VAT" => array(
              "NAME" => "НДС",
              "DESCRIPTION" => "",
              'SORT' => 403,
              'GROUP' => 'PAYMENT',
              "TYPE" => "SELECT",
              "INPUT" => array(
                'TYPE' => 'ENUM',
                'OPTIONS' => array(
                  'null' => "НДС не облагается",
                  '0' => "НДС 0%",
                  '10' => "НДС 10%",
                  "12" => "НДС 12%",
                  "20" => "НДС 20%",
                  "110" => "расчетный НДС 10/110",
                  "120" => "расчетный НДС 20/120"
                ),
              ),
            ),
            "METHOD" => array(
              "NAME" => "Признак способа расчета",
              "DESCRIPTION" => "",
              'SORT' => 404,
              'GROUP' => 'PAYMENT',
              "TYPE" => "SELECT",
              "INPUT" => array(
                'TYPE' => 'ENUM',
                'OPTIONS' => array(
                  '0' => "Неизвестный способ расчета",
                  "1" => "Предоплата 100%",
                  "2" => "Предоплата",
                  "3" => "Аванс",
                  "4" => "Полный расчёт",
                  "5" => "Частичный расчёт и кредит",
                  "6" => "Передача в кредит",
                  "7" => "Оплата кредита"
                ),
              ),
            ),
            "OBJECT" => array(
              "NAME" => "Признак предмета расчета",
              "DESCRIPTION" => "",
              'SORT' => 405,
              'GROUP' => 'PAYMENT',
              "TYPE" => "SELECT",
              "INPUT" => array(
                'TYPE' => 'ENUM',
                'OPTIONS' => array(
                  '0' => "Неизвестный способ оплаты",
                  "1" => "Товар",
                  "2" => "Подакцизный товар",
                  "3" => "Работа",
                  "4" => "Услуга",
                  "10" => "Платеж"
                ),
              ),
            ),
            "PAYMENT_ID" => array(
              "NAME" => 'Номер оплаты',
              'SORT' => 500,
              'GROUP' => 'PAYMENT',
              'DEFAULT' => array(
                'PROVIDER_KEY' => 'PAYMENT',
                'PROVIDER_VALUE' => 'ID'
              )
            ),
            "PAYMENT_DATE_INSERT" => array(
              "NAME" => "Дата оплаты",
              'SORT' => 600,
              'GROUP' => 'PAYMENT',
              'DEFAULT' => array(
                'PROVIDER_KEY' => 'PAYMENT',
                'PROVIDER_VALUE' => 'DATE_INSERT'
              )
            ),
            "PAYMENT_SHOULD_PAY" => array(
              "NAME" => 'Сумма оплаты',
              'SORT' => 700,
              'GROUP' => 'PAYMENT',
              'DEFAULT' => array(
                'PROVIDER_KEY' => 'PAYMENT',
                'PROVIDER_VALUE' => 'SUM'
              )
            ),
          )
        )
      );
      self::Log("____params____");
      self::Log($params);
      self::callB24Method($auth, "sale.paysystem.handler.add", $params);
      $status_list2 = self::callB24Method($auth, "crm.invoice.status.list", array());
      self::Log("____status_list222____");
      self::Log($status_list2);

      self::addconfigBD($auth);
      self::Log('result+++');
      self::Log($res);

      $status_list3 = self::callB24Method($auth, "crm.invoice.status.list", array());
      self::Log("____status_list333____");
      self::Log($status_list3);

    }

    public function addEvent($auth, $params) {
      self::Log('addEvent ___ ' . $params['event']);
      $params_ = array("event" => $params['event'], "handler" => $params['handler']);
      self::unbind_event($auth, $params_);
      return self::callB24Method($auth, "event.bind", $params_);
    }

    public function unbind_event($auth, $params) {
      self::Log('unbind_event ___ ' . $params['event']);
      $params_ = array("event" => $params['event'], "handler" => $params['handler']);
      return self::callB24Method($auth, "event.unbind", $params_);
    }

    public function checkInstall($auth) {
      self::Log('checkInstall');
      self::Log($auth);
      $paysystem_handler_list = self::callB24Method($auth, "sale.paysystem.handler.list", array());
      //self::Log($paysystem_handler_list);
      foreach($paysystem_handler_list['result'] as $payment):
        //self::Log('========' . $payment["CODE"]);
        if($payment["CODE"] == self::handler_name):
          $PAY_ID = $payment["ID"];
        endif;
      endforeach;
      self::Log('PAY_ID=' . $PAY_ID);
      if($PAY_ID)
        return $PAY_ID; else return false;
    } //OK

    public function uninstall($auth, $PAY_ID) {
      self::Log('uninstall');
      ////NEW start
      $res = self::callB24Method($auth, "sale.paysystem.list", array());
      if($res['result'] && count($res['result']) > 0 && sizeof($res['result']) > 0):
        foreach($res['result'] as $paySystem):
          if($paySystem['ACTION_FILE'] == "cloudpayments_cp" && !empty($paySystem['ID'])) {
            ///Удаляем созданный платежный способ с нашим обработчиком
            self::callB24Method($auth, "sale.paysystem.delete", array("id" => $paySystem['ID']));
          }
        endforeach;
      endif;
      ////NEW end

      self::callB24Method($auth, "sale.paysystem.handler.delete", array("id" => $PAY_ID));
    } //OK

    public function Log($str) ///OK
    {
      $file = $_SERVER['DOCUMENT_ROOT'] . '/log_2021.txt';
            $current = file_get_contents($file);
            $current .= date("d-m-y H:i:s") . ' :' . print_r($str, 1) . "\n";
            file_put_contents($file, $current);
    }

    function BD_connect()//OK
    {
      $bd_host = "localhost";
      $bd_user = "root";
      $bd_pass = "Kcv.....8r1";
      $bd_name = "bitrix24";

      // Создание соединения
      $mysqli = mysqli_connect($bd_host, $bd_user, $bd_pass, $bd_name);
      mysqli_set_charset($mysqli, "utf8");
      // Проверка соединения
      if(!$mysqli) {
        die("Подключение не удалось: " . mysqli_connect_error());
      }

      return $mysqli;
    }

    public function addconfigBD($auth)//OK
    {
      $mysqli = self::BD_connect();
      $params = array(
        "domain	" => '"' . $mysqli->real_escape_string($auth['domain']) . '"',
        "access_token" => '"' . $mysqli->real_escape_string($auth['access_token']) . '"',
        "refresh_token" => '"' . $mysqli->real_escape_string($auth['refresh_token']) . '"'
      );
      if($auth['application_token'])
        $params["application_token"] = '"' . $mysqli->real_escape_string($auth['application_token']) . '"';

      self::Log('______addconfigBD_________');
      self::Log($params);
      if($CONFIG_ID = self::checkConfigBd($mysqli, $auth['domain'])):
        if($auth['application_token']):
          $sql = "UPDATE `cp_payment` SET `application_token`=" . $params['application_token'] . ", `refresh_token`=" . $params['refresh_token'] . ", `access_token`=" . $params['access_token'] . " WHERE `id`=" . $CONFIG_ID;
        else:
          $sql = "UPDATE `cp_payment` SET `refresh_token`=" . $params['refresh_token'] . ", `access_token`=" . $params['access_token'] . " WHERE `id`=" . $CONFIG_ID;
        endif;
      else:
        $columns = implode(", ", array_keys($params));
        $values = implode(", ", $params);
        $sql = "INSERT INTO `cp_payment` (" . $columns . ") VALUES (" . $values . ")";
      endif;
      self::log($sql);

      mysqli_query($mysqli, $sql);
    }

    public function checkConfigBd($mysqli, $domain)//OK
    {
      $sql = "SELECT `id` FROM `cp_payment` WHERE `domain`=" . '"' . $mysqli->real_escape_string($domain) . '"';
      self::log($sql);
      $result = $mysqli->query($sql);
      if($result->num_rows > 0):
        if($row = $result->fetch_assoc()):
          if(!empty($row['id'])):
            return $row['id'];
          endif;
        endif;
      endif;
      return false;
    }

    function checkAccessToken($domain, $access_token, $refresh_token)//OK
    {
      $auth = array("access_token" => $access_token, "domain" => $domain, "refresh_token" => $refresh_token);
      $params = array();
      $res = self::callB24Method($auth, "app.info", $params);

      self::Log('checkAccessToken');
      self::Log($res);

      if(!isset($res['error'])):
        return $auth['access_token'];
      else:
        return self::GetNewAccessToken($auth, $domain);
      endif;
    }

    function getAuthorize($domain) {
      /*      $url = $domain . "/oauth/authorize/?client_id=" . self::client_id . "&response_type=code&redirect_uri=https://bitrix24.cloudpayments.ru/api/cp/index.php?event=authorize&domain=" . $domain;
            self::Log($url);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $response = curl_exec($ch);
            self::Log("__________11111____");
           // self::Log($response);*/

      $params = self::getConfig2($domain);
      $url = $domain . "/oauth/token/?client_id=" . self::client_id . "&grant_type=authorization_code&client_secret=" . self::client_secret . "&redirect_uri=https://devbitrix24.tk/api/cp/index.php?event=authorize&code=" . $params['application_token'] . "&scope=pay_system,crm";
      self::Log($url);
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
      curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      $response = curl_exec($ch);
      self::Log($response);
      $response = json_decode($response, true);
      if($response['access_token']):
        $auth = array(
          "domain" => $domain,
          "access_token" => $response['access_token'],
          "refresh_token" => $response['refresh_token']
        );
        self::Log("__________getAuthorize_______");
        self::Log($auth);
        self::addconfigBD($auth);
      endif;

      return false;
    }

    function setAuthorize($domain, $code) {
      $mysqli = self::BD_connect();
      self::Log('setAuthorize');
      self::Log($_REQUEST);
      if($CONFIG_ID = self::checkConfigBd($mysqli, $domain)):
        if($mysqli->real_escape_string($code)) {
          $sql = "UPDATE `cp_payment` SET `application_token`=" . '"' . $mysqli->real_escape_string($code) . '"' . " WHERE `id`=" . $CONFIG_ID;
          mysqli_query($mysqli, $sql);
        }
      endif;
    }

    function getAccess2($domain) {
      $url = $domain . "/oauth/token/?client_id=" . self::client_id . "&grant_type=authorization_code&client_secret=" . self::client_secret . "&redirect_uri=http%3A%2F%2Flocalhost%3A70005&code=код_получения_авторизации&scope=требуемый_набор_разрешений";
    }

    function GetNewAccessToken($auth, $domain)///OK
    {
      // self::getAuthorize($domain);
      self::Log('__________GetNewAccessToken_____________');
      $auth = self::getConfig2($domain);
      self::Log($auth);
      $url = "https://oauth.bitrix.info/oauth/token/?grant_type=refresh_token&client_id=" . self::client_id . "&client_secret=" . self::client_secret . "&refresh_token=" . $auth['refresh_token'];
      // print_r($url);
      //print_r($auth);
      self::Log($url);
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
      curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      $response = curl_exec($ch);
      self::Log($response);
      $response = json_decode($response, true);
      curl_close($ch);

      /*      if($_POST['test']){
            echo $url;
            echo '<pre>';
            print_r($response);
            echo '</pre>';
            die();
            }*/

      if($response['access_token']):
        $auth = array(
          "domain" => $domain,
          "access_token" => $response['access_token'],
          "refresh_token" => $response['refresh_token']
        );
        self::Log($auth);
        self::addconfigBD($auth);
        return $response['access_token'];
      endif;

      return false;
    }

    function getConfig($domain)///OK
    {
      if(!$domain)
        return false;
      $mysqli = self::BD_connect();
      $sql = "SELECT * FROM `cp_payment` WHERE `domain`=" . '"' . $mysqli->real_escape_string($domain) . '"';
      $result = $mysqli->query($sql);
      if($result->num_rows > 0):
        if($row = $result->fetch_assoc()):
          $row['access_token'] = self::checkAccessToken($row['domain'], $row['access_token'], $row['refresh_token']);
          if($_POST['test']) {
            if($row['date'] < "2019-08-26 00:00:00") {
              // die();
            }
          }
          return $row;
        endif;
      endif;
    }

    function getConfig2($domain)///OK
    {
      if(!$domain)
        return false;
      $mysqli = self::BD_connect();
      $sql = "SELECT * FROM `cp_payment` WHERE `domain`=" . '"' . $mysqli->real_escape_string($domain) . '"';
      $result = $mysqli->query($sql);
      if($result->num_rows > 0):
        if($row = $result->fetch_assoc()):
          return $row;
        endif;
      endif;
    }

    public function getPaymentForm($auth, $POST)///OK
    {
      self::Log("___getPaymentForm111___");
      $required_fields = array("domain", "publicID", "WIDGET_LANG", "currency", "skin", "PAYMENT_ID");
      foreach($required_fields as $val):
        if(!isset($POST[$val]) || empty($POST[$val])):
          echo 'ERROR ' . $val;
          return;
        endif;
      endforeach;

      $data = array();
      $items = array();

      $params_config = self::getConfig($POST['domain']);
      $data2['InvoiceId'] = $POST['PAYMENT_ID'];
      $PaymentConfig = self::getPaymentConfig($params_config, $data2);

      $order = self::get_order($params_config, $PaymentConfig);
      self::Log("___get_order___");
      self::Log($PaymentConfig);
      self::Log($order);

      if($order['PRODUCT_ROWS'] && count($order['PRODUCT_ROWS']) > 0):
        foreach($order['PRODUCT_ROWS'] as $item):
          $items[] = array(
            'label' => $item['PRODUCT_NAME'],
            'price' => number_format($item['PRICE'], 2, ".", ''),
            'quantity' => $item['QUANTITY'],
            'amount' => number_format($item['PRICE'] * $item['QUANTITY'], 2, ".", ''),
            'vat' => $PaymentConfig["VAT"],
            'method' => $PaymentConfig["METHOD"],
            'object' => $PaymentConfig["OBJECT"],
          );
        endforeach;
      else:
        echo 'ERROR - PRODUCT ITEMS NULL';
        die();
      endif;

      $hash = md5($POST['domain'] . $auth['access_token']);
      if($POST['CHECKONLINE'] == 'Y'):
        $data['cloudPayments']['customerReceipt']['Items'] = $items;
        $data['cloudPayments']['customerReceipt']['taxationSystem'] = $POST['TYPE_NALOG'];
        $data['cloudPayments']['customerReceipt']['email'] = $POST['USER_EMAIL'];
        $data['cloudPayments']['customerReceipt']['phone'] = $POST['USER_PHONE'];
      endif;
      $data['hash'] = $hash;
      $data['DOMEN'] = $POST['domain'];

      ?>
       Редирект на страницу оплаты
       <script src = "https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
       <script src = "https://widget.cloudpayments.ru/bundles/cloudpayments?cms=Bitrix24"></script>
       <button class = "cloudpay_button" style = "display:none" id = "payButton"></button>
       <div id = "result" style = "display:none"></div>
       <script type = "text/javascript">
           var payHandler = function () {
               var widget = new cp.CloudPayments({language: '<?=$POST['WIDGET_LANG']?>'});
               widget.charge({ // options
                       publicId: '<?=trim(htmlspecialchars($POST['publicID']));?>',
                       description: 'Счет № <?=htmlspecialchars($POST['PAYMENT_ID'])?> на "<?=$POST['domain']?>"',
                       amount: <?=number_format($POST['Sum'], 2, '.', '')?>,
                       currency: '<?=$POST['currency']?>',
                       email: '<?=$POST['USER_EMAIL']?>',
                       invoiceId: '<?=htmlspecialchars($POST['PAYMENT_ID'])?>',
                       accountId: '<?=htmlspecialchars($POST['customer'])?>',
                       skin: '<?=$POST['skin']?>',
                       data: <?=json_encode($data)?>,
                   },
                   function (options) { // success
                       window.location.href = "https://<?=$POST['domain']?>/pub/payment_result.php?action=success";
                   },
                   function (reason, options) { // fail
                       window.location.href = "https://<?=$POST['domain']?>/pub/payment_result.php?action=fail";
                   });
           };
           $(document).ready(function () {
               $("body").on("click", "#payButton", payHandler);
               $("#payButton").trigger("click");
           });
       </script>
      <?
    }

    public function getPaymentConfig($params_config, $data)///OK
    {
      self::Log("_________NEW222________");
      self::Log($data);
      if(!$data['InvoiceId']):
        json_encode(array("ERROR" => 'InvoiceId error'));
        die();
      endif;

      $params = array(
        "invoice_id" => $data['InvoiceId'],
        "bx_rest_handler" => self::handler_name,
      );
      self::Log('params_config');
      self::Log($params_config);

      $params_config['access_token'] = self::checkAccessToken($params_config['domain'], $params_config['access_token'], $params_config['refresh_token']);
      self::Log($params);
      $res = self::callB24Method($params_config, 'sale.paysystem.settings.invoice.get', $params);
      self::Log('getPaymentConfig');
      self::Log($params);
      self::Log($res);

      if($res['error'] && !empty($res['error'])):
        echo $res['error'];
      else:
        return $res['result'];
      endif;
    } //OK

    public function checkPaymentCP($auth) {
      $payment_list = self::callB24Method($auth, "sale.paysystem.list", array());
      if($payment_list['result']):
        foreach($payment_list['result'] as $payment):
          if($payment['ACTION_FILE'] == self::handler_name)
            return true;
        endforeach;
      endif;

      return false;
    }

    public function getPaymentCP($auth) {
      $payment_list = self::callB24Method($auth, "sale.paysystem.handler.list", array());
      self::Log("getPaymentCP");
      self::Log($payment_list);
      if($payment_list['result']):
        foreach($payment_list['result'] as $payment):
          if($payment['CODE'] == self::handler_name)
            return $payment['ID'];
        endforeach;
      endif;

      return false;
    }

    public function checkInvoiceStatus($REQUEST) {
      /*      if (!empty($REQUEST['data']['FIELDS']['ID']) && !empty($REQUEST['auth']['domain'])):
              $params_config = self::getConfig($REQUEST['auth']['domain']);
              $payment_config = array("PAYMENT_ID" => $REQUEST['data']['FIELDS']['ID']);
              $order = self::get_order($params_config, $payment_config);
              if (self::checkPaymentCP($params_config)):
                self::Log('____checkInvoiceStatus________');
                self::Log($order);
                if ($order['STATUS_ID']):
                  switch ($order['STATUS_ID']):
                    case $payment_config['STATUS_REFUND']:
                      //  self::refund($params_config, $order);
                      break;
                  endswitch;
                endif;
              endif;
            endif;*/
    }

    public function GetCloudPaymentId($auth) {
      if(!$auth['domain']):
        json_encode(array("ERROR" => 'Domen error'));
        die();
      endif;
      $salepaysystemlist = self::callB24Method($auth, "sale.paysystem.list", array());
      if($salepaysystemlist['result']):
        self::Log('GetCloudPaymentId');
        self::Log($salepaysystemlist);
        foreach($salepaysystemlist['result'] as $payment):
          if($payment['ACTION_FILE'] == self::handler_name)
            return $payment['ID'];
        endforeach;
      endif;

      return false;

    }

    public function refund($params_config, $order) {
      self::Log('______refund_______');
      $error = '';
      $request = array(
        'TransactionId' => $order['PAY_VOUCHER_NUM'],
        'Amount' => number_format($order['PRICE'], 2, '.', ''),
      );

      $url = 'https://api.cloudpayments.ru/payments/refund';

      if($params_config['publicID'] && $params_config['SECRET_KEY']) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $params_config['publicID'] . ":" . $params_config['SECRET_KEY']);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        $out = self::Object_to_array(json_decode($content));

        self::Log('______refund_______');
        self::Log($out);

        if($out['Success'] !== false) {
        } else {
          $error .= $out['Message'];
          json_encode(array("ERROR" => $error));
          die();
        }
      }
    }

    public function checkPayment($REQUEST) {
      $data_ = json_decode($REQUEST['Data']);
      if(!$data_->DOMEN):
        json_encode(array("ERROR" => 'domen error'));
        die();
      endif;
      $params_config = self::getConfig($data_->DOMEN);
      self::Log('getConfig');
      self::Log($params_config);
      self::Log('processCheckAction');
      self::Log($data_);

      $PaymentConfig = self::getPaymentConfig($params_config, $REQUEST);

      self::Log('___NEW1111____');
      self::Log($PaymentConfig);
      self::Log($params_config);

      //$params_config = self::getConfig($data_->DOMEN);
      $order = self::get_order($params_config, $PaymentConfig);
      if(!$order['ID']):
        json_encode(array("ERROR" => 'order empty'));
        die();
      endif;

      self::Log('CHECK!!111111');

      if(!$data_->DOMEN):
        self::Log('domain error');
        json_encode(array("ERROR" => 'domain error'));
        die();
      endif;

      self::Log('CHECK!!2222222');

      if(self::CheckHMac($PaymentConfig['SECRET_KEY'])):
        self::Log('333333333333333');
        if(self::isCorrectSum($REQUEST, $order)):
          $data['CODE'] = 0;
          self::Log('CorrectSum');
        else:
          $data['CODE'] = 11;
          $errorMessage = 'Incorrect payment sum';
          self::Log($errorMessage);
          echo json_encode($data);
          die();
        endif;

        self::Log("Проверка заказа");

        if(self::isCorrectOrderID($order, $REQUEST)):
          $data['CODE'] = 0;
        else:
          $data['CODE'] = 10;
          $errorMessage = 'Incorrect order ID';
          self::Log($errorMessage);
          echo json_encode($data);
          die();
        endif;

        if("P" == $order['STATUS_ID']):
          $data['CODE'] = 13;
          $errorMessage = 'Order already paid';
          self::Log($errorMessage);
          echo json_encode($data);
          die();
        else:
          $data['CODE'] = 0;
        endif;

      else:
        $errorMessage = 'ERROR HMAC RECORDS';
        self::Log($errorMessage);
        $data['CODE'] = 5204;
        echo json_encode($data);
        die();
      endif;

      self::Log(json_encode($data));
      echo json_encode($data);
    }

    public function getHandlerBill($auth, $order) {
      $salepaysystemlist = self::callB24Method($auth, "sale.paysystem.list", array());
      self::Log($salepaysystemlist);
      self::Log("__________ORDER______________");
      self::Log($order);
      if($salepaysystemlist['result']):
        foreach($salepaysystemlist['result'] as $payment):
          if($payment['ACTION_FILE'] == 'bill' && $order['PERSON_TYPE_ID'] == $payment['PERSON_TYPE_ID']):
            return $payment['ID'];
          endif;
        endforeach;
      endif;

      return 3;
    }

    public function SuccessAction($REQUEST) {
      $data_ = json_decode($REQUEST['Data']);
      if(!$data_->DOMEN):
        json_encode(array("ERROR" => 'domen error'));
        die();
      endif;
      $params_config = self::getConfig($data_->DOMEN);
      $payment_config = self::getPaymentConfig($params_config, $REQUEST);
      $params_config = self::getConfig($data_->DOMEN);

      $params = array("invoice_id" => $payment_config['PAYMENT_ID'], "bx_rest_handler" => self::handler_name);
      self::Log("SuccessAction");//PAY_SYSTEM_ID
      $res = self::callB24Method($params_config, 'sale.paysystem.pay.invoice', $params);
      self::Log($res);
      $order = self::get_order($params_config, $payment_config);
      $bill_id = self::getHandlerBill($params_config, $order);
      $params = array(
        "id" => $payment_config['PAYMENT_ID'],
        "PAY_VOUCHER_NUM" => $REQUEST['TransactionId'],
        "fields" => array("STATUS_ID" => $payment_config['STATUS_PAY'], "PAY_SYSTEM_ID" => $bill_id)
      );
      $res = self::callB24Method($params_config, 'crm.invoice.update', $params);

      self::Log($res);
      if($res['result']):
        $data['CODE'] = 0;
      else:
        json_encode(array("ERROR" => 'pay error'));
        die();
      endif;

      echo json_encode($data);
    }

    public function RefundAction($REQUEST) {
      $data_ = json_decode($REQUEST['Data']);
      $DOMEN = $data_->DOMEN;
      $params_config = self::getConfig($DOMEN);
      $payment_config = self::getPaymentConfig($params_config, $REQUEST);
      //if (PAY_VOUCHER_NUM)TransactionId
      if(!$REQUEST['TransactionId']):
        json_encode(array("ERROR" => 'TransactionId error'));
        die();
      endif;
      //$params_config = self::getConfig($DOMEN);
      if(self::checkPaymentCP($params_config)):
        $order = self::get_order($params_config, $payment_config);
        $bill_id = self::getHandlerBill($params_config, $order);

        if(!$bill_id):
          json_encode(array("ERROR" => 'handler bill error'));
          die();
        endif;
        self::Log("RefundAction11111");
        self::Log($payment_config);
        $params = array(
          "id" => $REQUEST['InvoiceId'],
          "fields" => array("STATUS_ID" => $payment_config['STATUS_REFUND'], "PAY_SYSTEM_ID" => $bill_id)
        );
        self::Log("RefundAction");
        $res = self::callB24Method($params_config, 'crm.invoice.update', $params);
        self::Log($res);
        if($res['result']):
          $data['CODE'] = 0;
        else:
          json_encode(array("ERROR" => 'Refund error'));
          die();
        endif;
      else:
        json_encode(array("ERROR" => 'Refund error'));
        die();
      endif;
      echo json_encode($data);
    }

    public function get_order($params_config, $payment_config) ///OK
    {
      self::Log("get_order");
      self::Log($payment_config);
      self::Log("end_get_order");
      if($payment_config['PAYMENT_ID'] == 5328)
        $payment_config['PAYMENT_ID'] = 5330;
      $res = self::callB24Method($params_config, 'crm.invoice.get', array("id" => $payment_config['PAYMENT_ID']));
      self::Log($res);
      if(!$res['result']['ID']):
        self::Log('PAYMENT_ID error');
        json_encode(array("ERROR" => 'PAYMENT_ID error'));
        die();
      else:
        return $res['result'];
      endif;
    }

    private function isCorrectSum($request, $order)  ////ok
    {
      $sum = $request['Amount'];
      $paymentSum = $order['PRICE'];
      self::Log("_______FUNCTION isCorrectSum_______");
      self::Log($sum.' = '.$paymentSum);

      return round($paymentSum, 2) == round($sum, 2);
    }

    private function isCorrectOrderID($order, $request)    ///ok
    {
      return $request['InvoiceId'] == $order['ID'];
    }

    private function CheckHMac($pass)   //ok
    {
      $headers = self::detallheaders();
      $this->Log($headers);

      if(isset($headers['Content-HMAC']) || isset($headers['Content-Hmac'])) {
        $this->Log('HMAC_1');
        $this->Log($pass);
        $message = file_get_contents('php://input');
        $s = hash_hmac('sha256', $message, $pass, true);
        $this->Log($message);
        $hmac = base64_encode($s);

        $this->Log($hmac);
        if($headers['Content-HMAC'] == $hmac)
          return true; elseif($headers['Content-Hmac'] == $hmac)
          return true;
      } else return false;
    }

    private function detallheaders()  ///ok
    {
      if(!is_array($_SERVER)) {
        return array();
      }
      $headers = array();
      foreach($_SERVER as $name => $value) {
        if(substr($name, 0, 5) == 'HTTP_') {
          $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
      }
      return $headers;
    }

    function Object_to_array($data) {
      if(is_array($data) || is_object($data)) {
        $result = array();
        foreach($data as $key => $value) {
          $result[$key] = self::Object_to_array($value);
        }
        return $result;
      }
      return $data;
    }

    function pre($str) {
      echo '<pre>';
      print_r($str);
      echo '</pre>';
      die();
    }

  }