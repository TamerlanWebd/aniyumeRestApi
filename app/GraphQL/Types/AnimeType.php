<?php

namespace App\GraphQL\Types;

use App\Models\Anime; // Assuming model exists, or we map generic object
use Rebing\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;

class AnimeType extends GraphQLType
{
    protected $attributes = [
        'name'          => 'Anime',
        'description'   => 'A anime',
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The id of the anime',
            ],
            'title' => [
                'type' => Type::string(),
                'description' => 'The title of the anime',
            ],
            'description' => [
                'type' => Type::string(),
                'description' => 'The description of the anime',
            ],
            'genre' => [
                'type' => Type::string(),
                'description' => 'The genre of the anime',
            ],
            'imageUrl' => [
                'type' => Type::string(),
                'description' => 'The image URL of the anime',
            ],
        ];
    }
}
