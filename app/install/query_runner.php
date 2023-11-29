<?php
if(is_readable(__DIR__.'/config.json')){
    $config = json_decode(file_get_contents(__DIR__.'/config.json'));
    if(!json_last_error() && $config){
        // your config
        $filename = $config->sql_schema_file;
        $dbHost = $config->db_host;
        $dbUser = $config->db_user;
        $dbPass = $config->db_pass;
        $dbName = $config->db_name;
        $maxRuntime = 8; // less than your max script execution limit

        $deadline = time() + $maxRuntime; 
        $progressFilename = $filename.'_filepointer'; // tmp file for progress
        $errorFilename = $filename.'_error'; // tmp file for erro
        try{
            $con = mysqli_connect($dbHost, $dbUser, $dbPass);
            if($con) mysqli_select_db($con, $dbName);
        }
        catch(Exception $e){
            @unlink(__DIR__."/{$progressFilename}");
            @unlink(__DIR__."/{$errorFilename}");
            @unlink(__DIR__."/config.json");
            @unlink(__DIR__."/schema.sql");
            die($e->getMessage());
        }
        ($fp = fopen($filename, 'r')) OR die('failed to open file:'.$filename);

        // check for previous error
        if( file_exists($errorFilename) ){
            die('<pre> Previous error: '.file_get_contents($errorFilename));
        }

        // go to previous file position
        $filePosition = 0;
        if( file_exists($progressFilename) ){
            $filePosition = file_get_contents($progressFilename);
            fseek($fp, $filePosition);
        }

        $queryCount = 0;
        $query = '';
        while( $deadline>time() AND ($line=fgets($fp, 1024000)) ){
            if(substr($line,0,2)=='--' OR trim($line)=='' ){
                continue;
            }

            $query .= $line;
            if( substr(trim($query),-1)==';' ){
                try{
                    if( !mysqli_query($con, $query) ){
                        $error = 'Error performing query \'<strong>' . $query . '\': ' . mysqli_error($con);
                        file_put_contents($errorFilename, $error."\n");
                        exit;
                    }
                    $query = '';
                    file_put_contents($progressFilename, ftell($fp)); // save the current file position for 
                    $queryCount++;
                }
                catch(Exception $e){
                    @unlink(__DIR__."/{$progressFilename}");
                    @unlink(__DIR__."/{$errorFilename}");
                    @unlink(__DIR__."/config.json");
                    @unlink(__DIR__."/schema.sql");
                    die($e->getMessage());
                }
            }
        }

        if( feof($fp) ){
            echo 'Installation is successful! <a href="../../">Continue</a>';
            @unlink(__DIR__."/{$progressFilename}");
            @unlink(__DIR__."/{$errorFilename}");
            @unlink(__DIR__."/config.json");
            @unlink(__DIR__."/schema.sql");
            $cfile = realpath(__DIR__.'/../system/secure/').'/config.json';
            $sys_conf = json_decode(file_get_contents($cfile));
            $sys_conf->db_configs->hostname = $dbHost;
            $sys_conf->db_configs->db_name = $dbName;
            $sys_conf->db_configs->username = $dbUser;
            $sys_conf->db_configs->password = $dbPass;
            file_put_contents($cfile, json_encode($sys_conf, JSON_PRETTY_PRINT));
        }
        else{
            // activate automatic reload in browser
            echo '<html><head> <meta http-equiv="refresh" content="'.($maxRuntime+2).'"></head><body>';
            echo ftell($fp).'/'.filesize($filename).' '.(round(ftell($fp)/filesize($filename), 2)*100).'%'."\n";
            echo $queryCount.' queries processed! please reload or wait for automatic browser refresh!';
        }
    }
}