<?php 
$me = human_resources::get_staff();
if($me){
    $return = ['title'=>'News and updates', 'body'=>'Comming soon'];
}
else $return = ['title'=>'Access denied!', 'body'=>'You are required to be registered as staff to continue'];