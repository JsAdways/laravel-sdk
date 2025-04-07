<?php

namespace Jsadways\LaravelSDK\Traits;

use Jsadways\LaravelSDK\Core\_Consts;
use Jsadways\LaravelSDK\TagProcessor\Tags\PreCallAction;
use Jsadways\LaravelSDK\Core\ReadListParamsDto;
use Jsadways\LaravelSDK\Exceptions\RepositoryException;
use Jsadways\LaravelSDK\Http\RequestCtx;
use Jsadways\LaravelSDK\Http\Requests\ReadListRequest;
use Jsadways\LaravelSDK\Http\Requests\Server\ServerRequest as Request;
use Jsadways\LaravelSDK\Models\BaseModel;
use App\Repositories\Repository;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\{Collection, Model};
use Illuminate\Pagination\LengthAwarePaginator;
use JetBrains\PhpStorm\ArrayShape;

trait GeneralApi
{
    use UseRepository;

    // Server Model Path
    private string $__model_root__ = _Consts::MODEL_ROOT;

    // Server Dto Path
    private string $__dto_root__ = _Consts::REPOSITORY_ROOT;

    // Controller Main Model. (set Example if ExampleController)
    private BaseModel|Authenticatable $__main_model__;

    // Controller Main Model Dto. (NewUserDto, UpdateUserDto)
    private ?string $__dto__;

    // Controller Main Model Repository.
    private Repository $repo;

    // Controller Name. (set Example if ExampleController)
    private string $name;

    /**
     * 初始化 name
     *
     * @used-by _pre_call
     * @return static
     */
    #[PreCallAction]
    protected function init_start(): static
    {
        $this->name = str_replace('Controller', '', class_basename(static::class));
        return $this;
    }

    /**
     * 初始化 model, repository
     *
     * @used-by _pre_call
     * @return static
     * @throws Exception
     */
    #[PreCallAction]
    protected function init_db_source(): static
    {
        if (class_exists($this->__model_root__.$this->name)) {
            $this->__main_model__ = new ($this->__model_root__.$this->name);
            $this->repo = $this->repository($this->name);
        }

        return $this;
    }

    /**
     * 初始化 dto
     *
     * @used-by _pre_call
     * @param RequestCtx $ctx
     * @return static
     */
    #[PreCallAction]
    protected function init_dto(RequestCtx $ctx): static
    {
        $this->__dto__ = match ($ctx->get_method())
        {
            'create' => "{$this->__dto_root__}{$this->name}\\Dtos\\Create{$this->name}Dto",
            'update' => "{$this->__dto_root__}{$this->name}\\Dtos\\Update{$this->name}Dto",
            'read_list' => ReadListParamsDto::class,
            default => Null
        };
        return $this;
    }

    /**
     * 通用 CREATE
     *
     * @param Request $request
     * @return Model
     * @throws RepositoryException
     */
    public function create(Request $request): Model
    {
        return $this->repo->create(new $this->__dto__(...$request->json()));
    }

    /**
     * 通用 READ LIST
     *
     * @param ReadListRequest $request
     * @return LengthAwarePaginator|Collection
     * @throws RepositoryException
     */
    public function read_list(ReadListRequest $request): LengthAwarePaginator|Collection
    {
        return $this->repo->read_models(new $this->__dto__(...$request->validated()));
    }

    /**
     * 通用 UPDATE
     *
     * @param Request $request
     * @return Model
     * @throws RepositoryException
     */
    public function update(Request $request): Model
    {
        return $this->repo->update(new $this->__dto__(...$request->json()));
    }

    /**
     * 通用 DELETE
     *
     * @param Request $request
     * @return array
     * @throws RepositoryException
     */
    #[ArrayShape(['message' => "string"])]
    public function delete(Request $request): array
    {
        $this->repo->delete_model($request->json()->get('id'));

        return [
            'message' => '刪除成功。'
        ];
    }
}
