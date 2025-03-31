<?php

namespace Jsadways\LaravelSDK\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Throwable;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Log;
use Jsadways\LaravelSDK\Http\Requests\Server\ServerRequest;
use Jsadways\LaravelSDK\Http\Requests\ReadListRequest;

class GenerateApiDocs extends Command
{
    private array $msg_list = [];
    public function __construct(
        private ServerRequest $ServerRequest,
        private ReadListRequest $ReadListRequest
    ){
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:docs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Api Docs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->newLine(1);
        try {
            $this->info('------ Generate Api Docs start -------');
            $this->newLine(2);
            $this->generate_api_docs();
            $this->newLine(2);
            $this->info('------ Generate Api Docs finish -------');
            $this->newLine(2);
            $this->info('Api Docs exported successfully');
            $this->info('Api docs url: '.Config::get('app.url').'/docs-api');
            $this->newLine(2);
            $this->info('------ msg start -------');
            $this->newLine(1);
            $this->print_msg_list();
            $this->newLine(1);
            $this->info('------ msg finish -------');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

    }

    protected function print_msg_list(): bool{
        foreach ($this->msg_list as $msg)
        {
            $this->warn($msg);
        }

        return true;
    }

    /**
     * 產生 api 文件
     *
     * @return boolean
     */
    protected function generate_api_docs(): bool
    {
        $route_list = $this->get_routes();

        // 進度條
        $bar = $this->output->createProgressBar(count($route_list));
        $bar->start();

        $paths_content = [];
        foreach ($route_list as $route) {
            $authenticate = $this->has_authenticate(middleware: $route->middleware());

            $action_name = $route->getActionName();
            if (preg_match('/([^\\\\]+?)Controller@/', $action_name, $controller_name)) {
                $model_name = $controller_name[1];
            } else {
                continue;
            }

            [$controller, $controller_method] = explode('@', $action_name);

            $http_method = mb_strtolower($route->methods()[0]);

            $validate_schemas = $this->get_validate_schemas(model_name: $model_name, controller_method: $controller_method);
            if ($validate_schemas) {
                $param_list = $this->transform_validate_schemas(validate_schemas: $validate_schemas);
            } else {
                continue;
            }

            $content = match ($http_method) {
                'get' => $this->prepare_http_get_content(model_name: $model_name),
                'post' => $this->prepare_http_post_content(model_name: $model_name, param_list: $param_list),
                'patch' => $this->prepare_http_patch_content(model_name: $model_name, param_list: $param_list),
                'delete' => $this->prepare_http_delete_content(param_list: $param_list),
                default => [],
            };

            if (!array_key_exists($route->uri(), $paths_content)) {
                $paths_content[$route->uri()] = [];
            }

            $paths_content[$route->uri()][$http_method] = [
                'tags' => [$model_name],
                'summary' => '',
                'description' => '',
                ...$content,
                ...$authenticate,
            ];

            // 執行進度條
            $bar->advance();
        }

        // 進度條結束
        $bar->finish();

        $docs = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => Config::get('app.name').' - OpenAPI 3.0',
                'description' => '',
                'version' => '3.0.0'
            ],
            'paths' => $paths_content,
        ];

        return $this->store_api_json($docs);
    }

    /**
     * 是否需要認證
     *
     * @param array $middleware
     * @return array
     */
    protected function has_authenticate(array $middleware): array
    {
        $authenticate = [];
        if (in_array('js-authenticate-middleware-alias', $middleware)) {
            $authenticate = [
                'security' => [
                    [
                        'securityScheme' => 'Authorization',
                        'type' => 'http',
                        'scheme' => 'bearer'
                    ]
                ]
            ];
        }
        return $authenticate;
    }

    /**
     * 取得所有路由
     *
     * @return Collection
     */
    protected function get_routes(): Collection
    {
        return collect(Route::getRoutes())->filter(function ($route) {
            $middlewares = $route->middleware();
            return in_array('api', $middlewares);
        });
    }

    /**
     * 取得 api schemas
     *
     * @param string $controller
     * @param string $controller_method
     * @param string $model_name
     * @return array|boolean
     */
    protected function get_validate_schemas(string $model_name, string $controller_method): array|bool
    {
        $schemas = null;
        try {
            if ($controller_method === 'read_list') {
                $schemas = $this->ReadListRequest->rules();
            } else {
                $this->ServerRequest->create_validation(model_name: $model_name, method_name: $controller_method);
                $schemas = $this->ServerRequest->rules();
            }
        } catch (Throwable $e) {
            $msg_list = $e->getMessage();
            $this->msg_list[] = "{$model_name} {$controller_method} {$msg_list}";
        }

        return $schemas === null ? false : $schemas;
    }

    /**
     * 轉換 schemas 資料格式
     *
     * @param array $schemas
     * @return array
     */
    protected function transform_validate_schemas(array $validate_schemas): array
    {
        $param_list = [];

        foreach ($validate_schemas as $param_name => $rule) {
            // create_dimension_item_list.*.name
            if (preg_match("/_list/", $param_name) ) {
                $param_name_list = preg_split("/\.\*\./", $param_name);
                if (count($param_name_list) > 1) {
                    $param_list = array_merge_recursive($param_list, $this->prepare_param_data(array_reverse($param_name_list), $rule));
                }
                // "treaties.default.*.content"
            } else if (preg_match('/\.\*\./', $param_name)) {
                $param_name_list = preg_split("/\.\*\./", $param_name);

                $param_date_list = [];
                foreach ($param_name_list as $param) {
                    $param_date_list = array_merge($param_date_list, preg_split("/\./", $param));
                }

                if (count($param_name_list) > 1) {
                    $param_list = array_merge_recursive($param_list, $this->prepare_param_data(array_reverse($param_date_list), $rule));
                }
                // "treaties.custom.*" => "string"
            } else if (preg_match('/\.\*$/', $param_name)) {
                $param_name_list = preg_split("/\.\*/", $param_name);

                $param_date_list = [];
                foreach ($param_name_list as $param) {
                    if($param === '') {
                        $rule .= '-array';
                        continue;
                    }
                    $param_date_list = array_merge($param_date_list, preg_split("/\./", $param));
                }

                if (count($param_name_list) > 1) {
                    $param_list = array_merge_recursive($param_list, $this->prepare_param_data(array_reverse($param_date_list), $rule));
                }
            } else {
                $param_list = array_merge_recursive($param_list, [$param_name => $rule]);
            }
        }

        return $param_list;
    }

    /**
     * 處理參數內容
     *
     * @param array $param_list
     * @param string $rule
     * @return array
     */
    protected function prepare_param_data(array $param_list, string $rule): array
    {
        $param = array_pop($param_list);
        $result = [];
        if (count($param_list) === 0) {
            $result[$param] = $rule;
            return $result;
        } else {
            $result[$param] = $this->prepare_param_data(param_list: $param_list, rule: $rule);
        }
        return $result;
    }

    /**
     * 建立 http get request 內容
     *
     * @param string $model_name
     * @return array
     */
    protected function prepare_http_get_content(string $model_name): array
    {
        $field_data = $this->get_model_field($model_name);

        $responses = $this->get_responses(content: [
            'list' => $field_data
        ]);

        return [
            'parameters' => [
                [
                    'name' => 'filter',
                    'in' => 'query',
                    'description' => '多條件篩選參數，使用 JSON 格式',
                    'required' => false,
                    'style' => 'deepObject',
                    'explode' => true,
                    'schema' => [
                        'properties' => [
                            'keyword' => [
                                'description' => '查詢名稱中包含特定關鍵字',
                                'type' => 'string',
                                'example' => '類型: string 範例: 案件名稱  說明: 查詢名稱中包含特定關鍵字的案件'
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'page',
                    'in' => 'query',
                    'description' => '頁數',
                    'required' => false,
                    'schema' => [
                        'type' => 'integer',
                        'example' => 1
                    ]
                ],
                [
                    'name' => 'per_page',
                    'in' => 'query',
                    'description' => '每頁案件筆數',
                    'required' => false,
                    'schema' => [
                        'type' => 'integer',
                        'example' => 30
                    ]
                ],
                [
                    'name' => 'sort_by',
                    'in' => 'query',
                    'description' => '根據欄位排序案件',
                    'required' => false,
                    'schema' => [
                        'type' => 'string',
                        'enum' => ['id', 'name', 'name_en'],
                        'example' => 'id'
                    ]
                ],
                [
                    'name' => 'sort_order',
                    'in' => 'query',
                    'description' => '案件排序方式',
                    'required' => false,
                    'schema' => [
                        'type' => 'string',
                        'enum' => ['asc', 'desc'],
                        'example' => 'asc'
                    ]
                ]
            ],
            ...$responses
        ];
    }

    /**
     * 建立 http post request 內容
     *
     * @param string $model_name
     * @param array $param_data
     * @return array
     */
    protected function prepare_http_post_content(string $model_name, array $param_list): array
    {
        $content = $this->generate_request_body(param_list: $param_list);
        $field_data = $this->get_model_field($model_name);
        $responses = $this->get_responses(content:  $field_data);

        return [
            ...$content,
            ...$responses,
        ];
    }

    /**
     * 建立 http patch request 內容
     *
     * @param string $model_name
     * @param array $param_data
     * @return array
     */
    protected function prepare_http_patch_content(string $model_name, array $param_list): array
    {
        $content = $this->generate_request_body(param_list: $param_list);
        $field_data = $this->get_model_field($model_name);
        $responses = $this->get_responses(content:  $field_data);

        return [
            ...$content,
            ...$responses,
        ];
    }

    /**
     * 建立 http delete request 內容
     *
     * @param array $param_data
     * @return array
     */
    protected function prepare_http_delete_content(array $param_list): array
    {
        $content = $this->generate_request_body(param_list: $param_list);
        $responses = $this->get_responses(content: ['message' => '刪除成功。']);

        return [
            ...$content,
            ...$responses,
        ];
    }

    /**
     * 取得 model 欄位
     *
     * @param string $model_name
     * @return array
     */
    protected function get_model_field(string $model_name): array
    {
        $factory_data = [];
        try {
            $model_class = "App\\Models\\{$model_name}";
            if (class_exists($model_class)) {
                $model = new $model_class();
            }
            $factory_data = $model::factory()->make()->only($model->getFillable());
        } catch (Throwable $e) {
            Log::info('Generate Api Docs - '.$e->getMessage());
        }

        return $factory_data;
    }
    /**
     * 取得請求內容
     *
     * @param array $param_data
     * @return array
     */
    protected function generate_request_body(array $param_list): array
    {
        $required_field = $this->extract_required_field($param_list);
        $properties = $this->extract_properties($param_list);

        return [
            'requestBody' => [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'required' => $required_field,
                            'properties' => $properties
                        ],
                    ]
                ]
            ]
        ];
    }

    /**
     * 取得必填欄位
     *
     * @param array $schemas_list
     * @return array
     */
    public function extract_required_field(array $schemas_list): array
    {
        $properties = [];
        foreach ($schemas_list as $param => $content) {
            if (gettype($content) === 'string') {
                if (str_contains($content, 'required')) {
                    $properties[] = $param;
                }
            }
        }

        return $properties;
    }

    /**
     * 取得請求參數
     *
     * @param array $schemas_list
     * @return array
     */
    public function extract_properties(array $schemas_list): array
    {
        $properties = [];
        foreach ($schemas_list as $param => $content) {
            if (gettype($content) === 'string') {
                switch (true) {
                    case preg_match('/-array/', $content):
                        $content = preg_replace('/-array/', '', $content);
                        $properties[$param] = [
                            'type' => 'array',
                            'items' => [
                                'type' => $content
                            ]
                        ];
                        break;
                    case str_contains($content, 'string'):
                        $properties[$param] = [
                            'type' => 'string',
                        ];
                        break;
                    case str_contains($content, 'integer'):
                        $properties[$param] = [
                            'type' => 'integer',
                        ];
                        break;
                }
            } else {
                if (gettype($content) === 'array') {
                    $is_enums = false;
                    foreach ($content as $rule) {
                        if (is_a($rule, Enum::class)) {
                            $properties[$param] = [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'integer'
                                ]
                            ];
                            $is_enums = true;
                            break;
                        }
                    }
                    if (!$is_enums) {
                        $items = $this->extract_properties($content);
                        $properties[$param] = [
                            'type' => 'array',
                            'items' => [
                                'properties' => $items,
                            ]
                        ];
                    }
                }
            }
        }
        return $properties;
    }

    /**
     * 取得 responses 內容
     *
     * @return array
     */
    protected function get_responses(array $content): array
    {
        return [
            'responses' => [
                '200' => [
                    'description' => '',
                    'content' => [
                        'application/json' => [
                            'schema' => [],
                            'example' => [
                                'data' => $content
                            ]
                        ]
                    ]
                ],
                '400' => [
                    'description' => '',
                    'content' => [
                        'application/json' => [
                            'schema' => [],
                            'example' => [
                                'status' => 400,
                                'message' => '客戶端錯誤'
                            ]
                        ]
                    ]
                ],
                '401' => [
                    'description' => '',
                    'content' => [
                        'application/json' => [
                            'schema' => [],
                            'example' => [
                                'status' => 401,
                                'message' => 'Unauthorized'
                            ]
                        ]
                    ]
                ],
                '500' => [
                    'description' => '',
                    'content' => [
                        'application/json' => [
                            'schema' => [],
                            'example' => [
                                'status' => 500,
                                'message' => '伺服器發生錯誤'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * 儲存 api 文件 json 檔
     *
     * @param array $docs
     * @return boolean
     */
    protected function store_api_json(array $docs): bool
    {
        $json = json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return File::put(public_path('api-docs.json'), $json);
    }
}
