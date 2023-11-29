<?php 

$myconf = json_decode(file_get_contents(__DIR__.'/../config.json'));

if(isset($_POST['sylabus_title'])){
    $data = [
        'sylabus_title'=>addslashes($_POST['sylabus_title']),
        'owner_school'=>intval($owner_school['id']), 
        'sylabus_class'=>intval($_POST['sylabus_class']),
        'sylabus_subject'=>intval($_POST['sylabus_subject']),
        'sylabus_content'=>addslashes($_POST['sylabus_content'])
    ];
    $k = $db->insert('eschool_sylabus', $data);
    if(!$db->error() && $k){
        if(isset($_POST['ajax_request'])) die('ok');
        else $msg = 'Sylabus added successful!';
    }
    else{
        $msg = $db->error()['message'];
        if(isset($_POST['ajax_request'])) die($msg);
    }
}
$classes = $db->select('eschool_classes')
                ->where(['owner_school'=>intval($owner_school['id'])])
                ->order_by('class_before', 'asc')
                ->fetchAll();

$subjects = $db->select('eschool_subjects', 'eschool_subjects.*')
                ->where(['owner_school'=>intval($owner_school['id'])])
                ->fetchAll();

$sylabus = $db->select('eschool_sylabus')
              ->join('eschool_classes','sylabus_class=class_id')
              ->join('eschool_subjects','sylabus_subject=subject_id')
              ->where("eschool_sylabus.owner_school={$owner_school['id']}")
              ->fetchAll();


ob_start();
include __DIR__.'/html/sylabus.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];