<?php
/**
 * @abstract 用于转换bearychat机器人和图灵机器人之间的转换
 * @author aishan
 * @date 2015-10-26
 * Bearychat：https://bearychat.com/
 * 图灵机器人：http://www.tuling123.com
 * 由于bearychat的outgoing机器人是post指定类型数据到某处，而图灵机器人接收信息是get方式，并且图灵机器人返回的数据形式之于Bearychat机器人
 * 需要接收的返回数据类型也是有一定的出入，所以需要此文件做个中转
 */
require_once 'curl.php';//引入curl类
$content = file_get_contents("php://input");//读取bearychat机器人post过来的数据
$content_arr=json_decode($content);//json转对象
$info=urlencode(substr($content_arr->text,11));//获取传过来的内容中的text字段并截取实际内容部分，去掉触发bearychat机器人的前缀
$key='2d6***********************c4e8ad52';// 图灵机器人网站获取的key
$request_url="http://www.tuling123.com/openapi/api?key=".$key."&info=".$info;//拼接图灵机器人所需请求url
$curl=new CURL();
$request=$curl->vget($request_url);//发送请求
$request=json_decode($request,1);//将请求转换成数组，由于图灵机器人的菜谱、列车等查询会涉及到返回list形式数据，而bearychat机器人默认不能处理，故将图灵机器人返回的list数据转化拼接到text中
if(isset($request['list'])){//如果有list形式数据，则进行转换处理
    $list=$request['list'];
    $text=$request['text'].PHP_EOL;//取到text数据，开始拼接
    for($i=1;$i<=3;$i++){//在list中的数据可能会有很多，为了bearychat机器人返回数据不至于过多，将最多只取前三条数据
        if(!isset($list[$i])){
            break;
        }
        $val=$list[$i];
        $list_key=array_keys($val);
        foreach($list_key as $key_val){
            if(!in_array($key_val,array('icon'))){//由于icon图标在bearychat上无法显示，所以此处直接抛弃
                $text.=$val[$key_val].PHP_EOL;
            }
        }
    }

    $request['text']=$text;//将拼接好的text数据赋值
    unset($request['list']);//丢弃list数据
}

if(isset($request['url'])){//此处是当询问图灵机器人“xxx的照片”，会返回url形式数据，也拼接到text之后
    $text=$request['text'].PHP_EOL.$request['url'];
    $request['text']=$text;
    unset($request['url']);
}
$request['text']=str_replace('<br>',PHP_EOL,$request['text']);//最后图灵机器人返回的text数据会以<br>作为换行，而<br>在bearychat中不被转义，所以此处要将<br>标签替换
echo json_encode($request);



