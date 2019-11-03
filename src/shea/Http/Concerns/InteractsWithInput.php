<?php


namespace Shea\Http\Concerns;


trait InteractsWithInput
{
    public function query($key = null,$default = null)
    {
        return $this->retrieveItem('query',$key,$default);
    }

    /**
     * 从指定的一个资源检索一个参数
     * @param string $source
     * @param string $key
     * @param string|array|null $default
     * @return string|array|null
     */
    protected function retrieveItem($source,$key,$default)
    {
        // $source 是 Symfony 的 request 调用了 ParameterBag 初始化了这些资源
        if (is_null($key)) {
            return $this->$source->all();
        }

        return $this->$source->get($key,$default);
    }
}