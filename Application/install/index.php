<?php 

if(is_readable(__DIR__.'/config.json')){
    include __DIR__.'/query_runner.php';
    die;
}
else{
    if(isset($_FILES['sql_schema'])){
        $fn = 'schema.sql';
        move_uploaded_file($_FILES['sql_schema']['tmp_name'], __DIR__.'/'.$fn);
        $_POST['sql_schema_file'] = $fn;
        $json = json_encode($_POST, JSON_PRETTY_PRINT);
        file_put_contents('config.json', $json);
        if(is_readable(__DIR__.'/'.$fn)){
            include __DIR__.'/query_runner.php';
            die;
        }
    }
    $form = '
    <title>System installer</title>
    <form method="POST" enctype="multipart/form-data">
    <h3>Let\'s correct some info</h3> 
    <hr>
    <label>Schema file (.sql)</label>
    <input type="file" name="sql_schema" accept=".sql" required>
    <label>Database host</label>
    <input type="text" name="db_host" value="localhost" required>
    <label>Database name</label>
    <input type="text" name="db_name" placeholder="name of existing database" required>
    <label>Database user</label>
    <input type="text" name="db_user" value="root" required>
    <label>Database pass</label>
    <input type="text" name="db_pass" placeholder="password of the database">
    <div style="text-align:right;padding-top:1em"><button>CONTINUE</button></div>
    </form>
    <style>
    *{margin:0;transition: all .5s;}body{font-family: Arial, Helvetica, sans-serif;}
    form{max-width: 300px;margin:auto;margin-top:1em;box-shadow:0 0 3px #999;padding:1em 2em;border-radius: 8px;}
    label{display:block;margin-top:1em;padding: .5em 0;}button{padding: .8em 1.3em;}
    input{width:100%;padding: .8em 1em;border-radius: 4px;border: 1px solid #ccc;}
    input:focus{outline:none;box-shadow: 0 0 4px #999;}h3{text-transform: uppercase;padding: 1em 0;text-align: center;}
    </style>
    ';
    echo $form;
}