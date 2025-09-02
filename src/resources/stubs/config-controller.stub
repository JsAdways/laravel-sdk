<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Config\ConfigService;
use Illuminate\Http\Request;
use Jsadways\LaravelSDK\Exceptions\ServiceException;

class ConfigController extends Controller
{
    public function __construct(
        private ConfigService $ConfigService
    ) {}

    /**
     * @throws ServiceException
     */
    public function get(Request $request): array
    {
        ['filter' => $filter] = $request->validate([
            'filter' => 'required|string',
        ]);

        $fields = json_decode($filter, true)['fields'];
        $config_list = $this->ConfigService->get($fields);

        return $config_list->get();
    }
}