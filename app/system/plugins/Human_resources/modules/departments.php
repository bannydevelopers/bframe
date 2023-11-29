<?php

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);

$departments = $db->select('departments')->fetchAll();

ob_start();
include __DIR__.'/html/departments.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];