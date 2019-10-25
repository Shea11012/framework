<?php


namespace Shea\Contracts\Routing;


interface ResponseFactory
{
    /**
     * 创建一个 json response 实例
     * @param array|string|object $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return \Shea\Http\JsonResponse
     */
    public function json($data = [],$status = 200,array $headers = [],$options = 0);
}