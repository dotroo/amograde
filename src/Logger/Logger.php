<?php 

namespace Logger;

class Logger 
{
    public static $PATH;
    protected static $loggers=array();
 
    protected $name;
    protected $file;
    protected $fp;

    public function __construct($name, $file=null)
    {
        $this->name=$name;
        $this->file=$file;
     
        $this->open();
    }
     
    public function open()
    {
        if(self::$PATH == null) {
            return;
        }
     
        $this->fp = fopen($this->file == null ? self::$PATH.'/'.$this->name.'.log' : self::$PATH.'/'.$this->file,'a+');
    }

    public static function getLogger($name = 'root', $file = null)
    {
        if(!isset(self::$loggers[$name])) {
            self::$loggers[$name] = new Logger($name, $file);
        }
     
        return self::$loggers[$name];
    }

    public function log($message)
    {
        if(!is_string($message)) {
            $this->logPrint($message);
            return;
        }
     
        $log = '';
            // зафиксируем дату и время происходящего
        $log .= '['.date('D M d H:i:s Y',time()).'] ';
        // если мы отправили в функцию больше одного параметра,
            // выведем их тоже
        if(func_num_args()>1){
            $params = func_get_args();
     
            $message = call_user_func_array('sprintf',$params);
        }
     
        $log .= $message;
        $log .= "\n";
        // запись в файл
        $this->_write($log);
    }
     
    public function logPrint($obj){
            // заносим все выводимые данные в буфер
        ob_start();
     
        print_r($obj);
        // очищаем буфер
        $ob = ob_get_clean();
 
        // записываем
        $this->log($ob);
    }
     
    protected function _write($string){
     
        fwrite($this->fp, $string);
     
    }
     
    // деструктор
    public function __destruct(){
        fclose($this->fp);
    }
}