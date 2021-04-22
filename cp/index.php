<?php
  include($_SERVER['DOCUMENT_ROOT'] . '/api/cp/class/cloudpayments_cp.php');
  //print_r($_REQUEST);die();
  $debug = true;

  $cloudpayments_cp = new cloudpayments_cp();
  if ($debug):
   // self::Log('лллллллллллл');
    $cloudpayments_cp->Log($_REQUEST);
    $cloudpayments_cp->Log($_POST);
  endif;

  /*  ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);*/
  if($_REQUEST['code']):
    $cloudpayments_cp->Log('_______authorize______');
    $cloudpayments_cp->setAuthorize($_REQUEST['domain'], $_REQUEST['code']);
  endif;


  if ($_REQUEST['event']):
    //include($_SERVER['DOCUMENT_ROOT'] . "/api/cp/view/template/main.php");

    /*
        $auth = array(
          'access_token' => $_REQUEST['auth']['access_token'],
          'expires_in' => $_REQUEST['auth']['expires_in'],
          //'scope' => 'user,bizproc',
          //'user_id' => 'member_id',
          'status' => $_REQUEST['auth']['status'],
          'member_id' => $_REQUEST['auth']['member_id'],
          'domain' => $_REQUEST['auth']['domain'],
        );*/
    if ($_REQUEST['event']):
      $cloudpayments_cp->Log('лллллллллллл');
      switch ($_REQUEST['event']):
        case 'authorize':
          $cloudpayments_cp->Log('authorize');
          $cloudpayments_cp->setAuthorize($_REQUEST['domain'], $_REQUEST['code']);
          break;

        case 'ONAPPUNINSTALL':
          $cloudpayments_cp->Log('ONAPPUNINSTALL');
          break;

        case 'ONAPPINSTALL':
          $cloudpayments_cp->Log('ONAPPINSTALL');
          $auth = $_REQUEST['auth'];
          $auth['access_token'] = $cloudpayments_cp->checkAccessToken($auth['domain'], $auth['access_token'], $auth['refresh_token']);
          if ($PAY_ID = $cloudpayments_cp->checkInstall($auth)):
            $cloudpayments_cp->uninstall($auth, $PAY_ID);
            $cloudpayments_cp->install($auth);
          else:
            $cloudpayments_cp->Log('ONAPPINSTALL222');
            $cloudpayments_cp->install($_REQUEST['auth']);
          endif;
          break;

        case 'ONAPPUPDATE':
          break;

        case 'widjet':
          //$cloudpayments_cp->getAuthorize($_REQUEST['domain']);
          $cloudpayments_cp->Log($_REQUEST);
          $auth = $cloudpayments_cp->getConfig($_REQUEST['domain']);
          $cloudpayments_cp->Log($auth);
          //$auth['access_token'] = $cloudpayments_cp->checkAccessToken($_REQUEST['domain'], $auth['access_token'], $auth['refresh_token']);
          $cloudpayments_cp->getPaymentForm($auth, $_REQUEST);
          break;

        case 'ONCRMINVOICESETSTATUS':
          if ($_REQUEST['data']['FIELDS']['ID']):
            $cloudpayments_cp->checkInvoiceStatus($_REQUEST);
          endif;
          break;

        case 'cp_payment':
          switch ($_REQUEST['action']):
            case 'pay':
              $cloudpayments_cp->Log("----PAY----");
              $cloudpayments_cp->SuccessAction($_REQUEST);
              break;

            case 'check':
              $cloudpayments_cp->Log("----CHECK----");
              $cloudpayments_cp->checkPayment($_REQUEST);
              break;

            case 'fail':
              $cloudpayments_cp->Log("----FAIL----");
              $cloudpayments_cp->RefundAction($_REQUEST);
              break;

            case 'refund':
              $cloudpayments_cp->Log("----REFUND----");
              $cloudpayments_cp->RefundAction($_REQUEST);
              break;
          endswitch;
          break;

      endswitch;
    endif;

  /*    echo '<pre>';
      print_r($paysystem_handler_list);
      echo '</pre>';
      echo $install;*/


  else:
    die();
   // UfKvWH0CKD8hPuDNHMTyeBVaLdeA9GWvaQtQcq3VZXhbULs9yR
/*    $domain = "https://b24-mx29zg.bitrix24.ru";
    $url = $domain . "/oauth/authorize/?client_id=local.5c0a51777d31a0.70800496&response_type=code&redirect_uri=https://bitrix24.cloudpayments.ru/api/cp/index.php?event=authorize&domain=" . $domain;
   // $url.'<br>';
    $application_token = "58731b5c003185dc003153fa0000000b100e031e26bf0c890493ae1a1b2b5cd8567bbf";
    $url = $domain . "/oauth/token/?client_id=local.5c0a51777d31a0.70800496&grant_type=authorization_code&client_secret=UfKvWH0CKD8hPuDNHMTyeBVaLdeA9GWvaQtQcq3VZXhbULs9yR&redirect_uri=https://bitrix24.cloudpayments.ru/api/cp/index.php?event=authorize&code=".$application_token."&scope=pay_system,crm";
   // echo $url;

    $access_token = "5f811b5c003185dc003153fa0000000b100e038730757a54b35708a120cf721084f559";
*/
    $auth['domain'] = "b24-fqomx7.bitrix24.ru";
    $auth['access_token'] = "995d43600052c8380052c836000..............853f1fa2d50728503692ca334";
   // $auth = array("access_token" => $access_token, "domain" => $domain, "refresh_token" => $refresh_token);
    $params = array();
    $res = $cloudpayments_cp->callB24Method($auth, "crm.invoice.get", array("id"=>2));
   // $res = $cloudpayments_cp->callB24Method($auth, "crm.invoice.list", array());
    echo '<pre>';
    print_r($res);
    echo '</pre>';

    $res = $cloudpayments_cp->callB24Method($auth, "crm.invoice.get", array("id"=>8));


    /*        $auth['domain'] = "b24-mx29zg.bitrix24.ru";*/
   // $auth['domain'] = "b24-w7qjde.bitrix24.ru";
/*        $auth = $cloudpayments_cp->getConfig($auth['domain']);
      $res = $cloudpayments_cp->callB24Method($auth, "crm.invoice.get", array("id"=>8));*/
     // $res = $cloudpayments_cp->callB24Method($auth, "crm.invoice.update", array("id"=>42,"fields" => array("STATUS_ID" => "D")));//sale.paysystem.list sale.paysystem.handler.list
/*        echo '1';
        $cloudpayments_cp->pre($res);*/

    //$res = $cloudpayments_cp->callB24Method($auth, "sale.paysystem.handler.delete", array("id" => 76));
    //print_r($res);
  endif;
?>