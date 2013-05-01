<?
/**
 * 主要負責 HandlerSocket 的連線管理
 * 透過singleton的方式管理connection pool
 * 由 factory function 製造 index
 */
class HandlerSocketManager extends HandlerSocket {

    /**
     * 跟資料庫連線的IP 列表
     * @var array
     */
    private $cluster;
    /**
     * 讀取與寫入使用的port r:read, w:write
     * @var array
     */
    private $portList = array('r'=>9998, 'w'=>9999);

    /**
     * 負責暫存 handlersocket 的index 儲存池
     * 主要是避免重復開啓 index
     * @var array
     */
    static private $indexPool = array();
    /**
     * 負責儲存 handlersocket 的 singleton 儲存池
     * @var array
     */
    static private $dbPool = array();

    /**
     * 對handlersocket 執行讀取操作的常數標誌
     * @var array
     */
    static public $OPERATING_SELECT = 1;
    /**
     * 對handlersocket 執行更新操作的常數標誌
     * @var array
     */
    static public $OPERATING_UPDATE = 2;
    /**
     * 對handlersocket 執行新增操作的常數標誌
     * @var array
     */
    static public $OPERATING_INSERT = 3;
    /**
     * 對handlersocket 執行刪除操作的常數標誌
     * @var array
     */
    static public $OPERATING_DELETE = 4;

    public function __construct($dataBase, $mode){
        
        if(!array_key_exists($mode, $this->portList)){
            throw new Exception('mode fail');
        }

        $this->init();
        $host = $this->gethost($mode);
        $port = $this->portList[$mode];

        parent::__construct($host, $port);
    }

    private function init(){

        $master = '192.168.1.1';
        $slave = array(
            array('host' => '192.168.1.2', 'proportion' => 1),
            array('host' => '192.168.1.3', 'proportion' => 1)
        );

        $this->cluster['master'] = $master;
        $this->cluster['slave'] = $slave;
    }

    /**
     * 透過data base name 取得host
     * @param String $mode 'w' => write or 'r' => read 
     * @return String $host data base server ip
     **/
    private function getHost($mode = 'r'){

        if($mode == 'r'){
            $hostCandidate = $this->selectHost($this->cluster['slave']);
            if(false != $hostCandidate){
                $host = $hostCandidate;
            }
        }

        if(empty($host)){
            $host = $this->cluster['master'];
        }

        return $host;
    }

    /**
     * select one host from cluster
     * @param Array $cluster
     * @return String the host ip address
     **/
    private function selectHost($cluster){

        $tempCandidate=array();
        if(is_array($cluster)){
            foreach($cluster as $candidate){
                for($i=0 ; $i < $candidate['proportion']; $i++){
                    $tempCandidate[] = $candidate['host'];  
                } 
            }
        }else{
            return false;
        }

        if(0 == count($tempCandidate)){
            return false;
        }

        return $tempCandidate[array_rand($tempCandidate)];
    }

    /**
     *  產生 handlersocket->openIndex
     * @param String $dataBase 資料庫名稱
     * @param String $table 資料表明稱
     * @param String $column 欄位名稱, 用逗號分隔
     * @param String $index 索引名, 可以是手动创建的索引名。这个参数可为空，一般指定时是用于 SELECT
     * @param int    $operating 1 SELECT, 2 UPDATE, 3 INSERT, 4 DELETE
     * @param HandlerSocket $result 
     * @return HandlerSocket $dbIndex 開啟index後的 HandlerSocket connection 
     **/
    static public function factory($dataBase, $table, $column, $index, $operating){

        $mode = 'w';
        if($operating == self::$OPERATING_SELECT){
            $mode = 'r';
        }

        $dbPoolKey = $dataBase . '*' . $table;
        $keyPoolKey = $dbPoolKey. '*' . $column . '*' . $index . '*' . $operating;
        
        if(!array_key_exists($dbPoolKey, self::$indexPool) || self::$indexPool[$dbPoolKey]['indexPoolKey'] != $keyPoolKey){

            $db = self::getDBInstance($dataBase, $mode);
            $result = $db->openIndex($operating, $dataBase, $table, $index, $column);
            $dbInfo = array('indexPoolKey'=>$keyPoolKey, 'db'=>$db);
            self::$indexPool[$dbPoolKey] = $dbInfo;
        }

        return self::$indexPool[$dbPoolKey]['db'];
    }

    /**
	 * 透過singleton 的方式取得 HandlerSocket instance
	 * @param String $dataBase 資料庫名稱
	 * @param Char   $mode 操作方式 r: 讀, w: 寫
     * @return HandlerSocket $instance 
     **/
    static public function getDBInstance($dataBase, $mode = 'r'){

        $poolKey = $dataBase . '*' . $mode;
        if(!isset(self::$dbPool[$poolKey])){
            self::$dbPool[$poolKey] = new HandlerSocketManager($dataBase, $mode);
        }

        return self::$dbPool[$poolKey];
    }
}
?>