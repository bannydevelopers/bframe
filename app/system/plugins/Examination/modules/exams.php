<?php
$status = 'error';
if(isset($_POST['exam_name'])){
    $data = [
        'exam_name'=>addslashes($_POST['exam_name']),
        'exam_desc'=>addslashes($_POST['exam_desc']),
        'owner_school'=>intval($owner_school['id'])
    ];
    if(isset($_POST['exam_id'])){
        $k = $_POST['exam_id'];
        $db->update('eschool_exams', $data)->where(['exam_id'=>intval($_POST['exam_id'])])->commit();
    }
    else{
        $k = $db->insert('eschool_exams', $data);
    }
    if(!$db->error() && $k){
        $msg = 'Saved successful!';
        $status = 'success';
    }
    else{
        $msg = $db->error()['message'];
    }
    if(isset($_POST['ajax_request'])){
        die(json_encode(['status'=>$status, 'message'=>$msg]));
    }
}
if(isset($_POST['delete_exam'])){
    $db->delete('eschool_exams')->where(['exam_id'=>intval($_POST['delete_exam'])])->commit();
    if(!$db->error()){
        $msg = 'Deleted successful!';
        $status = 'success';
    }
    else{
        $msg = $db->error()['message'];
    }
    if(isset($_POST['ajax_request'])){
        die(json_encode(['status'=>$status, 'message'=>$msg]));
    }
}
$exams = $db->select('eschool_exams')->where(['owner_school'=>intval($owner_school['id'])])->fetchAll();
ob_start();
include __DIR__.'/html/examinations.html';
$body = ob_get_clean();
$return = ['title'=>' ', 'body'=>$body];