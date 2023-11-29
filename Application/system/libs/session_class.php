<?php
class session implements SessionHandlerInterface{
    // Acquire db instance
    private $db;

    public function __construct()
    {
        $this->db  = db::get_connection(storage::get_instance()->system_config->db_configs);
    }

    public function open($savePath, $sessionName): bool
    {
        if($this->db) return true;
        else false;
    }

    public function read($saveId): string
    {
        // SELECT session_content FROM sessions WHERE session_id = ?
        $sess_data = $this->db->select('user_sessions', 'session_content')
                     ->where(['session_id', $saveId])
                     ->limit(1)->fetch();
        return $sess_data ? $sess_data : '';
    }

    public function write($saveId, $sessionData): bool
    {
        // REPLACE INTO sessions(`session_id`, `session_content`, `created`) VALUES(?, ?, ?)
        $this->db->replace(
            'user_sessions', 
            [
                'session_id'=>$saveId, 
                'session_content'=>$sessionData, 
                'created'=>time()
            ]
        );
        return $this->db->error() ? false : true;
    }

    public function destroy($saveId): bool
    {
        // DELETE FROM sessions WHERE session_id = ?
        $this->db->delete('user_sessions')->where(['session_id', $saveId]);
        return $this->db->error() ? false : true;
    }

    public function close(): bool
    {
        return true;
    }

    public function gc($max_lifetime): int
    {
        // DELETE FROM sessions WHERE `created` < ?
        $past = time() - $max_lifetime;
        $this->db->delete('user_sessions')->where("created < $past")->commit();
        return $this->db->error() ? 0 : 1;
    }
}