<?php 

$myconf = json_decode(file_get_contents(__DIR__.'/../config.json'));

if(isset($_POST['assignment_title'])){
    $data = [
        'assignment_title'=>addslashes($_POST['assignment_title']), 
        'owner_school'=>intval($owner_school['id']), 
        'assignment_class'=>intval($_POST['assignment_class']),
        'assignment_section'=>intval($_POST['assignment_section']),
        'assignment_subject'=>intval($_POST['assignment_subject']),
        'assigment_date'=>addslashes($_POST['assigment_date']),
        'assignment_submission_date'=>addslashes($_POST['assignment_submission_date'])
    ];
    if(is_readable($_FILES['assignment_attachment']['tmp_name'])){
        $dir = realpath(__DIR__.'/../uploads/assignments');
        $name = 'as_'.time().'.pdf';
        file_put_contents("{$dir}/{$name}",file_get_contents($_FILES['assignment_attachment']['tmp_name']));
        if(is_readable("{$dir}/{$name}")){
            $data['assignment_content'] = "app/system/plugins/Acadermic/uploads/assignments/{$name}";
            $k = $db->insert('eschool_assignments', $data);
            if(!$db->error() && $k){
                if(isset($_POST['ajax_request'])) die('ok');
                else $msg = 'Assignment added successful!';
            }
            else{
                $msg = $db->error()['message'];
            }
        }
        else{
            $msg = 'Attachment saving failed!';
        }
        if(isset($_POST['ajax_request'])) die($msg);
    }
    else{
        $msg = 'Unkown error occured!';
    }
}
if(isset($_POST['delete_assignment'])){
    $whr = ['assignment_id'=>intval($_POST['delete_assignment'])];

    $sm = $db->select('eschool_assignments','assignment_content')
             ->where($whr)
             ->and(['owner_school'=>intval($owner_school['id'])])
             ->limit(1)
             ->fetch();

    $k = $db->delete('eschool_assignments')
            ->where($whr)
            ->and(['owner_school'=>intval($owner_school['id'])])
            ->commit();

    if(!$db->error() && $k){
        unlink(realpath(__DIR__.'/../../../../../').'/'.$sm['assignment_content']);
        if(isset($_POST['ajax_request'])) die('ok');
        else $msg = 'Assignment deleted successful!';
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

$sections = $db->select('eschool_sections')
                ->where(['owner_school'=>intval($owner_school['id'])])
                ->order_by('section_name', 'asc')
                ->fetchAll();

$assignments = $db->select('eschool_assignments')
                    ->join('eschool_classes','assignment_class=class_id')
                    ->join('eschool_sections','assignment_section=section_id')
                    ->join('eschool_subjects','assignment_subject=subject_id')
                    ->where("eschool_assignments.owner_school={$owner_school['id']}")
                    ->fetchAll();

ob_start();
include __DIR__.'/html/assignments.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];