<?php
namespace Jmcc\Meiya;

use Jmcc\Meiya\Traits\HasHttpRequests;

abstract class BaseClient
{
    use HasHttpRequests {request as performRequest;}

    protected $accessToken;

    public function __construct()
    {
        $this->init();
    }

    abstract protected function init(): void;

    abstract protected function getToken();

    public function setHeader(array $header = [])
    {
        return $header;
    }

    public function httpGet(string $url, array $query = [], array $header = [])
    {
        $headers = $this->setHeader($header);
        $response = $this->request($url, 'GET', ['headers' => $headers, 'query' => $query]);
        return \json_decode($response->getBody()->getContents());
    }

    public function httpPost(string $url, array $data = [], array $header = [])
    {
        $headers = $this->setHeader($header);
        $response = $this->request($url, 'POST', ['headers' => $headers, 'form_params' => $data]);
        return \json_decode($response->getBody()->getContents());
    }

    public function httpPostJson(string $url, array $data = [], array $header = [])
    {
        $headers = $this->setHeader($header);
        $response = $this->request($url, 'POST', ['headers' => $headers, 'json' => $data]);
        return \json_decode($response->getBody()->getContents());
    }

    public function httpUpload(string $url, array $files = [], array $form = [], array $query = [])
    {
        $multipart = [];

        foreach ($files as $name => $path) {
            $multipart[] = [
                'name' => $name,
                'contents' => fopen($path, 'r'),
            ];
        }

        foreach ($form as $name => $contents) {
            $multipart[] = compact('name', 'contents');
        }

        return $this->request($url, 'POST', ['query' => $query, 'multipart' => $multipart, 'connect_timeout' => 30, 'timeout' => 30, 'read_timeout' => 30]);
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function request(string $url, string $method = 'GET', array $options = [])
    {

        $response = $this->performRequest($url, $method, $options);

        return $response;
    }

}
