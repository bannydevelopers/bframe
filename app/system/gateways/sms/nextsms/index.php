<?php
class nextsms{
  
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
  public function send_request($opts){
    $conf = $this::$conf;
    $curl = curl_init();
    $config = storage::init()->system_config->nextsms;
    $reference = md5(microtime(true));
    if(!is_array($opts['recipients'])) $opts['recipients'] = [$opts['recipients']];
    $reqPayload = [
        "from" => $config->sender,
        "to" => $opts['recipients'],
        "text" => $opts['message'],
        "reference" => $reference,
    ];

    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://messaging-service.co.tz/api/sms/v1/text/single',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($reqPayload),
    CURLOPT_HTTPHEADER => array(
        "Authorization: Basic {$config->token}",
        'Content-Type: application/json',
        'Accept: application/json'
    ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $res = json_decode($response);
    //var_dump($res);
    if(isset($res->messages) && isset($res->messages->to) && isset($res->messages->to->id)){
      return ['status'=>'ok', 'message'=>'Message sent', 'details'=>$res];
    }
    else {
      return ['status'=>'fail', 'message'=>'Error processing request', 'details'=>$res];
    }
  }
}