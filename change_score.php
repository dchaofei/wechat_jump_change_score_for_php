<?php

Class ChangeScore
{
    public $score;
    public $session_id;
    public $base_req;

    private $base_site = "https://mp.weixin.qq.com/wxagame/";
    private $path = '';
    private $version = 9;
    private $times = 258;
    private $header;
    private $action = [], $musicList = [], $touchList = [];
    private $req = [];

    public function __construct()
    {
        $configs = require './config.php';
        $this->score = $configs['score'];
        $this->session_id = $configs['session_id'];

        $this->req = [
            "base_req" => [
                "session_id" => $this->session_id,
                "fast"       => 1,
            ],
        ];

        $this->base_req = $this->extend($this->req);
        $this->path     = 'wxagame_settlement';
        $this->simulationSteps();
        $this->changeScore();


    }

    /**
     * 请求微信接口
     * @param $url
     * @return mixed
     */
    public function request($url)
    {
        $this->header = [
            "User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 11_2_1 like Mac OS X) AppleWebKit/604.4.7 (KHTML, like Gecko) Mobile/15C153 MicroMessenger/6.6.1 NetType/WIFI Language/zh_CN",
            "Referer: https://servicewechat.com/wx7c8d593b2c3a7703/{$this->version}/page-frame.html",
            "Content-Type: application/json",
            "Accept-Language: zh-cn",
            "Accept': */*"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->base_req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $res = curl_exec($ch);
        curl_close($ch);
        //var_dump($this->base_req);exit;
        return json_decode($res, true);
    }

    /**
     * 获取时间戳毫秒数
     * @return float
     */
    private function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    /**
     * aes 加密
     * @param $data
     * @param $originKey
     * @return string
     */
    private function encrypt($data, $originKey)
    {
        $data      = str_replace("\\\\", "\\", json_encode($data, JSON_UNESCAPED_SLASHES));
        $originKey = substr($originKey, 0, 16);
        $key       = $originKey;
        $iv        = $originKey;
        $blocksize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $len       = strlen($data); //取得字符串长度
        $pad       = $blocksize - ($len % $blocksize); //取得补码的长度
        $data      .= str_repeat(chr($pad), $pad); // 填充
        $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);
        $res       = base64_encode($crypttext);
        return $res;
    }

    private function extend($data)
    {
        return str_replace("\\\\", "\\", json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    private function simulationSteps()
    {
        for ($i = round(10000 + lcg_value(0, 1) * 2000); $i > 0; $i--) {
            array_push($this->action, [number_format(lcg_value(0, 1), 3), number_format(lcg_value(0, 1) * 2, 2), $i / 5000 == 0 ? true : false]);
            array_push($this->musicList, false);
            array_push($this->touchList, [number_format((250 - lcg_value(0, 1) * 10), 4), number_format((670 - lcg_value(0, 1) * 20), 4)]);
        }
    }

    private function returnUrl()
    {
        return $this->base_site . $this->path;
    }

    private function getUserInfo()
    {
        $this->path = 'wxagame_getfriendsscore';
        return $this->request($this->returnUrl());
    }

    private function changeScore()
    {
        $data = [
            "score"     => $this->score,
            "times"     => $this->times,
            "game_data" => json_encode([
                "seed"      => 2018,
                "action"    => $this->action,
                "musicList" => $this->musicList,
                "touchList" => $this->touchList,
                "version"   => 1,
            ]),
        ];


        $this->base_req = $this->extend(array_merge([
            "action_data" => $this->encrypt($data, $this->session_id),
        ], $this->req));

        $res = $this->request($this->returnUrl());
        $userInfo = $this->getUserInfo()['my_user_info'];

        if ($res['base_resp']['errcode'] == 0) {
            echo "修改成功" . PHP_EOL;
            echo "name: {$userInfo['nickname']}" . PHP_EOL;
            echo "score: {$this->score}" . PHP_EOL;
            echo "week_best_score: {$userInfo['week_best_score']}" . PHP_EOL;
        }
    }
}

new ChangeScore();