<?php


namespace Shea\Http;


use Shea\Contracts\Support\Arrayable;
use Shea\Http\Concerns\InteractsWithInput;
use Shea\Support\Arr;
use Shea\Support\Str;
use Symfony\Component\HttpFoundation\ParameterBag;
use \Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request extends SymfonyRequest implements \ArrayAccess,Arrayable
{
    use InteractsWithInput;
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
        return rtrim($this->getSchemeAndHttpHost() . $this->getBaseUrl(), '/');
    }

    /**
     * get the url (no query string) for the request
     * @return string
     */
    public function url()
    {
        return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
    }

    /**
     * get the full url for the request
     * @return string
     */
    public function fullUrl()
    {
        $query = $this->getQueryString();

        $question = $this->getBaseUrl() . $this->getPathInfo() === '/' ? '/?' : '?';

        return $query ? $this->url() . $question . $query : $this->url();
    }

    public function fullUrlWithQuery(array $query)
    {
        $question = $this->getBaseUrl() . $this->getPathInfo() === '/' ? '/?' : '?';

        return count($this->query()) > 0
            ? $this->url() . $question . Arr::query(array_merge($this->query(), $query))
            : $this->fullUrl() . $question . Arr::query($query);
    }

    /**
     * 获取当前的请求的 pathinfo
     * @return string
     */
    public function path()
    {
        $pattern = trim($this->getPathInfo(), '/');

        return $pattern == '' ? '/' : $pattern;
    }

    /**
     * 解码当前请求的 pathinfo 信息
     * @return string
     */
    public function decodePath()
    {
        return rawurldecode($this->path());
    }

    /**
     * 从 URI 获取分段
     * @param int $index
     * @param null $default
     * @return string|null
     */
    public function segment($index, $default = null)
    {
        return Arr::get($this->segments(), $index - 1, $default);
    }

    /**
     * 获取请求路径的所有分段
     * @return array
     */
    public function segments()
    {
        $segments = explode('/', $this->decodePath());

        return array_values(array_filter($segments, function ($value) {
            return $value !== '';
        }));
    }

    /**
     * 判断当前 URI 是否匹配一个 pattern
     * @param mixed $patterns
     * @return bool
     */
    public function is(...$patterns)
    {
        $path = $this->decodePath();

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断表达式是否匹配当前 request url 和 query string
     * @param mixed $patterns
     */
    public function fullUrlIs(...$patterns)
    {
        $url = $this->fullUrl();

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $url)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断这个请求是否是 ajax 请求
     * @return bool
     */
    public function ajax()
    {
        return $this->isXmlHttpRequest();
    }

    /**
     * 判断是否是 pjax
     * @return bool
     */
    public function pjax()
    {
        return $this->headers->get('X-PJAX') == true;
    }

    /**
     * 判断当前请求是否是一个预先载入请求
     * @return bool
     */
    public function prefetch()
    {
        // HTTP_X_MOZ 是火狐浏览器，Purpose 是 chrome 浏览器
        return strcasecmp($this->server->get('HTTP_X_MOZ'),'prefetch') === 0 ||
            strcasecmp($this->headers->get('Purpose'),'prefetch') === 0;
    }

    /**
     * 是否是 https 请求
     * @return bool
     */
    public function secure()
    {
        return $this->isSecure();
    }

    /**
     * 获取当前客户端 ip
     */
    public function ip()
    {
        return $this->getClientIp();
    }

    public function ips()
    {
        return $this->getClientIps();
    }

    public function userAgent()
    {
        return $this->headers->get('User-Agent');
    }

    public function json($key = null,$default = null)
    {
        if (!isset($this->json)) {
            $this->json = new ParameterBag((array)json_decode($this->getContent(),true));
        }

        if (is_null($key)) {
            return $this->json;
        }

        return data_get($this->json->all(),$key,$default);
    }
}