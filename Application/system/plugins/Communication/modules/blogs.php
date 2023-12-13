<?php 

if(isset($_POST['post_title'])){
    $data = [
        'owner_branch'=>$me['work_location'], 
        'post_title'=>addslashes($_POST['post_title']), 
        'post_content'=>addslashes($_POST['post_content']),
        'post_author'=>$user['user_id'],
        'create_date'=>date('Y-m-d H:i:s'),
        'post_hits'=>0, 
        'post_category'=>intval($_POST['post_category'])
    ];
    if(isset($_POST['post_id'])){
        $db->update('blog_posts', $data)->where(['post_id'=>intval($_POST['post_id'])])->commit();
    }
    else{
        //var_dump($data);die;
        $k = $db->insert('blog_posts', $data);
    }
    if(!$db->error()) $msg = 'Post saved successful';
    else $msg = $db->error()['message'];
    if(isset($_POST['ajax_request'])){
        $icon = $db->error() ? 'error' : 'success';
        die(json_encode(['status'=>$icon, 'message'=>$msg]));
    }
}
$posts = $db->select('blog_posts', 'blog_posts.*, user_accounts.full_name,branches.*, count(comment_id) as comments')
            ->join('blog_post_comments', 'blog_post_comments.post = blog_posts.post_id', 'LEFT')
            ->join('user_accounts', 'post_author=user_id')
            ->join('branches', 'owner_branch=branch_id')
            ->where(['owner_branch'=>$me['work_location']])
            ->group_by('blog_posts.post_id')
            ->order_by('create_date', 'DESC')
            ->fetchAll();

$headquarters = human_resources::get_headquarters_branch();
$isheadquarter = $headquarters == $me['work_location'] ? true : false;
$sortedPosts = [];
foreach($posts as $post){
    if(!isset($sortedPosts[$post['branch_name']])) $sortedPosts[$post['branch_name']] = [];

    $post['post_url'] = "{$myURL}/blogs/{$post['post_id']}";
    preg_match('/src="([^"]*)"/', $post['post_content'], $matches);
    $post['preview'] = @$matches[1] ? $matches[1] : 'Application/storage/uploads/products/product_thumb_0.jpg';

    $sortedPosts[$post['branch_name']][] = $post;
}
ob_start();
if(isset($args[2])){
    /*///
    $items_q = "(SELECT JSON_ARRAYAGG(JSON_OBJECT('id', comment_id, 'author',full_name, 'body', comment_content, 
                'cdate', create_date, 'approval', approval)) FROM blog_post_comments 
                JOIN user_accounts ON user_id=comment_author WHERE post_id=post) AS comments";
    $qry = "invoice.*, branches.branch_name, customer.*, user_accounts.full_name, {$items_q}, tax_invoice.*";
    $proforma = $db->select('invoice',$qry)
                    ->join('branches','branch_id=owner_branch')
                    ->join('tax_invoice', 'invoice_id=reference_invoice', 'left')
                    ->join('customer', 'customer_id=customer')
                    ->join('user_accounts', 'user_id=sale_represantative')
                    ->where($whr)
                    ->order_by('invoice_id', 'desc')
                    ->fetchAll();
    ///*/
    $post = $db->select('blog_posts')
                ->join('user_accounts', 'post_author=user_id')
                ->where(['post_id'=>intval($args[2])])->fetch();
    $comms = $db->select('blog_post_comments')
                ->join('user_accounts', 'comment_author=user_id')
                ->where(['post'=>intval($args[2])])->fetchAll();

    include __DIR__.'/html/blog_post_view.html';
}
else{
    include __DIR__.'/html/blog_posts.html';
}
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];