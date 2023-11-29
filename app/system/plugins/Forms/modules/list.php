<?php 

$registry = storage::init();
$db = db::get_connection($registry->system_config);

$my_url = "{$registry->request[0]}/{$registry->request[1]}/{$registry->request[2]}/{$registry->request[3]}";
if(isset($_POST['page_content']) && isset($_POST['page_id'])){
    $id = intval($_POST['page_id']);
    //var_dump($_POST);die;
    $_POST['page_extras']['form_fields'] = htmlspecialchars($_POST['page_extras']['form_fields']);
    $data = [
        'page_title'=>$_POST['page_title'], 
        'page_name'=>$_POST['page_name'], 
        //'page_parent'=>$_POST['page_parent'], 
        'page_content'=>$_POST['page_content'], 
        'page_icon'=>$_POST['page_icon'], 
        'page_order'=>$_POST['page_order'], 
        'page_desc'=>$_POST['page_desc'], 
        'page_keywords'=>$_POST['page_keywords'], 
        'page_extras'=>json_encode($_POST['page_extras'])
    ];
    $db->update('pages', $data)->where(['page_id'=>$id])->commit();
    //var_dump($db->error());
}
if( isset($registry->request[4]) ){
    if( intval($registry->request[4]) ){
        $page =  $db->select('pages')
                    //->join('user_accounts', 'page_author=user_id', 'LEFT')
                    ->where(['page_type'=>'forms::form_page', 'page_id'=>intval($registry->request[4])])
                    ->fetch();
        //$pages =  $db->select('pages', 'page_id, page_name')->where('page_special<2')->fetchAll();
        $page['page_extras'] = json_decode($page['page_extras'], true);

        $form_fields = isset($page['page_extras']['form_fields']) ? $page['page_extras']['form_fields'] : [];
        //die($form_fields);
        ob_start();
        include __DIR__.'/templates/form-edit.html';
        $htm = ob_get_clean();
    }
    else{
        $page = [
            'page_id'=>'',
            'page_title'=>'',
            'page_name'=>'',
            'page_type'=>'',
            'page_parent'=>'',
            'page_content'=>'',
            'page_icon'=>'',
            'page_order'=>'',
            'page_desc'=>'',
            'page_keywords'=>'',
            'page_author'=>'',
            'create_date'=>'',
            'page_special'=>'',
            'page_extras'=>[]
        ];
        ob_start();
        include __DIR__.'/templates/form-edit.html';
        $htm = ob_get_clean();
    }
}
else {
    $pages =  $db->select('pages')
                //->join('user_accounts', 'page_author=user_id', 'LEFT')
                ->where(['page_type'=>'forms::form_page'])
                ->fetchAll();
    ob_start();
    include __DIR__.'/templates/forms-list.html';
    $htm = ob_get_clean();
}
$return = ['title'=>' ','body'=>$htm];