<?php 

if(isset($_POST['post_title'])){
    $data = [
        'owner_branch'=>$me['work_location'], 
        'post_title'=>addslashes($_POST['post_title']), 
        'post_content'=>addslashes($_POST['post_content']),
        'post_writer'=>$user['user_id'],
        'create_date'=>date('Y-m-d H:i:s'),
        'post_hits'=>0, 
        'post_category'=>intval($_POST['post_category']),
        'last_modified'=>date('Y-m-d H:i:s'),
    ];
    if(isset($_POST['post_id'])){
        $db->update('blog_posts', $data)->where(['post_id'=>intval($_POST['post_id'])])->commit();
    }
    else{
        $db->insert('blog_posts', $data);
    }
    if(!$db->error()) $msg = 'Post saved successful';
    else $msg = $db->error()['message'];
    if(isset($_POST['ajax_request'])){
        $icon = $db->error() ? 'error' : 'success';
        die(json_encode(['status'=>$icon, 'message'=>$msg]));
    }
}
$posts = $db->select('blog_posts')
            ->where(['owner_branch'=>$me['work_location']])
            ->fetchAll();

ob_start();
include __DIR__.'/html/blog_posts.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];