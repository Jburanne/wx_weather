<?php

define("TOKEN","haha");
$wxobj = new weixin();
$wxobj->checkSignature();
$wxobj->responseMsg();

class weixin{
    public function checkSignature(){
    //获得参数 signature nonce token timestamp echostr
    $nonce     = $_GET['nonce'];
    $token     = 'haha';
    $timestamp = $_GET['timestamp'];
    $echostr   = $_GET['echostr'];
    $signature = $_GET['signature'];
    //形成数组，然后按字典序排序
    $array = array();
    $array = array($nonce, $timestamp, $token);
    sort($array);
    //拼接成字符串,sha1加密 ，然后与signature进行校验
    $str = sha1( implode( $array ) );
    if( $str == $signature && $echostr ){
        //第一次接入weixin api接口的时候
        echo  $echostr;
        exit;
    }       
 }
  
	public function responseMsg(){
    	//1.获取到微信推送过来post数据（xml格式）
        $postArr = $GLOBALS['HTTP_RAW_POST_DATA'];
        //2.处理消息类型，并设置回复类型和内容
        $postObj = simplexml_load_string( $postArr );
      	switch(strtolower( $postObj->MsgType))
        {
          case "text":
            $resultStr = $this->handleText($postObj);
            break;
          case "event":
            $resultStr = $this->handleEvent($postObj);
            break;      
          default:
            $resultStr="";
            break;
        }
      	echo $resultStr;     	
    
    }
  
  	public function handleText($postObj){
    	$content = $postObj->Content;
      	$kword = mb_substr($content,-2,2,'UTF-8');
        $city=mb_substr($content,0,-2,'UTF-8');
      	if ($kword == "天气"){ 
          	$city_json=$this->getcity($city);
          	$city_code=$city_json->data;
          	$weather_json=$this->getweather($city_code);
            $weather_info=$weather_json->data;
          	$content=$city."今日天气：".$weather_info->climate."。最高温度:".$weather_info->temperatureH."。最低温度:".$weather_info->temperatureL."。风速：".$weather_info->wind;
     	 }      
     	 $resultStr = $this->responseText($postObj, $content);
     	 echo $resultStr;
     	 return $resultStr;   
    }
  
	public function handleEvent($postObj){
    	$contentStr="";
      	switch($postObj->Event)
        {
          case "subscribe":
            $contentStr="感谢您关注我们的微信公众号";
            break;
          default:
            $contentStr="Unknown Event".$postObj->Event;
            break;      
        }
      	$resultStr=$this->responseText($postObj,$contentStr);
      	echo $resultStr;
      	return $resultStr;      	   	
    }
  
  	public function responseText($postObj,$content){
    	$toUser   = $postObj->FromUserName;
        $fromUser = $postObj->ToUserName;
        $time     = time();
        $msgType  =  'text';
        $template = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                    </xml>";
        $info  = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
        return $info;
    
    }    
  
  public function getCity($city_name){
  		$url_get = 'http://192.144.139.121/city/'.$city_name;
    	$city_json=file_get_contents($url_get);
    	//var_dump($city_json['data']);
    	return (json_decode($city_json));   
  }
  public function getWeather($city_code){
    $url_get='http://192.144.139.121/weather/'.$city_code;
    $weather_json=file_get_contents($url_get);
    //var_dump($weather_json);
    return (json_decode($weather_json));
  }
}