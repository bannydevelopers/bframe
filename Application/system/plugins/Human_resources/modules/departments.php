<?php

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);

if(isset($_POST['delete_department'])){
    $k = intval($_POST['delete_department']);
    $db->delete('departments')->where(['dept_id'=>$k])->commit();
    $chk = $db->select('departments')->where(['dept_id'=>$k])->limit(1)->fetch();

    if($db->error()) $msg = $db->error()['message'];
    else {
        $msg = $chk ? 'Delete failed' : 'Deleted successful';
    }
    if(isset($_POST['ajax_request'])) die($msg);
}
$departments = $db->select('departments')->fetchAll();

ob_start();
include __DIR__.'/html/departments.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];