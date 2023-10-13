<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);

human_resources::save_department();
if(isset($_POST['delete_department'])){
    $k = $db->delete('departments')->where(['dept_id'=>$_POST['dept_id']])->commit();
    if(!$db->error()) $msg = 'ok';
    else $msg = $db->error()['message'];
    die($msg);
}
$departments = $db->select('departments')
                  ->join('faculties','faculty_id=dept_faculty')
                  ->order_by('faculty_id', 'desc')
                  ->fetchAll();

$d_tree = [];
foreach($departments as $d){
    if(!isset($d_tree[$d['faculty_name']])) $d_tree[$d['faculty_name']] = [];
    $d_tree[$d['faculty_name']][] = $d;
}
$faculties = $db->select('faculties')->fetchAll();
$body = '';
ob_start();
include __DIR__.'/html/departments.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];