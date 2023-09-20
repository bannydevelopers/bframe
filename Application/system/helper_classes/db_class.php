<?php 

class db{
   private static $instance = null; 
   private static $connection = null;
   private $db_driver = 'mysql';
   public $error;
   private $data = [];
   private static $query;
   
   private function __construct(){

   } 
   public static function initialize($db_opt){
    $db_opt = (array) $db_opt;
    if(self::$instance === null){
        self::$instance = new static($db_opt);
        $pdo = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
            PDO::ATTR_EMULATE_PREPARES => true
        ];
        $support = ['mysql','pgsql','oci','sqlite'];
        if(isset($db_opt['db_driver']) && in_array(strtolower($db_opt['db_driver']), $support)) self::$instance->db_driver = $db_opt['db_driver'];
        else throw new Exception('Unsupported database driver');
        $driver = self::$instance->db_driver;
        $host = isset($db_opt['hostname']) ? $db_opt['hostname'] : 'localhost';
        try{
            if(strtolower($driver) == 'sqlite'){
                $dir = realpath(__DIR__);
                self::$connection = new PDO("sqlite:{$dir}/{$db_opt['db_name']}.sqlx");
            }
            else{
                $dsn ="{$driver}:host={$host};dbname={$db_opt['db_name']}";
                self::$connection = new PDO($dsn, $db_opt['db_user'],$db_opt['db_password'], $pdo);
            }
        }
        catch(PDOException $exception){
            //to be continued
            var_dump('<pre>',$exception);
        }
    }
    return self::$instance;
   }

   public function select($table, $fields = '*'){
        self::$query = "SELECT $fields FROM $table";
        return $this;
    } 
   public function fetch(){
        $return = $this->bind_data();
        return $return ? $return->fetch(PDO::FETCH_ASSOC) : [];
   }
   public function fetchAll(){
    $return = $this->bind_data();
    return $return ? $return->fetchAll(PDO::FETCH_ASSOC) : [];
   }
    public function insert($table, $data){
        $values = [];
        if(isset($data[0]) && is_array($data[0])) {
            $columns = array_keys($data[0]);
            foreach($data[0] as $key=>$value){
                $values[] = '(:'.implode(", :", $columns).')';   
            }
        }
        else {
            $columns = array_keys($data);
            $values[] = '(:'.implode(", :", $columns).')'; 
        }
        $values = implode(', ', $values);

        $fields = implode(', ', $columns);
        self::$query = "INSERT INTO $table ({$fields}) VALUES $values";
        $this->data = $data;
        $statement = $this->bind_data();
        //echo '<br>', self::$query, '<br>';
        return 0;//$statement->lastInsertId;
    }
    private function create_condition($condition, $type){
        $where = [];
        if(is_array($condition)){
            foreach($condition as $key => $value) {
                $kx = $key;
                if(isset($this->data[$kx])){
                    $kx = 'kx'.time();
                }
                $this->data[$kx] = $value;
                if(is_null($value)){
                    $where[] = "{$key} IS NULL";
                }
                else{
                    $where[] = "{$key} = :{$kx}";
                }
            }
            self::$query .= " {$type} ".implode(' AND ', $where);
        }
        else{
            self::$query .= " {$type} {$condition}";
        }
    }
    public function where($condition){
        $this->create_condition($condition, 'WHERE');
        return $this;
    }
    public function and($condition){
        $this->create_condition($condition, 'AND');
        return $this;

    }  
    public function or($condition){
        $this->create_condition($condition, 'OR');
        return $this;
    } 
    public function in($condition){
        $this->create_condition($condition, 'IN');
        return $this;
    } 
    public function like($pattern){
        self::$query .= " LIKE '%{$pattern}%'";
        return $this;
    }
    public function limit($count, $start = 0){
        self::$query .= " LIMIT {$start}, {$count}";
        return $this;
    }
    public function join($table, $field1, $field2, $type_of_join = ''){
        self::$query .= " {$type_of_join} JOIN ON {$field1}={$field2}";
        return $this;

    } 
    public function having($condition){
        $this->create_condition($condition, 'HAVING');
        return $this;
    }
    public function group_by($columns){
        self::$query .= " GROUP BY {$columns}";
        return $this;
    }
    public function order_by($columns, $order = 'DESC'){
        self::$query .= " ORDER BY {$columns} {$order}";
        return $this;
    }
    public function delete($table){
        self::$query .= " DELETE FROM {$table}";
        return $this;
    }
    public function update($table, $data){
        $this->data = $data;
        if(isset($this->data[0]) && is_array($this->data[0])){
            $fields = array_keys($this->data[0]);
        }
        else{
            $fields = array_keys($this->data);
        }
        $set = [];
        foreach($fields as $field) $set[] = "{$field} = :{$field}";
        $set = implode(', ', $set);
        self::$query .= "UPDATE {$table} SET {$set}";
        return $this;
    }
    public function commit(){
        return $this->bind_data();
    }
   private function bind_data(){
        if(!self::$connection) return;
        try{
            $opts = [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY];
            $statement = self::$connection->prepare(self::$query, $opts);
            if(isset($this->data[0]) && is_array($this->data[0])) {
                foreach ($this->data as $key => $value) {
                    $this->send_value($value, $statement);
                }
            }
            else{
                $this->send_value($this->data, $statement);
            }
            $this->data = [];
            $statement->execute();
        }
        catch(PDOException $exception){
            //to be continued
            var_dump('<pre>',$exception);
        }
        return $statement;
    }
    private function send_value($data, $statement){
        foreach ($data as $key => $value) {
            switch ($value) {
                case intval($value): 
                    $data_type = PDO::PARAM_INT;
                    break;
                case is_bool($value): 
                    $data_type = PDO::PARAM_BOOL;
                    break;
                case is_null($value): 
                    $data_type = PDO::PARAM_NULL;
                    break;
                
                default:
                    $data_type = PDO::PARAM_STR;
                    break;
            }
            $statement->bindValue($key, $value, $data_type);
        }
        $statement->execute();
    }

}