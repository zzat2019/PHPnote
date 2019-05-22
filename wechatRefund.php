<?php
/**
 * Created by PhpStorm.
 * User: "zz"
 * Date: 2019/5/22 0022
 * Time: 9:42
 */
class wechatRefund
{
//向外暴露的微信退款接口
    public function refund()
    {
        $result = $this->weChatrefund();
        return $result;
        /*
        * <xml>
              <return_code><![CDATA[SUCCESS]]></return_code>
              <return_msg><![CDATA[OK]]></return_msg>
              <appid><![CDATA[wx2421b1c4370ec43b]]></appid>
              <mch_id><![CDATA[10000100]]></mch_id>
              <nonce_str><![CDATA[NfsMFbUFpdbEhPXP]]></nonce_str>
              <sign><![CDATA[B7274EB9F8925EB93100DD2085FA56C0]]></sign>
              <result_code><![CDATA[SUCCESS]]></result_code>
              <transaction_id><![CDATA[1008450740201411110005820873]]></transaction_id>
              <out_trade_no><![CDATA[1415757673]]></out_trade_no>
              <out_refund_no><![CDATA[1415701182]]></out_refund_no>
              <refund_id><![CDATA[2008450740201411110000174436]]></refund_id>
              <refund_channel><![CDATA[]]></refund_channel>
              <refund_fee>1</refund_fee>
           </xml>
           */
        if ($result['return_code'] == 'SUCCESS') {
            //退款申请成功
        }
    }

    private function weChatrefund()
    {
        $parma = array(
            'appid' => $this->APPID,
            'mch_id' => $this->MCHID,
            'nonce_str' => $this->createNoncestr(),
            'out_refund_no' => $this->outRefundNo,
            'transaction_id' => $this->transactionId,//微信订单号
            'total_fee' => $this->totalFee,
            'refund_fee' => $this->refundFee,
        );
        $parma['sign'] = $this->getSign($parma);
        $xmldata = $this->arrayToXml($parma);
        $xmlresult = $this->postXmlSSLCurl($xmldata, 'https://api.mch.weixin.qq.com/secapi/pay/refund');
        $result = $this->xmlToArray($xmlresult);
        return $result;
    }

    /*
     * 对要发送到微信统一下单接口的数据进行签名
     */
    protected function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->KEY;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    /*
     *排序并格式化参数方法，签名时需要使用
     */
    protected function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = "";
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    /*
     * 生成随机字符串方法
     */
    protected function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

//数组转字符串方法
    protected function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    protected static function xmlToArray($xml)
    {
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

//需要使用证书的请求
    private function postXmlSSLCurl($xml, $url, $second = 30)
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, '../../' . $this->SSLCERT_PATH);
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, '../../' . $this->SSLKEY_PATH);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            curl_close($ch);
            return false;
        }
    }

}