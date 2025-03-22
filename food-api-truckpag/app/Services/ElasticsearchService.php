<?php

namespace App\Services;

use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchService
{
    protected $client;
    protected $index = 'products';

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts(['localhost:9200'])
            ->build();
    }

    public function indexProduct(array $productData)
    {
        return $this->client->index([
            'index' => $this->index,
            'id'    => $productData['code'],
            'body'  => $productData,
        ]);
    }

    public function search(string $query)
    {
        return $this->client->search([
            'index' => $this->index,
            'body'  => [
                'query' => [
                    'multi_match' => [
                        'query'  => $query,
                        'fields' => [
                            'product_name^3',
                            'brands',
                            'categories',
                            'labels',
                            'ingredients_text'
                        ],
                    ],
                ],
            ],
        ]);
    }
}
