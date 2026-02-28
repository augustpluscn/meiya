<?php

namespace Jmcc\Meiya;

use Illuminate\Support\Facades\Cache;

class Login extends BaseClient
{
    protected $baseUri;
    protected $signkey;
    protected $staffCode;
    protected $realName;
    protected $passwordType;
    protected $timeStamp;
    protected $companyId;

    protected $token;

    public function __construct($staffCode, $realName, $passwordType, $companyId)
    {
        $this->init();

        $this->staffCode = $staffCode;
        $this->realName = $realName;
        $this->passwordType = $passwordType;
        $this->companyId = $companyId;
    }

    protected function init(): void
    {
        $this->baseUri = config('meiya.uri');
        $this->signkey = config('meiya.signkey');

        $now = microtime(true);
        $milli = sprintf("%04d", ($now - floor($now)) * 10000); // 获取 4 位毫秒/微秒部分
        $this->timeStamp = date("YmdHis", $now) . $milli;

        $this->accessToken = $this->getToken();
    }

    protected function getToken()
    {
        $token = "";
        $tokenKey = $this->companyId . '-' . $this->staffCode;
        if (Cache::has('accessToken-' . $tokenKey)) {
            //存在缓存
            $token = Cache::get('accessToken-' . $tokenKey);
        } else {
            $password = $this->getPassword();

            $data = [
                'password' => $password,
                'staffCode' => $this->staffCode,
                'timeStamp' => $this->timeStamp,
                'realName' => $this->realName,
                'passwordType' => $this->passwordType,
                'companyId' => $this->companyId,
            ];

            $url = 'Login';

            $response = $this->request($url, 'POST', ['form_params' => $data]);
            $body = \json_decode($response->getBody()->getContents());

            if ($body->code != 10000) {
                throw new \Exception($body->description);
            }
            $token = $body->sessionId;
            \Cache::put('accessToken-' . $tokenKey, $token, now()->endOfDay());
        }

        return $token;
    }

    protected function getPassword()
    {
        // 顺序：companyId, staffCode, realName, timeStamp, signkey
        $infos = [$this->companyId, $this->staffCode, $this->realName, $this->timeStamp, $this->signkey];

        // 拼接字符串
        $input = implode("", $infos);

        /**
         * SHA1 加密 (Encoding.UTF8)
         */
        $password = sha1($input);
        return $password;
    }

    public function setHeader(array $header = [])
    {
        $reg = Cache::get('reg');
        $rsa = new Rsa('', '', '', $reg->spk);
        $userEncrypt = $rsa->publicEncrypt((string)$this->userid);

        $arr = [
            'appid' => $this->appId,
            'token' => $this->accessToken,
            'userid' => $userEncrypt,
            // 'Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8',
        ];
        return array_merge($arr, $header);
    }
}
