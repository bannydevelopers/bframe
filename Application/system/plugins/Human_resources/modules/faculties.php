<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);

if(isset($_POST['faculty_name'])){
    $fd = [
        'faculty_name'=>addslashes($_POST['faculty_name']),
        'faculty_desc'=>addslashes($_POST['faculty_desc'])
    ];
    if(isset($_POST['faculty_id']) && intval($_POST['faculty_id'])) {
        $id = intval($_POST['faculty_id']);
        $k = $db->update('faculties', $fd)->where("faculty_id={$id}")->commit();
    }
    else{
        $k = $db->insert('faculties', $fd);
    }
    if($db->error()) var_dump($db->error());
    else header("Location: {$_SERVER['REQUEST_URI']}");
}
if(isset($_POST['delete_faculty'])){
    $k = $db->delete('faculties')->where(['faculty_id'=>$_POST['faculty_id']])->commit();
    if(!$db->error()) $msg = 'ok';
    else $msg = $db->error()['message'];
    die($msg);
}
$faculties = $db->select('faculties')
                ->order_by('faculty_id', 'desc')
                ->fetchAll();


$body = '';
ob_start();
include __DIR__.'/html/faculties.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];