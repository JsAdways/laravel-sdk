<?php

namespace App\Services\Internal;

use App\Core\Services\Internal\Contracts\EnumServiceContract;
use Jsadways\LaravelSDK\Core\Manager\GetObjectDto;
use Jsadways\LaravelSDK\Managers\EnumManager;
use Jsadways\LaravelSDK\Exceptions\ServiceException;
use App\Services\Service;

class InternalService extends Service implements EnumServiceContract
{

    public function __construct(
        protected EnumManager $enum_manager
    )
    {
    }

    /**
     * 取得設定列表
     *
     * @param array $fields: ['ServiceA.EnumA', 'SeviceA.EnumB', 'ServiceB.EnumN', ...]
     * @return array
     * @throws ServiceException
     */
    public function get_enums(array $fields): array
    {
        $content = [];
        foreach ($fields as $service_enum) {
            [$service, $enum] = explode('.', $service_enum);
            $enums = $content[$service] ?? [];
            $enums[$enum] = $this->enum_manager->get(new GetObjectDto("{$service}\\{$enum}"))::to_array();
            $content[$service] = $enums;
        }
        return $content;
    }
}
