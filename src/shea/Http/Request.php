<?php


namespace Shea\Http;


use Shea\Contracts\Support\Arrayable;
use Shea\Support\Arr;
use \Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request extends SymfonyRequest implements \ArrayAccess,Arrayable
{
    /**
     * 解码 request 的 json 数据
     * @var \Symfony\Component\HttpFoundation\ParameterBag|null
     */
    protected $json;

    /**
     * 转换 request 的文件
     * @var array
     */
    protected $convertedFiles;

    /**
     * 用户解析回调
     * @var \Closure
     */
    protected $userResolver;

    /**
     * 路由解析回调
     * @var \Closure
     */
    protected $routeResolver;

    /**
     * 从 server 变量中创建一个新的 http 请求
     * @return static
     */
    public static function capture()
    {
        static::enableHttpMethodParameterOverride();

        return static::createFromBase(SymfonyRequest::createFromGlobals());
    }

    /**
     * @return $this
     */
    public function instance()
    {
        return $this;
    }

    /**
     * 获取 request 方法
     * @return string
     */
    public function method()
    {
        return $this->getMethod();
    }

    /**
     * get the root url for the application
     * @return string
     */
    public function root()
    {
        return rtrim($this->getSchemeAndHttpHost().$this->getBaseUrl(),'/');
    }

    /**
     * get the url (no query string) for the request
     * @return string
     */
    public function url()
    {
        return rtrim(preg_replace('/\?.*/','',$this->getUri()),'/');
    }

    /**
     * get the full url for the request
     * @return string
     */
    public function fullUrl()
    {
        $query = $this->getQueryString();

        $question = $this->getBaseUrl().$this->getPathInfo() === '/' ? '/?' : '?';

        return $query ? $this->url().$question.$query : $this->url();
    }

    public function fullUrlWithQuery(array $query)
    {
        $question = $this->getBaseUrl().$this->getPathInfo() === '/' ? '/?' : '?';

        // 这个 query 函数不知道什么意思
        return count($this->query()) > 0
            ? $this->url().$question.Arr::query(array_merge($this->query(),$query))
            : $this->fullUrl().$question.Arr::query($query);
    }

}