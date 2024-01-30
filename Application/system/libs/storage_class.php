<?php
class storage{
    private static $data = [];
    private static $instance = null;
    
    private function __construct(){
        // Made it private to prevent multiple instances
    }

    public static function get_instance(){
        if (self::$instance == null) self::$instance = new static();
        return self::$instance; 
    }

    public static function init(){
        return self::get_instance();
    }

    public function __set($index, $data){
        self::$data[$index] = $data;
    }

    public function __get($index){
        //return get_called_class()::get_data($index);
        return self::get_data($index);
    }

    public function __toString(){
        return $this->get_json();
    }
    public function get_json(){
        return json_encode(self::$data, JSON_PRETTY_PRINT);
    }

    public static function get_data($index = null){
        if($index == null) return self::$data;
        if(isset(self::$data[$index])) return self::$data[$index];
        else return null;
    }

	public static function get_entity(string $entity_name){
		return self::get_data($entity_name);
	}

    public static function save_data($index, $data){
        self::$data[$index] = $data;
    }

    public static function append_data($index, $data){
        if(!isset(self::$data[$index])) self::$data[$index] = [];
        $data_type = gettype(self::$data[$index]);
        $given_data_type = gettype($data);
        if(is_array(self::$data[$index])) self::$data[$index][] = $data;
        elseif(is_string(self::$data[$index]) && is_string($data)) self::$data[$index] .= $data;
        else return "Existing data type '$data_type' cannot be extended with given data type '$given_data_type'!";
        // We could've changed other types to array and extend but may break codes of the former accessor
    }
}