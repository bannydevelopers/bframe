<?php

function get_attendee($sid, $db){

    $students = $db->select('eschool_students')
                    ->join('eschool_classes', 'class_id=student_class')
                    ->join('eschool_sections', 'section_id=student_section')
                    ->join('eschool_exam_results', 'result_student=student_id', 'left')
                    ->where(['result_exam' => $sid])
                    ->and("student_id IN (SELECT result_student FROM eschool_exam_results WHERE result_exam = {$sid})")
                    ->fetchAll();
//var_dump('<pre>', $students);echo '</pre>';
    /*$marksheet = $db->select('eschool_exam_results')
                    ->join('eschool_exam_results', 'result_student=student_id')
                    ->where(['result_exam'=>$sid])
                    ->fetchAll();
    $marks = [];
    foreach($marksheet as $m) $marks[$m['result_student']] = $m;*/
    ob_start();
    if($students) include __DIR__.'/html/students_mark_sheet.html';
    return ob_get_clean();
}
if(isset($_POST['schedule_attendee'])){
    $body = get_attendee($_POST['schedule_attendee'], $db);
    if(empty(trim($body))){
        $sid = $_POST['schedule_attendee'];
        $students = $db->select('eschool_students')
                        ->join('eschool_classes', 'class_id=student_class')
                        ->join('eschool_sections', 'section_id=student_section')
                        ->join('eschool_exam_attendance', 'attendance_student=student_id', 'left')
                        ->where(['attendance_exam' => $sid])
                        ->and("student_id IN (SELECT attendance_student FROM eschool_exam_attendance WHERE attendance_exam = {$sid})")
                        ->fetchAll();

        ob_start();
        if($students) include __DIR__.'/html/students_mark_sheet.html';
        else include __DIR__.'/html/no_match.html';
        $body = ob_get_clean();
    }
    die($body);
}
if(isset($_POST['list_students'])){
    $sid = $_POST['list_students'];
    $students = $db->select('eschool_students')
                    ->where("student_class IN (SELECT schedule_class FROM eschool_examschedule WHERE schedule_id = {$sid})")
                    ->and("student_section IN (SELECT schedule_section FROM eschool_examschedule WHERE schedule_id = {$sid})")
                    ->fetchAll();
    $ret = '';
    foreach($students as $k=>$std){
        $si = "{$std['student_name']}";
        $ret .= "<div><input type=\"checkbox\" name=\"students[]\" id=\"std{$k}\" value=\"{$std['student_id']}\"><label for=\"std{$k}\">{$si}</label></div>";
    }
    die($ret);
}
if(isset($_POST['mark_sheet_exam'])){
    //var_dump($_POST);die;
    $sid = intval($_POST['mark_sheet_exam']);
    
    $qry = "INSERT INTO eschool_exam_results (result_exam, result_student, result_mark) VALUES ";
    $vals = [];
    foreach($_POST['score'] as $st=>$sc){
        $vals[] = "({$sid}, {$st}, {$sc})";
    }
    $vals = implode(',', $vals);
    $qry .= " {$vals} ON DUPLICATE KEY UPDATE result_mark=VALUES(result_mark)";
        $db->query($qry);
        //var_dump($db->error());
        if($db->error()) die('Saving failed');
        else die('Saved successful');
}
//$exams = $db->select('exam')->where(['owner_school'=>intval($owner_school['id'])])->fetchAll();
$whr = "eschool_examschedule.owner_school={$owner_school['id']}";

$exams = $db->select('eschool_examschedule','schedule_id,schedule_date,subject_name,class_name, section_name,exam_name')
            ->join('eschool_subjects','schedule_subject=subject_id')
            ->join('eschool_classes','schedule_class=class_id')
            ->join('eschool_sections','schedule_section=section_id', 'left')
            ->join('eschool_exams', 'schedule_exam=exam_id', 'left')
            ->where($whr)
            ->order_by('schedule_date', 'desc')
            ->fetchAll();
            
ob_start();
include __DIR__.'/html/mark_sheet.html';
$body = ob_get_clean();
$return = ['title'=>' ', 'body'=>$body];