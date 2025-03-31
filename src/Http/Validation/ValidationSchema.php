<?php

namespace Jsadways\LaravelSDK\Http\Validation;

use Jsadways\LaravelSDK\Http\Validation\Unit\Node;
use Jsadways\LaravelSDK\Traits\UseRepository;
use Exception;

class ValidationSchema
{
    use UseRepository;

    /** @var array<Node> */
    private array $linked_nodes = [];


    # 根節點(最上層資料表)
    private ?Node $root_node = Null;

    public function __construct(ModelSchema $model_schema)
    {
        $this->_init_root($model_schema);
        $this->_init_tree($model_schema);
        $this->_ensure_complete();
    }



    private function _init_root(ModelSchema $model_schema)
    {
        $root = new Node(model_schema: $model_schema);
        $this->root_node = $root;
        $this->append($root);
    }

    private function _init_tree(ModelSchema $model_schema)
    {
        foreach($model_schema->get_relations() as $relation)
        {
            $this->append(new Node(relation: $relation));
        }
    }

    public function gen_schemas()
    {
        return function ()
        {
            return $this->root_node->gen_schema(
                $this->root_node->get_model_picks(),
                $this->root_node->get_option()
            );
        };
    }

    public function append(Node $new): void
    {
        foreach ($this->linked_nodes as $old)
        {
            if (!$new->has_last())
            {
                $this->_link_parent($new, $this->root_node);
            }
            if ($new->get_last() === $old->get_current())
            {
                $this->_link_parent($new, $old);
            }
            if ($new->get_current() === $old->get_last())
            {
                $this->_link_child($new, $old);
            }
        }
        $this->linked_nodes[] = $new;
    }

    protected function _link_parent(Node $new, Node $old): void
    {
        $new->link_parent_node($old);
        $old->link_child_node($new);
    }

    protected function _link_child(Node $new, Node $old): void
    {
        $new->link_child_node($old);
        $old->link_parent_node($new);
    }

    protected function _ensure_complete()
    {
        foreach ($this->linked_nodes as $node)
        {
            if (!$node->has_last_node())
            {
                throw new Exception("類別：{$node->get_current()} 未成功連結上層");
            }
        }
    }
}
