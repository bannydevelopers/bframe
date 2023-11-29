<?php 

$myconf = json_decode(file_get_contents(__DIR__.'/../config.json'));

if(isset($_POST['materials_topic'])){
    $data = [
        'materials_topic'=>addslashes($_POST['materials_topic']),
        'owner_school'=>intval($owner_school['id']),  
        'materials_class'=>intval($_POST['materials_class']),
        'materials_subject'=>intval($_POST['materials_subject'])
    ];
    if(is_readable($_FILES['material_attachment']['tmp_name'])){
        $dir = realpath(__DIR__.'/../uploads/study_materials');
        $name = 'sm_'.time().'.pdf';
        file_put_contents("{$dir}/{$name}",file_get_contents($_FILES['material_attachment']['tmp_name']));
        if(is_readable("{$dir}/{$name}")){
            $data['materials_content'] = "app/system/plugins/Acadermic/uploads/study_materials/{$name}";
            $k = $db->insert('eschool_study_materials', $data);
            if(!$db->error() && $k){
                if(isset($_POST['ajax_request'])) die('ok');
                else $msg = 'Sylabus added successful!';
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
    if(isset($_POST['ajax_request'])) die($msg);
}
if(isset($_POST['delete_study_material'])){
    $whr = ['materials_id'=>intval($_POST['delete_study_material'])];

    $sm = $db->select('eschool_study_materials','materials_content')->where($whr)->limit(1)->fetch();

    $k = $db->delete('eschool_study_materials','')
            ->where($whr)->commit();

    if(!$db->error() && $k){
        unlink(realpath(__DIR__.'/../../../../../').'/'.$sm['materials_content']);
        if(isset($_POST['ajax_request'])) die('ok');
        else $msg = 'Study material deleted successful!';
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

$materials = $db->select('eschool_study_materials')
              ->join('eschool_classes','materials_class=class_id')
              ->join('eschool_subjects','materials_subject=subject_id')
              ->where("eschool_study_materials.owner_school={$owner_school['id']}")
              ->fetchAll();

ob_start();
include __DIR__.'/html/study_materials.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];