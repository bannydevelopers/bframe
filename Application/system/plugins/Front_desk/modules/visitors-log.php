<?php 

$myconf = json_decode(file_get_contents(realpath(__DIR__.'/../').'/config.json'));

ob_start();
include __DIR__.'/html/visitor_log.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];