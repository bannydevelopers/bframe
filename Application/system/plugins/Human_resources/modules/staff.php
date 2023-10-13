<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);


$staff = $db->select('notes')
              ->join('acadermic_classes','class_id=level')
              ->order_by('notes_name', 'desc')
              ->fetchAll();
$body = '';
ob_start();
include __DIR__.'/html/staff.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];