<?php
class system{
    public static function dispatch_event($event_name, $details = []){
        $events = storage::get_data('system_events', $event_name);
        
        $return = [];
        if(isset($events[$event_name]) && is_array($events[$event_name])){
            foreach($events[$event_name] as $event){
                $scope = explode('::', $event[0]);
                if(method_exists(...$scope)) {
                    $contents = call_user_func([...$scope], $details);
                    if($contents) $return[] = $contents;
                }
            }
        }
        $return = array_unique($return, SORT_REGULAR); // remove duplicates
        return $return;
    }
    public static function add_event_listener($event_name, $callback, $data = null){
        $events = storage::get_data('system_events');
        if(!isset($events[$event_name])) $events[$event_name] = [];
        $hash = md5($callback);
        $events[$event_name][$hash] = [$callback, $data];
        storage::save_data('system_events', $events);
    }
    public static function sync_plugins(){
        $conf = self::get_system_config();
        if(!isset($conf->plugins) or !is_array($conf->plugins)) return;
        if(is_object($conf) or is_array($conf)){
            $conf = (object) $conf;
            $system_dir = SYSTEM_DIR;
            $root = realpath(__DIR__)."/{$system_dir}/plugins/";
            foreach($conf->plugins as $plugin){
                $class = strtolower($plugin);
                //$obj = $class::init();
                if(method_exists($class, 'init')) call_user_func( [$class, 'init']);
            }
        }
        //else die(gettype($conf));
    }
    public static function translate($index){
        return $index;
    }
    public static function load_page(){
        $req = storage::init()->request;
        $db = db::get_connection(storage::get_instance()->system_config->db_configs);
        if(empty(implode('', $req))) {
            $page = $db->select('pages')->where(['page_special'=>1])->or(['page_special'=>3])->limit(1)->fetch();
        }
        else{
            $parents = $req;
            if(count($parents) > 1){
                // Flip to reverse order
                $pages = array_reverse($parents);
                $qry = "SELECT * FROM pages WHERE page_name='{$pages[0]}'";
                foreach($pages as $k=>$pg){
                    if($k == 0) continue;
                    $qry .= " AND page_parent IN (SELECT page_id FROM pages WHERE page_name = '{$pg}'";
                }
                $qry .= str_repeat(')', count($pages)-1);
                $page = $db->query(str_replace('-', ' ', $qry))->fetch();
            }
            else{
                $pname = str_replace('-', ' ', implode('',$req));
                $page = $db->select('pages')->where(['page_name'=>$pname, 'page_parent'=>0])->limit(1)->fetch();
            }
        }
        if(!$page){
            $page = [
                'page_id'=>0, 
                'page_title'=>self::translate('request_not_found_title'), 
                'page_name'=>'404', 
                'page_content'=>self::translate('request_not_found_desc'), 
                'page_desc'=>'', 
                'page_keywords'=>'', 
                'page_author'=>0, 
                'create_date'=>0, 
                'page_special'=>0, 
                'page_template'=>'404', 
                'page_extras'=>null
            ];
        }
        storage::save_data('page', $page);
    }
    public static function secure_session_start($regenerate_id = false){
        session_start();
        return; // Not well implemented
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        $SessionHandler = new session();
        session_set_save_handler($SessionHandler, true);
    }
    public static function get_session(){
        $conf = storage::get_data('system_config');
        return isset($_SESSION[$conf->session_name]) ? $_SESSION[$conf->session_name] : null;
    }
    public static function get_system_config($return_array = false){
        $config_path = realpath(__DIR__.'/../').'/secure/config.json';
        $stringJSON = file_get_contents($config_path);
        $conf_arr = json_decode($stringJSON, $return_array);
        return $conf_arr;
    }
    public static function get_countries($return_array = false){
        $config_path = realpath(__DIR__.'/../').'/secure/countries.json';
        $stringJSON = file_get_contents($config_path);
        return json_decode($stringJSON, $return_array);

    }
    public static function get_country($code){
        $countries = self::get_countries(true);
        $index = array_search($code, array_column($countries,'code'));
        if(intval($index)) return $countries[$index]['name'];
        else return $code;
    }
    public static function init_gateway($name){
        $conf =  self::get_system_config();
        $gateway = $conf->gateways->{$name};
        if(!isset($gateway)) return null; // unknown gateway
        $root = realpath(__DIR__.'/../gateways');
        if(!is_dir("{$root}/{$name}/{$gateway}")) return false; // gateway not available
        include_once "{$root}/{$name}/{$gateway}/index.php";
        
        return is_callable([$gateway, 'init']) ? call_user_func( [$gateway, 'init'], $conf) : 0; //invalid gateway
    }
    public static function config_write(array $data){
        $path = realpath(__DIR__.'/../');
        if(isset($data['plugins'])) $data['plugins'] = array_values($data['plugins']);
        $jsonString = json_encode($data, JSON_PRETTY_PRINT);
        // Write in the file
        $fp = fopen("$path/secure/config.json", 'w');
        fwrite($fp, $jsonString);
        fclose($fp);
        storage::get_instance()->system_config = $data;
    }
    public static function create_hash($plain_txt){
        $plain_txt = md5(str_rot13($plain_txt));
        $ret = hash('sha512', md5($plain_txt));
        return md5(str_rot13($ret));
    }
    public static function format_phone($phone_number){
        // Allow +, - and . in phone number
        $filtered_phone_number = filter_var($phone_number, FILTER_SANITIZE_NUMBER_INT);
        // Remove "-" from number
        $phone_to_check = str_replace("-", "", $filtered_phone_number);
        
        // Check the lenght of number
        // This can be customized if you want phone number from a specific country
        if (strlen(trim($phone_to_check,'+')) < 10 || strlen($phone_to_check) > 14){
            return $phone_number;
        } 
        else{
            if($phone_to_check[0] == 0) $phone_to_check = '255'.substr($phone_to_check, 1);
            return ltrim($phone_to_check, '+');
        }
    }
    public static function format_time($datetime, $selected=NULL){
        if(!$datetime) return $datetime;// passing null to strtotime is deprecated, hence bail
        if($selected == NULL) $selected = 0;
        $ts = strtotime($datetime); 
        $formats = array('l, m d, Y', //0
                         'l, M d, Y', //1
                         'D, m d, Y', //2
                         'D, M d, Y');//3
        if(date($formats[3]) == date($formats[$selected], $ts)) return 'Today';
        if(!isset($formats[$selected])) $formats[$selected] = $formats[0];
        if(date('H:i:s', $ts) != '00:00:00') $formats[$selected] .= ' @ H:i';
        return date($formats[$selected], $ts);
    }
    public static function format_email($email){
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    public static function send_email($opts){
        $config = storage::init()->system_config;

        $opts['headers']  = "From: " . strip_tags($opts['from']) . "\r\n";
                
        //$opts['headers'] .= "CC: susan@example.com\r\n";
        if(isset($opts['replyTo'])) {
            if(is_array($opts['replyTo'])) $opts['headers'] .= "Reply-To: " . $opts['replyTo'][0] . "\r\n";
            else $opts['headers'] .= "Reply-To: " . $opts['replyTo'] . "\r\n";
        }
        $opts['headers'] .= "MIME-Version: 1.0\r\n";
        $opts['headers'] .= "Content-Type: text/html; charset=UTF-8\r\n";

        if(isset($config->phpmailer)){
            //use PHPMailer\PHPMailer\PHPMailer;
            //use PHPMailer\PHPMailer\Exception;
            require realpath(__DIR__.'/../../../').'/vendor/phpmailer/phpmailer/src/Exception.php';
            require realpath(__DIR__.'/../../../').'/vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require realpath(__DIR__.'/../../../').'/vendor/phpmailer/phpmailer/src/SMTP.php';

            $phpmailer = new PHPMailer\PHPMailer\PHPMailer();
            $phpmailer->isSMTP();
            $phpmailer->isHTML();
            $phpmailer->Host = $config->phpmailer->host;
            $phpmailer->SMTPAuth = true;
            $phpmailer->Port = 25;
            $phpmailer->Username = $config->phpmailer->username;
            $phpmailer->Password = $config->phpmailer->password;
            $phpmailer->SMTPSecure = 'tls';

            if(is_array($opts['to'])) $phpmailer->addAddress(...$opts['to']);
            else  $phpmailer->addAddress($opts['to']);

            $phpmailer->setFrom($opts['from'], $config->site_name);

            if(isset($opts['replyTo'])) {
                if(is_array($opts['replyTo'])) $phpmailer->addReplyTo(...$opts['replyTo']);
                else $phpmailer->addReplyTo($opts['replyTo']);
            }
            if(isset($opts['cc'])) {
                if(is_array($opts['cc'])) $phpmailer->addReplyTo(...$opts['cc']);
                else $phpmailer->addReplyTo($opts['cc']);
            }
            if(isset($opts['bcc'])) {
                if(is_array($opts['bcc'])) $phpmailer->addReplyTo(...$opts['bcc']);
                else $phpmailer->addReplyTo($opts['bcc']);
            }
            
            $phpmailer->Subject = $opts['subject'];

            $phpmailer->isHTML(true);

            $phpmailer->Body = strip_tags($opts['message']);
            $phpmailer->MsgHTML($opts['message']);

            if(isset($opts['attachment'])) $phpmailer->addAttachment($opts['attachment']);
            //$phpmailer->msgHTML($content, $dir);
            if($phpmailer->send()){
                return true;
            }
            else{
                storage::init()->mail_error = $phpmailer->ErrorInfo;
                return mail($opts['to'], $opts['subject'], $opts['message'], $opts['headers']);
            }
        }
        else{
            storage::init()->mail_error = 'Mailer config not found';
            return mail($opts['to'], $opts['subject'], $opts['message'], $opts['headers']);
        }
    }
    public static function clear_cache($target = null){
        $root = realpath(__DIR__.'/../');
        $root = "{$root}/cache";
        if(!is_readable($root)) mkdir($root);
        $files = [];
        if($target && is_writable("{$root}/{$target}")){
            $tmp = scandir("{$root}/{$target}");
            foreach($files as $f){
                if(is_file("{$root}/{$target}/$f")) unlink("{$root}/{$target}/$f");
            }
        }
        else{
            $cdir = scandir($root);
            foreach($cdir as $target){
                $files = scandir("{$root}/{$target}");
                foreach($files as $f){
                    if(is_file("{$root}/{$target}/$f")) unlink("{$root}/{$target}/$f");
                }
            }
        }
    }
    public static function cache_clear($target = null){
        return self::clear_cache($target);
    }
    /*
    private function resize_imagejpg($file, $w, $h, $finaldst) {

        list($width, $height) = getimagesize($file);
        $src = imagecreatefromjpeg($file);
        $ir = $width/$height;
        $fir = $w/$h;
        if($ir >= $fir){
            $newheight = $h; 
            $newwidth = $w * ($width / $height);
        }
        else {
            $newheight = $w / ($width/$height);
            $newwidth = $w;
        }   
        $xcor = 0 - ($newwidth - $w) / 2;
        $ycor = 0 - ($newheight - $h) / 2;
     
     
        $dst = imagecreatetruecolor($w, $h);
        imagecopyresampled($dst, $src, $xcor, $ycor, 0, 0, $newwidth, $newheight, 
        $width, $height);
        imagejpeg($dst, $finaldst);
        imagedestroy($dst);
        return $file;
    }
     
    private function resize_imagegif($file, $w, $h, $finaldst) {
     
        list($width, $height) = getimagesize($file);
        $src = imagecreatefromgif($file);
        $ir = $width/$height;
        $fir = $w/$h;
        if($ir >= $fir){
            $newheight = $h; 
            $newwidth = $w * ($width / $height);
        }
        else {
            $newheight = $w / ($width/$height);
            $newwidth = $w;
        }   
        $xcor = 0 - ($newwidth - $w) / 2;
        $ycor = 0 - ($newheight - $h) / 2;
     
        $dst = imagecreatetruecolor($w, $h);
        $background = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagecolortransparent($dst, $background);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, $xcor, $ycor, 0, 0, $newwidth, $newheight, 
        $width, $height);
        imagegif($dst, $finaldst);
        imagedestroy($dst);
        return $file;
    }
     
     
     
     private function resize_imagepng($file, $w, $h, $finaldst) {
     
        list($width, $height) = getimagesize($file);
        $src = imagecreatefrompng($file);
        $ir = $width/$height;
        $fir = $w/$h;
        if($ir >= $fir){
            $newheight = $h; 
            $newwidth = $w * ($width / $height);
        }
        else {
             $newheight = $w / ($width/$height);
        $newwidth = $w;
        }   
        $xcor = 0 - ($newwidth - $w) / 2;
        $ycor = 0 - ($newheight - $h) / 2;
     
     
        $dst = imagecreatetruecolor($w, $h);
        $background = imagecolorallocate($dst, 0, 0, 0);
        imagecolortransparent($dst, $background);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
     
        imagecopyresampled($dst, $src, $xcor, $ycor, 0, 0, $newwidth, 
        $newheight,$width, $height);
     
        imagepng($dst, $finaldst);
        imagedestroy($dst);
        return $file;
     }
     
     public function upload_photo($file, $w, $h, $finaldst) {
        $_this = new static();
        $getsize = getimagesize($file);
        $image_type = $getsize[2];
    
        if( $image_type == IMAGETYPE_JPEG) {
            return $_this->resize_imagejpg($file, $w, $h, $finaldst);
        } 
        elseif( $image_type == IMAGETYPE_GIF ) {
    
            return $_this->resize_imagegif($file, $w, $h, $finaldst);
        } 
        elseif( $image_type == IMAGETYPE_PNG ) {
    
            return $_this->resize_imagepng($file, $w, $h, $finaldst);
        }
     }
     */
    public static function upload_image($source, $destination, $sizes = ['width'=>600, 'height'=>300]){
        return self::image_upload_resize($source, $destination, $sizes['width'], $sizes['height']);
    }
    public static function image_upload_resize($src, $dst, $width, $height, $crop=true){
        $sizes = getimagesize($src);
        //return $sizes;
        //if(!list($w, $h) = getimagesize($src)) return "Unsupported picture type!";
        $w = $sizes[0];
        $h = $sizes[1];
        $type = substr($sizes['mime'], strpos($sizes['mime'], '/')+1);
        //return $type;
        //$type = strtolower(substr(strrchr($src,"."), 1));
        if($type == 'jpeg') $type = 'jpg';
        switch($type){
          case 'bmp': $img = imagecreatefromwbmp($src); break;
          case 'gif': $img = imagecreatefromgif($src); break;
          case 'jpg': $img = imagecreatefromjpeg($src); break;
          case 'png': $img = imagecreatefrompng($src); break;
          default : return "Unsupported picture type {$type}!";
        }
      
        // resize
        if($crop){
          //if($w < $width or $h < $height) return "Picture is too small!";
          $ratio = max($width/$w, $height/$h);
          $h = intval($height / $ratio);
          $x = ($w - $width / $ratio) / 2;
          $w = intval($width / $ratio);
        }
        else{
          //if($w < $width and $h < $height) return "Picture is too small!";
          $ratio = min($width/$w, $height/$h);
          $width = intval($w * $ratio);
          $height = intval($h * $ratio);
          $x = 0;
        }
      
        $new = imagecreatetruecolor($width, $height);
      
        // preserve transparency
        if($type == "gif" or $type == "png"){
          imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
          imagealphablending($new, false);
          imagesavealpha($new, true);
        }
      // I'll fix it some day
        @imagecopyresampled($new, $img, 0, 0, (int)$x, 0, (int)$width, (int)$height, (int)$w, (int)$h);
        $dest_type = explode('.', $dst);
        $ext = end($dest_type);
        switch($ext){
          case 'jpg': imagejpeg($new, $dst); break;
          case 'jpeg': imagejpeg($new, $dst); break;
          case 'gif': imagegif($new, $dst); break;
          case 'png': imagepng($new, $dst); break;
          case 'bmp': imagewbmp($new, $dst); break;
        }
        imagedestroy($new);
        unlink($src);
        return $dst;
    }
}