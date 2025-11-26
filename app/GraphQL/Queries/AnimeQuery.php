<?php

namespace App\GraphQL\Queries;

use Closure;
use Rebing\GraphQL\Support\Query;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use App\Services\FirestoreRestService;

class AnimeQuery extends Query
{
    protected $attributes = [
        'name' => 'anime',
    ];

    protected $firestore;

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Anime'));
    }

    public function args(): array
    {
        return [
            'limit' => ['name' => 'limit', 'type' => Type::int(), 'defaultValue' => 10],
            'page' => ['name' => 'page', 'type' => Type::int(), 'defaultValue' => 1],
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $info, Closure $getSelectFields)
    {
        $limit = $args['limit'];
        $page = $args['page'];
        $offset = ($page - 1) * $limit;

        $data = $this->firestore->collection('anime')->list($limit, $offset);
        
        // Map Firestore array to object structure if needed, but array usually works
        return $data;
    }
}
