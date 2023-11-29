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
if(isset($_POST['schedule_attendee'])){
    $body = get_attendee($_POST['schedule_attendee'], $db);
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
if(isset($_POST['attendance_exam'])){
    if(!isset($_POST['students']) or count($_POST['students']) < 1){
        $msg = 'No student selected as attendee. Select some...';
    }
    else{
        $sid = intval($_POST['attendance_exam']);
        $chk = $db->select('eschool_exam_attendance')
                  ->where(['attendance_exam'=>$sid])
                  ->and('attendance_student')
                  ->in(array_values($_POST['students']))
                  ->fetchAll();

        if($chk){
            $msg = 'Attendance already exist! Try editing attendees';
        }
        else{
            $vals = [];
            foreach(array_values($_POST['students']) as $s){
                $vals[] = "({$sid}, {$s})";
            }
            $vals = implode(',', $vals);
            $k = $db->query("INSERT INTO eschool_exam_attendance (attendance_exam, attendance_student) VALUES {$vals}");
            $msg = json_encode($db->error());
        }
        die($msg);
    }

}
if(isset($_POST['remove_from_attendance'])){
    $data = [
        'attendance_exam'=>intval($_POST['exam_id']), 
        'attendance_student'=>intval($_POST['student_id'])
    ];
    $db->delete('eschool_exam_attendance')->where($data)->commit();
    if(!$db->error()) $msg = 'Removed from attendance successful!';
    if(isset($_POST['ajax_request'])) {
        die(json_encode(['msg'=>$msg, 'html'=>get_attendee($data['attendance_exam'], $db)]));
    }
}
if(isset($_POST['add_to_attendance'])){
    $data = [
        'attendance_exam'=>intval($_POST['exam_id']), 
        'attendance_student'=>intval($_POST['student_id'])
    ];
    $db->insert('eschool_exam_attendance', $data);
    if(!$db->error()) $msg = 'Added to attendance successful!';
    if(isset($_POST['ajax_request'])) {
        die(json_encode(['msg'=>$msg, 'html'=>get_attendee($data['attendance_exam'], $db)]));
    }
}
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
include __DIR__.'/html/attendance.html';
$body = ob_get_clean();
$return = ['title'=>' ', 'body'=>$body];