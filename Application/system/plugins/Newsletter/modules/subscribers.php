<?php 

$registry = storage::init();
$db = db::get_connection($registry->system_config);

$subscribers = $db->select('newsletter_subscribers')
                  ->fetchAll();

$subs = '';
foreach($subscribers as $sub){
    ob_start();
    include realpath(__DIR__.'/../assets/html/').'/subscriber_card.html';
    $subs .= ob_get_clean();
}
$return = ['title'=>'Subscribers','body'=>"<div class=\"flex cards\">{$subs}</div>"];