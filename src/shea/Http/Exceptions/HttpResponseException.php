<?php


namespace Shea\Http\Exceptions;


use Symfony\Component\HttpFoundation\Response;

class HttpResponseException extends \RuntimeException
{
    /**
     * response 实例
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $response;

    /**
     * HttpResponseException constructor.
     * @param Response $response
     * @return void
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}