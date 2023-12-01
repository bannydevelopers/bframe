<?php
$root = realpath(__DIR__.'/../../../');
if(isset($_FILES['file']) && is_writable($_FILES['file']['tmp_name'])){
	move_uploaded_file($_FILES['file']['tmp_name'], $root.'/Application/storage/uploads/images/'.$_FILES['file']['name']);
	echo stripslashes(json_encode(["location"=>'Application/storage/uploads/images/'.$_FILES['file']['name']]));
	die;
}	
if($_POST['ftype'] == 'image'){
	$dirs = scandir($root.'/Application/storage/uploads/images/');
	array_shift($dirs);
	array_shift($dirs);
	$body = '<div class="pad-min" style="min-height:220px">';
	foreach ($dirs as $key => $value) {
		$supported = ['jpeg','jpg','png','bmp','gif','jfif','webp'];
		$ext = trim(substr($value, -4),'.');
		if(!in_array($ext, $supported)) continue;
		$body .= '<img src="Application/storage/uploads/images/'.$value.'" height="60" alt="'.$value.'" title="'.$value.'" style="padding:4px">';
	}
	$body .= '</div>';
	echo $body;
}
else if($_REQUEST['filetype'] == 'media'){
	echo 'Videos are currently not supported!';
}