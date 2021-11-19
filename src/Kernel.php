<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/21
 */

namespace QL;

use QL\Contracts\ServiceProviderContract;
use QL\Exceptions\ServiceNotFoundException;
use Closure;
use QL\Services\EncodeService;
use QL\Services\PluginService;
use think\Collection;

class Kernel
{
    protected $providers = [];

    protected $binds;
    protected $ql;

    /**
     * Kernel constructor.
     * @param $ql
     */
    public function __construct(QueryList $ql)
    {
        $this->ql = $ql;
        $this->binds = new Collection();
    }

    public function bootstrap()
    {
        //注册服务提供者
        $this->registerProviders();
        $this->bind('html',function (...$args){
            $this->setHtml(...$args);
            return $this;
        });

        $this->bind('queryData',function (Closure $callback = null){
            return $this->query()->getData($callback)->all();
        });

        $this->bind('pipe',function (Closure $callback = null){
            return $callback($this);
        });
        $this->bind('encoding',function (string $outputEncoding,string $inputEncoding = null){
            return EncodeService::convert($this,$outputEncoding,$inputEncoding);
        });
        $this->bind('use',function ($plugins,...$opt){
            return PluginService::install($this,$plugins,...$opt);
        });
        return $this;
    }

    public function registerProviders()
    {
        foreach ($this->providers as $provider) {
            $this->register(new $provider());
        }
    }

    public function bind(string $name,Closure $provider)
    {
        $this->binds[$name] = $provider;
    }

    public function getService(string $name)
    {
        if(!$this->binds->offsetExists($name)){
            throw new ServiceNotFoundException("Service: {$name} not found!");
        }
        return $this->binds[$name];
    }

    private function register(ServiceProviderContract $instance)
    {
        $instance->register($this);
    }


}