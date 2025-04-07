<?php

namespace Jsadways\LaravelSDK\Repositories;

use Jsadways\LaravelSDK\Core\_Consts;
use Exception;

final class RepositoryManager
{
    /**
     * 實例化 Repository
     *
     * @param string $name
     * @return Repository Repository
     * @throws Exception
     */
    public function get(string $name): Repository
    {
        try {
            $namespace = _Consts::REPOSITORIES_ROOT;
            $repository = "{$namespace}{$name}" . "Repository";
            return new $repository;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

    }
}
