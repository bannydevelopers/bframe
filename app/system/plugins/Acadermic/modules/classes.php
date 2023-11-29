<?php
if(isset($_POST['class_name'])){
    $cls = json_encode(@$_POST['class_subjects']);
    if(!$cls) $cls = [];
    $data = [
        'class_name'=>addslashes($_POST['class_name']),
        'owner_school'=>intval($owner_school['id']),
        'class_subjects'=>$cls,
        'class_before'=>intval(@$_POST['class_before'])
    ];
    $k = $db->insert('eschool_classes', $data);
    if(!$db->error() && $k){
        if(isset($_POST['ajax_request'])) die('ok');
        else $msg = 'Class added successful!';
    }
    else{
        if(isset($_POST['ajax_request'])) die($db->error()['message']);
        else $msg = $db->error()['message'];
    }
}
if(isset($_POST['delete_class'])){
    $k = $db->delete('eschool_classes')
            ->where(['class_id'=>intval($_POST['delete_class'])])
            ->and(['owner_school'=>intval($owner_school['id'])])
            ->commit();
    if(!$db->error() && $k){
        if(isset($_POST['ajax_request'])) die('ok');
        else $msg = 'Class deleted successful!';
    }
    else{
        $msg = $db->error()['message'];
        if(isset($_POST['ajax_request'])) die($msg);
    }
}
if(isset($_POST['section_name'])){
    $data = [
        'section_name'=>addslashes($_POST['section_name']),
        'owner_school'=>intval($owner_school['id'])
    ];
    $k = $db->insert('eschool_sections', $data);
    if(!$db->error() && $k){
        if(isset($_POST['ajax_request'])) die('ok');
        else $msg = 'Section added successful!';
    }
    else{
        $msg = $db->error()['message'];
        if(isset($_POST['ajax_request'])) die($msg);
    }
}
if(isset($_POST['delete_section'])){
    $k = $db->delete('eschool_sections')
            ->where(['section_id'=>intval($_POST['delete_section'])])
            ->and(['owner_school'=>intval($owner_school['id'])])
            ->commit();
    if(!$db->error() && $k){
        if(isset($_POST['ajax_request'])) die('ok');
        else $msg = 'Section deleted successful!';
    }
    else{
        $msg = $db->error()['message'];
        if(isset($_POST['ajax_request'])) die($msg);
    }
}
$classes = $db->select('eschool_classes')
                ->join('pages', 'page_id=owner_school', 'LEFT')
                ->where(['owner_school'=>$owner_school['id']])
                ->order_by('class_before', 'asc')
                ->fetchAll();

$subjects = $db->select('eschool_subjects', 'eschool_subjects.*')
                ->where(['owner_school'=>$owner_school['id']])
                ->fetchAll();
$sections = $db->select('eschool_sections')
                //->join('pages', 'page_id=owner_school')
                ->where(['owner_school'=>$owner_school['id']])
                ->fetchAll();


ob_start();
include __DIR__.'/html/classes.html';
$body = ob_get_clean();
$return = ['title'=>' ', 'body'=>$body];