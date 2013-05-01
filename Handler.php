<?
require 'HandlerSocketManager.php';

/**
 * 透過HandlerSocket對資料庫做新增刪除修改查詢的操作
 */
class Handler {

    static private $dataBasePool = array();
    
    /**
     * 資料庫名稱
     * @var String
     */
    private $dataBase;
    /**
     * 資料表名稱
     * @var String
     */
    private $table;
    /**
     * 索引名稱
     * @var String
     */
    private $index;

    /**
     *
     * @param String $dataBase 資料庫名稱
     * @param String $table 資料表名稱
     * @param String $index 索引名稱
     */
    public function __construct($dataBase, $table, $index){
        
        $this->dataBase = $dataBase;
        $this->table    = $table;
        $this->index    = $index;
    }

    /**
     * 負責透過singleton的方式生產 Handler
     * @param String $dataBase 資料庫名稱
     * @param String $table 資料表名稱
     * @param String $index 索引名稱，預設 primary key
     * @return HandlerSocket $dbIndex 開啟index後的 HandlerSocket connection 
     */
    static public function factory($dataBase, $table, $index = HandlerSocket::PRIMARY){

        $poolKey = $dataBase . '*' . $table . '*' . $index;
        if(!isset(self::$dataBasePool[$poolKey])){
            self::$dataBasePool[$poolKey] = new Handler($dataBase, $table, $index);
        }

        return self::$dataBasePool[$poolKey];
    }
	
    /**
     * 透過HandlerSocketManager
     * 取得handlersocket 的instance
     * @param Array $column 想要操作的欄位列表
     * @param String $operating 操作模式 1 SELECT, 2 UPDATE, 3 INSERT, 4 DELETE
     * @param String $index 索引名稱，預設 primary key
     * @return HandlerSocket $instance 
     */
    private function getDBInstance($column, $operating){
        $fields = implode(',', $column);
        return HandlerSocketManager::factory($this->dataBase, $this->table, $fields, $this->index, $operating);
    }

    /**
     * 搜尋單一個資料列
     * @param Array $where
     * @param Array $column
     * @param String $op 操作方式，有以下選項, ‘=’, ‘>=’, ‘<=’, ‘>’, ‘<’, ‘+’。
     * @return Array $result
     */
    public function findOne($where, $column, $op = '='){
        
        $db = $this->getDBInstance($column, HandlerSocketManager::$OPERATING_SELECT);
        $rawResult = $db->executeSingle(HandlerSocketManager::$OPERATING_SELECT, $op, $where, 1, 0);
        return $this->packRow($rawResult[0], $column);
    }

    /**
     * 搜尋所有符合的資料列
     * @param Array $where 搜尋條件
     * @param Array $column 搜尋目標所需的欄位
     * @param String $op 操作方式，有以下選項, ‘=’, ‘>=’, ‘<=’, ‘>’, ‘<’, ‘+’。
     * @param Int $limit 搜尋結果列數的上限
     * @param Int $skip 搜尋資料前忽略掉的行数
     * @return Array $reslut 根據欄位名稱儲存對應值的array
     */
    public function findAll($where, $column, $op = '=', $limit = 999999, $skip = 0){
        $db = $this->getDBInstance($column, HandlerSocketManager::$OPERATING_SELECT);
        $rawResult = $db->executeSingle(HandlerSocketManager::$OPERATING_SELECT, $op, $where, $limit, $skip);
        return $this->packAllResult($rawResult, $column);
    }
    /**
     * 將所有搜尋結果包裝成相對應欄位的array
     * @param String $rawList 要打包的原始資料 
     * @param Int $column  相對應的欄位名稱
     * @return array $result
     */
    private function packAllResult($rawList, $column){
        $result = array();
        foreach($rawList as $raw){
            $result[] = $this->packRow($raw, $column);
        }
        return $result;
    }
    /**
     * 將單一搜尋結果包裝成相對應欄位的array
     * @param String $raw 要打包的原始資料 
     * @param Int $column  相對應的欄位名稱
     * @return array $result
     */
    private function packRow($raw, $column){

        $rawRowNum = count($raw);
        if($rawRowNum != count($column)){
            throw new Exception('fail: lost fields');
        }

        $packResult = array();
        for($i = 0;$i < $rawRowNum;$i++){
            $resultKey = $column[$i];
            $resultValue = $raw[$i];
            $packResult[$resultKey] = $resultValue;
        }

        return $packResult;
    }
}

?>