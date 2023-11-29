<?php

$whr = ["owner_school"=>$owner_school['id']];

if(isset($_POST['schedule_exam'])){
    $data = [
        "schedule_exam"=>intval($_POST['schedule_exam']),
        "schedule_subject"=>intval($_POST['schedule_subject']),
        "schedule_class"=>intval($_POST['schedule_class']),
        "schedule_section"=>intval($_POST['schedule_section']),
        "schedule_from"=>addslashes($_POST['schedule_from']),
        "schedule_to"=>addslashes($_POST['schedule_to']),
        "notes"=>addslashes($_POST['notes']),
        "owner_school"=>$owner_school['id'],
        "schedule_date"=>date('Y-m-d')
    ];
    $db->insert('eschool_examschedule', $data);
    if($db->error()) $msg = $db->error()['message'];
    else $msg = 'Saved successful';
}
$classes = $db->select('eschool_classes')
                ->where($whr)
                ->order_by('class_before', 'asc')
                ->fetchAll();

$subjects = $db->select('eschool_subjects', 'eschool_subjects.*')
               ->where($whr)
               ->fetchAll();

$sections = $db->select('eschool_sections')
               ->where($whr)
               ->order_by('section_name','asc')
               ->fetchAll();

$grades = $db->select('eschool_grades')
             ->where($whr)
             ->fetchAll();

$examination = $db->select('eschool_exams')
             ->where($whr)
             ->fetchAll();

$exams = $db->select('eschool_examschedule')
            ->join('eschool_subjects','schedule_subject=subject_id')
            ->join('eschool_classes','schedule_class=class_id')
            ->join('eschool_sections','schedule_section=section_id', 'left')
            ->join('eschool_exams', 'schedule_exam=exam_id', 'left')
            ->where("eschool_examschedule.owner_school={$owner_school['id']}")
            ->order_by('schedule_date', 'desc')
            ->fetchAll();

$sortedExams = [];
foreach($exams as $ex){
    if(!isset($sortedExams[$ex['exam_name']])) $sortedExams[$ex['exam_name']] = [];
    $sortedExams[$ex['exam_name']][] = $ex;
}
ob_start();
include __DIR__.'/html/schedule.html';
$body = ob_get_clean();
$return = ['title'=>' ', 'body'=>$body];