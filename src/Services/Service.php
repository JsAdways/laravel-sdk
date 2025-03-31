<?php

namespace Jsadways\LaravelSDK\Services;

use Jsadways\LaravelSDK\Traits\LogMessage;
use Jsadways\LaravelSDK\Traits\UseRepository;

abstract class Service
{
    use LogMessage, UseRepository;
}
