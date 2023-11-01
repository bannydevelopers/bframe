<?php

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);

$designations = $db->select('designations')->fetchAll();

ob_start();
include __DIR__.'/html/designations.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];