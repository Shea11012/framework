<?php


namespace Shea\Routing;


class RoutingServiceProvider
{
    /**
     * @var \Shea\Contracts\Foundation\Application
     */
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }
}