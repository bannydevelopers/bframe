<?php 

// System time zone
date_default_timezone_set('Africa/Dar_es_Salaam');

// Some vars
define('SYSTEM_DIR','system');
define('STORAGE_DIR','storage');

// Setup auto load
spl_autoload_register(
    function($classname){
        $system_dir = SYSTEM_DIR;
        $filename = strtolower($classname).'_class';
        $path = realpath(__DIR__)."/$system_dir/libs/$filename.php";
        if(is_readable($path)) include $path;
        else{
            // Discover active plugins from config
            $conf = storage::get_data('system_config');
            $cls = ucwords(strtolower($classname));
            if(is_array($conf->plugins) && in_array($cls, $conf->plugins)){
                $path = realpath(__DIR__)."/$system_dir/plugins/$cls/$filename.php";
                if(is_readable($path)) include $path;
            }
        }
        if(!class_exists($classname)){
            $err = storage::init()->error;
            if(is_array($err)) $err = [];
            $err[] = "Class '{$classname}' does not exist";
            storage::init()->error = $err;
            $newClass = new class{
                public function __call($name, $arguments)
                {
                    // Note: value of $name is case sensitive.
                }

                /**  As of PHP 5.3.0  */
                public static function __callStatic($name, $arguments)
                {
                    // Note: value of $name is case sensitive.
                }
            }; //create an anonymous class
            $newClassName = get_class($newClass); //get the name PHP assigns the anonymous class
            class_alias($newClassName, $classname);
        }
    },
    true
);

// System configs
$storage = storage::get_instance();
$storage->system_config = system::get_system_config();
unset($conf_data); // Reclaim memmory

// Replace \ with / to standardize
$docroot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$install_dir = str_replace('\\', '/', realpath(__DIR__.'/../'));

$request = str_replace($docroot, '', $install_dir); // Find real requested url
$storage->install_dir = trim($request, '/');
if(empty(trim($request, '/'))) $storage->install_dir = '/';
else $storage->install_dir = "{$request}/";

// Reduce request to our file structure
$req = str_replace($request, '', strtok($_SERVER['REQUEST_URI'], '?')); 

$req_parts = explode('/', trim($req,'/'));
$storage->request = $req_parts;
$config = $storage->system_config;

// Session securely starting
system::secure_session_start();

// Sync plugins
system::sync_plugins();

// Responding to ajax requests
if(isset($_REQUEST['ajax_event']) && $_REQUEST['ajax_event']){
    $response = system::dispatch_event($_REQUEST['ajax_event'], $_REQUEST);
    die(json_encode(['event'=>$_REQUEST['ajax_event'], 'response'=>$response]));
}
// Check admin request
if(isset($storage->request[0]) && $storage->request[0] == $storage->system_config->dashboard_url){
    // load admin
    admin::load_dashboard($req_parts);
    die;
}

// Dispatch system init event
system::dispatch_event('system_init');

// Prepare page if not taken care
system::load_page();

// Dispatch templating event
system::dispatch_event('templating');

// Init template meta
template::create_meta(false); // false = do not overwrite existing meta

// Dispatch output event
system::dispatch_event('exec_end');

// Output
template::display();