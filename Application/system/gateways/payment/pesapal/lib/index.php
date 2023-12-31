<?php
class pesapal{
  
  protected static $conf;
  private static $instance = null;
    
  private function __construct(){
      // Made it private to prevent multiple instances
  }

  public static function init($conf){
    self::$conf = $conf;
    if(self::$instance == null) self::$instance = new static();
    return self::$instance;
  }
  public function send_request($post_data){
    $conf = $this::$conf;
    //var_dump($post_data);die;
    include __DIR__.'/lib/pesapalV30Helper.php';
    $consumer_key = $conf->pesapal->consumer_key;
    $consumer_secret = $conf->pesapal->consumer_secret;

    $gateway = new pesapalV30Helper();

    $token = $gateway->getAccessToken($consumer_key, $consumer_secret);
   if(!isset($token->token)) return ['status'=>'error', 'message'=>'Failed to get access; probably network error'];
    $access_token = $token->token;

    //$token_ok = $gateway->validateKeys($access_token, $consumer_key, $consumer_secret);
    $callback = "https://{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}";
    $ipn_id = $gateway->generateNotificationId("{$callback}/?ipn=1", $access_token);
    //print_r($next);
    //$ipn = $gateway->getRegisteredIpn($access_token);
    //print_r($ipn);
    $request = new stdClass();

    $request->currency = $conf->currency;
    $request->amount = $post_data['order_amount'];
    $request->pesapalMerchantReference = $post_data['order_reference'];
    $request->pesapalDescription = $post_data['order_description'];
    $request->account_number = '';
    $request->app_id = 0;
    $request->billing_phone = $post_data['phone'];
    $request->billing_email = $post_data['email'];
    $request->billing_country = $post_data['country'];
    $request->billing_first_name = $post_data['first_name'];
    $request->billing_last_name = $post_data['last_name'];
    $request->billing_address_1 = isset($post_data['address1']) ? $post_data['address1'] : '';
    $request->billing_address_2 = isset($post_data['address2']) ? $post_data['address2'] : '';
    $request->billing_city = isset($post_data['city']) ? $post_data['city'] : '';
    $request->billing_state = $post_data['country'];
    $request->billing_postcode = isset($post_data['postcode']) ? $post_data['postcode'] : '';
    $request->callback_url = "{$callback}?pay_order=1";
    $request->notification_id = $ipn_id;
    $order = $gateway->getMerchertOrderURL($request, $access_token);

    if(isset($order->redirect_url)) return '<iframe src="'.$order->redirect_url.'"></iframe>';
    else return ['status', 'message'=>'Error processing request', 'details'=>$order];
  }
}