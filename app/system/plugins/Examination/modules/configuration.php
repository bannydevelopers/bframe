<?php

$classes = $db->select('eschool_classes')
                ->order_by('class_before', 'asc')
                ->fetchAll();

$subjects = $db->select('eschool_subjects', 'eschool_subjects.*')->fetchAll();
$sections = $db->select('eschool_sections')->fetchAll();

$grades = $db->select('eschool_grades')->where(['owner_school'=>intval($owner_school['id'])])->fetchAll();

$exams = $db->select('exam')->where(['owner_school'=>intval($owner_school['id'])])->fetchAll();
ob_start();
include __DIR__.'/html/schedule.html';
$body = ob_get_clean();
$return = ['title'=>' ', 'body'=>$body];