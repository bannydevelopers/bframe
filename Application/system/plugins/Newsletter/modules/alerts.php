<?php 

if(isset($_POST['subscribe_thanks'])){
    $data = json_encode($_POST, JSON_PRETTY_PRINT);
    file_put_contents(__DIR__.'/alerts.json', $data);
}
$conf_txt = file_get_contents(__DIR__.'/alerts.json');
$cd = json_decode($conf_txt);
//$zones = $db->select('weather_zones')->fetchAll();

ob_start();
include __DIR__.'/templates/alerts.html';
$htm = ob_get_clean();
$return = ['title'=>' ','body'=>$htm];