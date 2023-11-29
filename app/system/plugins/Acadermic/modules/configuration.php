<?php

$classes = $db->select('eschool_classes')
                ->order_by('class_before', 'asc')
                ->fetchAll();

$subjects = $db->select('eschool_subjects', 'eschool_subjects.*')->fetchAll();
$sections = $db->select('eschool_sections')->fetchAll();
ob_start();
include __DIR__.'/html/acadermic_configuration.html';
$body = ob_get_clean();
$return = ['title'=>' ', 'body'=>$body];