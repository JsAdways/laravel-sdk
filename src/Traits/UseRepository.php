<?php

namespace Jsadways\LaravelSDK\Traits;

use Jsadways\LaravelSDK\Repositories\Repository;
use Jsadways\LaravelSDK\Repositories\RepositoryManager;
use Exception;

trait UseRepository
{
    /**
     * 呼叫需實例化 Class
     *
     * @param string $name
     * @return Repository
     * @throws Exception
     */
    protected function repository(string $name): Repository
    {
        $repository_manager = new RepositoryManager();
        return $repository_manager->get($name);
    }
}
