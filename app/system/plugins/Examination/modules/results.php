<?php

function get_attendee($sid, $db){

    $students = $db->select('eschool_students')
                    ->join('eschool_classes', 'class_id=student_class')
                    ->join('eschool_sections', 'section_id=student_section')
                    ->where("student_class IN (SELECT schedule_class FROM eschool_examschedule WHERE schedule_id = {$sid})")
                    ->and("student_section IN (SELECT schedule_section FROM eschool_examschedule WHERE schedule_id = {$sid})")
                    ->fetchAll();

    $attendance = $db->select('eschool_exam_attendance','attendance_student')
                     ->where(['attendance_exam'=>$sid])
                     ->fetchAll();

    $attendance = array_column($attendance, 'attendance_student');
    ob_start();
    include __DIR__.'/html/students.html';
    return ob_get_clean();
}
if(isset($_POST['fetch_results'])){
    $res = $db->select('eschool_exam_results')
              ->join('eschool_students', 'result_student=student_id')
              ->join('eschool_classes', 'class_id=student_class')
              ->join('eschool_sections', 'section_id=student_section')
              ->join('eschool_examschedule', 'result_exam=schedule_id')
              ->join('eschool_exams', 'exam_id=schedule_exam')
              ->join('eschool_subjects','schedule_subject=subject_id')
              ->where('result_student')
              ->in("SELECT student_id FROM eschool_students WHERE student_class = {$_POST['class']} AND student_section = {$_POST['section']}")
              ->fetchAll();

    if($res){
        $results = [];
        foreach($res as $r){
            if(!isset($results[$r['subject_name']])) $results[$r['subject_name']] = [];
            $results[$r['subject_name']][] = $r;
        }
        ob_start();
        include __DIR__.'/html/student_results.html';
        //ob_get_clean();
        ob_end_flush();
    }
    else{
        ob_start();
        include __DIR__.'/html/no_match.html';
        //ob_get_clean();
        ob_end_flush();
    }
    die;
}

$class = $db->select('eschool_classes')
            ->where(['owner_school'=>$owner_school['id']])
            ->order_by('class_before', 'asc')
            ->fetchAll();

$section = $db->select('eschool_sections')
              ->where(['owner_school'=>$owner_school['id']])
              ->order_by('section_name', 'asc')
              ->fetchAll();

ob_start();
include __DIR__.'/html/results.html';
$body = ob_get_clean();
$return = ['title'=>' ', 'body'=>$body];