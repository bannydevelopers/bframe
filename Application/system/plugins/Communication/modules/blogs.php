<?php 

$departments = $db->select('departments')->fetchAll();

ob_start();
include __DIR__.'/html/blog_posts.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];