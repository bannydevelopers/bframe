<?php
class storage{
    private static $data = [];
    private static $instance = null;

    private function __construct(){
        // prohibited
    }
    
   public static function initialize(){
        if(self::$instance === null) self::$instance = new static();
        return self::$instance;
    }
    public function __set($index, $data){
        self::$data[$index] = $data;
    }
    public function __get($index){
        if(isset(self::$data[$index])) {
            return self::$data[$index];
        }
        else {
            return null;
        }
    }
}