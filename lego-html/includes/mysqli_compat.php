<?php
// =====================================================================
// Camada de compatibilidade: imita o trecho do PDO usado no projeto,
// mas por cima do mysqli. Só é carregada quando o PHP NÃO tem pdo_mysql.
// =====================================================================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class MyStmt {
    private $db;
    private $sql;
    private $rows = array();
    private $pos = 0;
    private $affected = 0;

    public function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }

    public function execute($params = array()) {
        $m = $this->db->conn();
        $stmt = $m->prepare($this->sql);
        if ($params) {
            $types = '';
            $vals = array();
            foreach (array_values($params) as $v) {
                if (is_bool($v)) { $v = (int)$v; }
                if (is_int($v))       { $types .= 'i'; }
                elseif (is_float($v)) { $types .= 'd'; }
                else                  { $types .= 's'; }
                $vals[] = $v;
            }
            $refs = array();
            $refs[] = &$types;
            foreach ($vals as $k => $unused) { $refs[] = &$vals[$k]; }
            call_user_func_array(array($stmt, 'bind_param'), $refs);
        }
        $stmt->execute();
        $this->affected = $stmt->affected_rows;
        if ($stmt->insert_id > 0) { $this->db->lastId = $stmt->insert_id; }
        $res = $stmt->get_result();
        $this->rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : array();
        if ($res) { $res->free(); }
        $stmt->close();
        $this->pos = 0;
        return true;
    }

    public function fetch() {
        return $this->pos < count($this->rows) ? $this->rows[$this->pos++] : false;
    }
    public function fetchAll() {
        $r = array_slice($this->rows, $this->pos);
        $this->pos = count($this->rows);
        return $r;
    }
    public function fetchColumn() {
        $row = $this->fetch();
        return $row ? reset($row) : false;
    }
    public function rowCount() { return $this->affected; }
}

class MyDb {
    public $lastId = 0;
    private $m;
    private $inTx = false;

    public function __construct($host, $user, $pass, $name, $port) {
        $this->m = new mysqli($host, $user, $pass, $name, (int)$port);
        $this->m->set_charset('utf8mb4');
        $this->m->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
    }
    public function conn() { return $this->m; }
    public function prepare($sql) { return new MyStmt($this, $sql); }
    public function query($sql) { $s = new MyStmt($this, $sql); $s->execute(); return $s; }
    public function exec($sql) { $this->m->query($sql); }
    public function lastInsertId() { return $this->lastId; }
    public function beginTransaction() { $this->m->autocommit(false); $this->inTx = true; return true; }
    public function commit() { $this->m->commit(); $this->m->autocommit(true); $this->inTx = false; return true; }
    public function rollBack() { $this->m->rollback(); $this->m->autocommit(true); $this->inTx = false; return true; }
    public function inTransaction() { return $this->inTx; }
}
