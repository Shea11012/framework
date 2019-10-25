<?php


namespace Shea\Contracts\Http;

interface Kernel
{
    /**
     * 启动 http
     * @return void
     */
    public function bootstrap();

    /**
     * 处理到来的 http 请求
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request);

    /**
     * 终止请求
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return void
     */
    public function terminate($request,$response);

    /**
     * 获取应用实例
     * @return \Shea\Container\Container;
     */
    public function getApplication();
}