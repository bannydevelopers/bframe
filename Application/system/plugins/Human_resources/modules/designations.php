<?php

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);

if(isset($_POST['delete_designation'])){
    $k = intval($_POST['delete_designation']);
    $db->delete('designations')->where(['designation_id'=>$k])->commit();
    $chk = $db->select('designations')->where(['designation_id'=>$k])->limit(1)->fetch();

    if($db->error()) $msg = $db->error()['message'];
    else {
        $msg = $chk ? 'Delete failed' : 'Deleted successful';
    }
    if(isset($_POST['ajax_request'])) die($msg);
}

$designations = $db->select('designations')->fetchAll();

ob_start();
include __DIR__.'/html/designations.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];