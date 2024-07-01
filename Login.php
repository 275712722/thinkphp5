<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;

class Login extends Controller
{
    //获取Access_Token
    // public function getWxAccessToken(){
    //     $res = json_decode(cache('access_token'),true);//获取缓存
    //     if($res !== null && $res["access_token"] && $res["expires_in"]>time()){
    //         return $res['access_token'];
    //     }else {
    //         $lturl="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx4b9ddc3b84ff51c3&secret=11e55796decb2ffc8cbd1821a1047a59";
    //         //初始化
    //         $ch = curl_init();
    //         //2.设置url 的参数
    //         curl_setopt($ch,CURLOPT_URL,$lturl);
    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //         //不需要安全检验证书
    //         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    //         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    //         //3.采集
    //         $res = curl_exec($ch);
    //         if ( curl_errno($ch)){
    //             var_dump(curl_error($ch));
    //         }
    //         //4.关闭
    //         curl_close($ch);
    //         $arr = json_decode($res,true);
    //         if($arr !== null && $arr['access_token'] ){
    //             $arr['expires_in'] = $arr['expires_in'] +time();
    //             //将access_token 全局缓存 
    //             cache('access_token',json_encode($arr));
    //             return $arr['access_token'];
    //         }else {
    //             exit('微信获取acess_token 失败');
    //         } 
    //         var_dump($arr);
    //     }     
    // }
    
    public function getWxAccessToken(){
        $res = json_decode(cache('access_token'), true); // 获取缓存
    
        // 判断缓存是否存在以及是否在有效期内
        if ($res !== null && isset($res["access_token"]) && $res["expires_in"] > time()) {
            return $res['access_token'];
        } else {
            $lturl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx4b9ddc3b84ff51c3&secret=11e55796decb2ffc8cbd1821a1047a59";
    
            // 初始化
            $ch = curl_init();
            // 设置url的参数
            curl_setopt($ch, CURLOPT_URL, $lturl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // 不需要安全检验证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    
            // 采集
            $res = curl_exec($ch);
            if (curl_errno($ch)) {
                var_dump(curl_error($ch));
            }
            // 关闭
            curl_close($ch);
    
            $arr = json_decode($res, true);
            if ($arr !== null && isset($arr['access_token'])) {
                // 设置过期时间为当前时间加上expires_in（微信返回的秒数）
                $arr['expires_in'] = time() + $arr['expires_in'];
                // 将access_token缓存，并设置缓存时间不超过7000秒
                cache('access_token', json_encode($arr), 7000);
                return $arr['access_token'];
            } else {
                exit('微信获取access_token失败');
            }
            // var_dump($arr);
            // return $arr;
        }
    }
     
    public function getJsapiTicket() {
        $res = json_decode(cache('jsapi_ticket'), true); // 获取缓存
    
        // 判断缓存是否存在以及是否在有效期内
        if ($res !== null && isset($res["ticket"]) && $res["expires_in"] > time()) {
            return $res['ticket'];
        } else {
            $accessToken = $this->getWxAccessToken();
            $jsapiUrl = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token={$accessToken}";
    
            // 初始化
            $ch = curl_init();
            // 设置url的参数
            curl_setopt($ch, CURLOPT_URL, $jsapiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // 不需要安全检验证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    
            // 采集
            $res = curl_exec($ch);
            if (curl_errno($ch)) {
                var_dump(curl_error($ch));
            }
            // 关闭
            curl_close($ch);
    
            $arr = json_decode($res, true);
    
            // 打印微信API返回状态
            echo "微信API返回状态: ";
            var_dump($arr);
    
            if ($arr !== null && isset($arr['ticket'])) {
                // 设置过期时间为当前时间加上expires_in（微信返回的秒数）
                $arr['expires_in'] = time() + $arr['expires_in'];
                // 将jsapi_ticket缓存，并设置缓存时间不超过7000秒
                cache('jsapi_ticket', json_encode($arr), 7000);
                return $arr['ticket'];
            } else {
                exit('微信获取jsapi_ticket失败');
            }
        }
        var_dump($arr);
    }
    
    
    public function getSignature($url) {
        $arr = array($url => '', 'msg' => '获取失败');
        $jsapiTicket = $this->getJsapiTicket();
        $nonceStr = $this->generateNonceStr();
        $timestamp = time();
        
        // 排列并拼接参数
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        
        // 生成签名
        $signature = sha1($string);
        
        // var_dump($jsapiTicket,$nonceStr,$timestamp,$string,$signature);
        
        $data = [
            'url' => $url,
            'jsapi_ticket' => $jsapiTicket,
            'nonceStr' => $nonceStr,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];
        // header("Access-Control-Allow-Origin: *");
        
        
        if($url !==''){
			header("Access-Control-Allow-Origin: *");
			$res =  $data;
            $arr['msg'] = '获取成功';
            $arr['data'] = array(
                'url' => $res['url'],
                'jsapi_ticket' => $res['jsapi_ticket'],
                'nonceStr' => $res['nonceStr'],
                'timestamp' => $res['timestamp'],
                'signature' => $res['signature'],
            );
        }
        
        return json($arr);
        
        // return json_encode($data);
        
    }
    
    
    // 生成随机字符串
    private function generateNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    
}
