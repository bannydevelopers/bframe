<?php 

$myconf = json_decode(file_get_contents(realpath(__DIR__.'/../').'/config.json'));
//$myconf->leave_approve_flow = explode(',',$myconf->leave_approve_flow);
$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);

if(isset($_POST['leave_approve_flow'])){
    $myconf->leave_approve_flow = array_values($_POST['leave_approve_flow']);
    file_put_contents(realpath(__DIR__.'/../').'/config.json', json_encode($myconf, JSON_PRETTY_PRINT));
}
if(isset($_POST['headquarters_branch'])){
    $myconf->headquarters_branch = intval($_POST['headquarters_branch']);
    file_put_contents(realpath(__DIR__.'/../').'/config.json', json_encode($myconf, JSON_PRETTY_PRINT));
}
$branches = $db->select('branches')->fetchAll();
$myroles = $db->select('roles')->where('role_id')->in($myconf->leave_approve_flow)->fetchAll();
$roles = $db->select('roles')->fetchAll();
ob_start();
include __DIR__.'/html/configuration.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];