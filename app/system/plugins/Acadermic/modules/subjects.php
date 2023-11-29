<?php 

if(isset($_POST['subject_name'])){
    $data = [
        'owner_school'=>intval($owner_school['id']),
        'subject_name'=>addslashes($_POST['subject_name']),
        'subject_code'=>addslashes($_POST['subject_code']),
        'subject_type'=>addslashes($_POST['subject_type'])
    ];
    $k = $db->insert('eschool_subjects', $data);
}
if(isset($_POST['delete_subject'])){
    $k = $db->delete('eschool_subjects')
            ->where(['subject_id'=>intval($_POST['delete_subject'])])
            ->and(['owner_school'=>intval($owner_school['id'])])
            ->commit();
    if(!$db->error() && $k){
        if(isset($_POST['ajax_request'])) die('ok');
        else $msg = 'Subject deleted successful!';
    }
    else{
        $msg = $db->error()['message'];
        if(isset($_POST['ajax_request'])) die($msg);
    }
}
if(isset($_POST['group_class'])){
    $data = [
        'owner_school'=>intval($owner_school['id']),
        'group_class'=>addslashes($_POST['group_class']),
        'group_subjects'=>json_encode(array_values($_POST['group_subjects']))
    ];
    $k = $db->insert('eschool_subjects_group', $data);
}
if(isset($_POST['delete_subject_group'])){
    $k = $db->delete('eschool_subjects_group')
            ->where(['group_id'=>intval($_POST['delete_subject_group'])])
            ->and(['owner_school'=>intval($owner_school['id'])])
            ->commit();
    if(!$db->error() && $k){
        if(isset($_POST['ajax_request'])) die('ok');
        else $msg = 'Subject group deleted successful!';
    }
    else{
        $msg = $db->error()['message'];
        if(isset($_POST['ajax_request'])) die($msg);
    }
}
$subjects = $db->select('eschool_subjects', 'eschool_subjects.*')
                ->where(['owner_school'=>intval($owner_school['id'])])
                ->fetchAll();

$groups = $db->select('eschool_subjects_group', 'eschool_subjects_group.*, eschool_classes.*')
                ->join('eschool_classes', 'class_id=group_class')
                ->where(['owner_school'=>intval($owner_school['id'])])
                ->order_by('class_before', 'asc')
                ->fetchAll();

$classes = $db->select('eschool_classes', 'eschool_classes.*, pages.page_name')
                ->where(['owner_school'=>intval($owner_school['id'])])
                ->order_by('class_before', 'asc')
                ->fetchAll();
ob_start();
include __DIR__.'/html/subjects.html';
$body = ob_get_clean();
$return = ['title'=>' ', 'body'=>$body];