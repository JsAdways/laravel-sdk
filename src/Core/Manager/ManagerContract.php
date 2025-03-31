<?php

namespace Jsadways\LaravelSDK\Core\Manager;

interface ManagerContract
{
    public function get(GetObjectDto $data): mixed;
    public function get_root(): string;
}
