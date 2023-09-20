<?php

$start_time = microtime(true);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Dar_es_Salaam'); 

// Class autoload register
spl_autoload_register(
    function($class_name){
        $path = realpath(__DIR__.'/Application/system/helper_classes/');
        $fn = "{$class_name}_class.php";
        if(is_readable("{$path}/{$fn}")){
            include "{$path}/{$fn}";
        }
        else{
            // to be continued
        }
    }
);
//var_dump('<pre>',system::get_system_configuration());
$storage = storage::initialize();
$storage->start_time = $start_time;
$storage->system_config = system::get_system_configuration();
$document_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$install_dir = str_replace('\\', '/', realpath(__DIR__));

$request = str_replace($document_root, '', $install_dir);
$storage->install_dir = trim($request, '/');

if(empty(trim($request, '/'))) $storage->install_dir = '/';
else $storage->install_dir = "{$request}/";

// To do...
$no_string = strtok($_SERVER['REQUEST_URI'], '?');

$no_string = str_replace($storage->install_dir, '', $no_string);
$storage->request = explode('/', trim($no_string, '/'));

//var_dump($storage->request);

system::start_session();

//Sychronize plugins
system::sync_plugins();

// check dashboard request
if($storage->request[0] == $storage->system_config->dashboard_url){
    admin::load_dashboard();
    exit;
}
//Dispatch system initialization event
system::dispatch_event('initialize_system');

// prepare page
system::load_page();

//Templating
system::dispatch_event('templating');
//template::create_meta();

system::display();
