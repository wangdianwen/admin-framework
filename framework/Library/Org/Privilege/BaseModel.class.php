<?php
namespace Org\Privilege;
use Think\Model;
class BaseModel extends Model{
    //调用配置文件中的数据库配置1
    public $app_name = '';
    public $user_email = '';
    public $is_super = false;

    protected $connection = 'DB_PRIVILEGE';
    protected $autoCheckFields = false; 
    public function __construct(){
        parent::__construct();

        $this->init();
    }
    public function init(){
        $this->app_name = C("APP_NAME");
        $this->name = I("session.name");
        $this->is_super = array_key_exists($this->name, C("SUPER_ADMIN")) ? true : false;
    }

    public function _curl($url, $post=null, $header=array(),$config=array()) {                                                                                     
        $ch = curl_init();
        if(!empty($post)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        } else {
            curl_setopt($ch, CURLOPT_POST, false);
        }   
        if(!empty($header)) {
            curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
        }                         
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        curl_setopt($ch, CURLOPT_URL, $url);

        $data = curl_exec($ch);
        //$info = curl_getinfo($ch);
        return $data;
    }

    function encode($string, $skey = 'majinlahusong') {
        $strArr = str_split(base64_encode($string));
        $strCount = count($strArr);
        foreach (str_split($skey) as $key => $value)
            $key < $strCount && $strArr[$key].=$value;
        return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
    }

    function decode($string, $skey = 'majinlahusong') {
        $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
        $strCount = count($strArr);
        foreach (str_split($skey) as $key => $value)
            $key <= $strCount  && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
        return base64_decode(join('', $strArr));
    }

}
