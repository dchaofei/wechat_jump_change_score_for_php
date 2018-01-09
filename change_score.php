<?php

Class ChangeScore
{
    public $score;
    public $session_id;
    public $base_req;

    private $base_site = "https://mp.weixin.qq.com/wxagame/";
    private $path = '';
    private $version = 6;
    private $times = 265;
    private $startTime;
    private $endTime;
    private $header;
    private $action = [], $musicList = [], $touchList = [], $data = [];
    private $steps = [], $touchMoveList = [], $timestamp = [], $game_data = [];
    private $req = [];
    private $bestscore;

    public function __construct()
    {
        $configs          = require './config.php';
        $this->score      = $configs['score'];
        $this->session_id = $configs['session_id'];

        $this->req = [
            "base_req" => [
                "session_id" => $this->session_id,
                "fast"       => 1,
            ],
        ];

        //$this->times = rand(100, 300);
        /*$version = [
            '3' => 3,
            '6' => 6,
            '9' => 9,
        ];*/
        //$this->version = array_rand($version);
        var_dump($this->version);

        $this->base_req = $this->extend($this->req);
        $this->times = $this->getUserInfo()['my_user_info']['times'] + 1;
        //var_dump($this->getUserInfo());exit;
        $this->path     = 'wxagame_settlement';
        $this->simulationSteps();
        $this->changeScore();
        $this->bottlereport();


    }

    /**
     * 请求微信接口
     * @param $url
     * @return mixed
     */
    public function request($url)
    {
        $this->header = [
            //"User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 11_2_1 like Mac OS X) AppleWebKit/604.4.7 (KHTML, like Gecko) Mobile/15C153 MicroMessenger/6.6.1 NetType/WIFI Language/zh_CN",
            "User-Agent: MicroMessenger/6.6.1.1220(0x26060134) NetType/4G Language/zh_CN",
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
        /*for ($i = round(10000 + lcg_value(0, 1) * 2000); $i > 0; $i--) {
            array_push($this->action, [number_format(lcg_value(0, 1), 3), number_format(lcg_value(0, 1) * 2, 2), $i / 5000 == 0 ? true : false]);
            array_push($this->musicList, false);
            array_push($this->touchList, [number_format((250 - lcg_value(0, 1) * 10), 4), number_format((670 - lcg_value(0, 1) * 20), 4)]);
        }*/
        $currentScore = 0;
        $perScore = 1;
        $addScore = 0;
        $succeedTime = 0;
        $mouseDownTime;
        $order = 15;
        $StayTime;
        $musicScore = false;
        $OrderList = [];
        $IsDouble = [];
        $Count = 0;

        for ($i = 0; $i < 100; $i++) {
            if ($i < 60) {
                array_push($OrderList, 15);
                array_push($IsDouble, false);
            } else {
                if ($i < 78) {
                    array_push($IsDouble, false);
                    array_push($OrderList, 26);
                } elseif ($i < 86) {
                    array_push($IsDouble, false);
                    array_push($OrderList, 17);
                } elseif ($i < 95) {
                    array_push($IsDouble, true);
                    array_push($OrderList, 24);
                } else {
                    array_push($IsDouble, true);
                    array_push($OrderList, 19);
                }
            }
        }

        $startTime = time();

        do {
            $stoptime = rand(200, 500);
            $t = rand(300, 1000);
            $d = lcg_value(0, 1) * 4 / 1000 + 1.88;
            $duration = $t / 1000;
            $o = rand(0, 99);
            $order = $OrderList[$o];
            if ($order != 15) {
                if ($Count < 4) {
                    $order = 15;
                    $StayTime = 0;
                } else {
                    $musicScore = true;
                    $StayTime = rand(2000, 3000);
                }
            } else {
                $perScore = 1;
            }

            $calY = round(2.75 - $d * $duration, 2);
            array_push($this->action, [$duration, $calY, false]);
            array_push($this->musicList, $musicScore);
            $x = rand(230, 245);
            $y = rand(500, 530);

            $touch_x = $x + ($x % 4) * 0.25;
            $touch_y = $y + ($y % 4) * 0.25;
            array_push($this->touchList, [$touch_x, $touch_y]);

            if ($t < 410) {
                for ($l = 0; $l < 3; $l++) {
                    array_push($this->touchMoveList, $touch_x);
                    array_push($this->touchMoveList, $touch_y);
                }
            } elseif ($t < 450) {
                for ($l = 0; $l < 4; $l++) {
                    array_push($this->touchMoveList, $touch_x);
                    array_push($this->touchMoveList, $touch_y);
                }
            } else {
                for ($l = 0; $l < 5; $l++) {
                    array_push($this->touchMoveList, $touch_x);
                    array_push($this->touchMoveList, $touch_y);
                }
            }

            array_push($this->steps, $this->touchMoveList);

            if ($succeedTime == 0) {
                $succeedTime = $startTime;
            }

            $WaitTime = rand(1000, 3000);

            $mouseDownTime = $succeedTime + $StayTime + $WaitTime;

            array_push($this->timestamp, $mouseDownTime);

            $succeedTime = $mouseDownTime + round((135 + 15 * $duration) * 2000 / 720) + $t;

            switch ($order) {
                case 26:
                        $addScore=5;
                        break;
                case 17:
                        $addScore = 10;
                        break;
                case 24:
                        $addScore = 15;
                        break;
                case 19:
                        $addScore = 30;
                        break;
                default:
                        $addScore=0;
                        break;
            }

            $currentScore = $currentScore + $perScore+$addScore;
            $Count ++;
        } while ($currentScore <= $this->score);

        $s = $this->timestamp[$Count - 1] - $startTime + 200;

        for ($i = 0; $i < $Count; $i++) {
            $this->timestamp[$i] = $this->timestamp[$i] - $s;
        }

        $this->startTime  = $startTime - $s;
        $this->endTime = $succeedTime - $s;

        $seed = $startTime - $s;
        $this->game_data = json_encode([
            "seed" => $seed,
            "version"   => 2 ,
            "timestamp" => $this->timestamp,
            "action" => $this->action,
            "musicList" => $this->musicList,
            "touchList" => $this->touchList,
            "steps" => $this->steps,
        ]);

        $this->data = [
            "score"     => $this->score,
            "times"     => $this->times,
            'game_data' => $this->game_data,
        ];
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

    private function bottlereport()
    {
        $base_req = [
            "session_id" => $this->session_id,
            "fast" => 1,
            "client_info" => [
                "platform" => "android",
                "brand" => "SMARTISAN",
                "model" => "SM901",
                "system" => "Android 6.0.1"
            ]
        ];
        $ts1 = round((float)$this->startTime / 1000);
        $ts2 = round((float)$this->endTime / 1000);
        $this->base_req = json_encode([
            "base_req" => $base_req,
            "report_list" => [
                [
                    "ts" => $ts1,
                    "type" => 0,
                    "scene" => 1089,
                ],
                [
                    "ts" => $ts2,
                    "type" => 2,
                    "duration" => $ts2 - $ts1,
                    "best_score" => $this->bestscore,
                    "times" => $this->times,
                    "score" => $this->score,
                    "break_record" => $this->score > $this->bestscore ? 1 : 0,
                ],
            ],
        ]);

        $this->path = 'wxagame_bottlereport';
        return $this->request($this->returnUrl());
    }

    private function changeScore()
    {
        /*$data = [
            "score"     => $this->score,
            "times"     => $this->times,
            "game_data" => json_encode([
                "seed"      => 2018,
                "action"    => $this->action,
                "musicList" => $this->musicList,
                "touchList" => $this->touchList,
                "version"   => 1,
            ]),
        ];*/

        $this->base_req = $this->extend(array_merge([
            "action_data" => $this->encrypt($this->data, $this->session_id),
        ], $this->req));

        $res      = $this->request($this->returnUrl());
        var_dump($res);
        $userInfo = $this->getUserInfo()['my_user_info'];
        $this->bestscore = $userInfo['history_best_score'];
        var_dump($userInfo);

        if ($res['base_resp']['errcode'] == 0) {
            echo "修改成功" . PHP_EOL;
            echo "name: {$userInfo['nickname']}" . PHP_EOL;
            echo "score: {$this->score}" . PHP_EOL;
            echo "week_best_score: {$userInfo['week_best_score']}" . PHP_EOL;
        }
    }
}

new ChangeScore();