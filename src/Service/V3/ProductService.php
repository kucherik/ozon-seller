<?php

declare(strict_types=1);

namespace Gam6itko\OzonSeller\Service\V3;

use Gam6itko\OzonSeller\Enum\SortDirection;
use Gam6itko\OzonSeller\ProductValidator;
use Gam6itko\OzonSeller\Service\AbstractService;
use Gam6itko\OzonSeller\Utils\ArrayHelper;

class ProductService extends AbstractService
{
    private $path = '/v3/product';

    /**
     * Creates product page in our system.
     *
     * @see https://cb-api.ozonru.me/apiref/en/#t-title_product_import
     *
     * @param array $income Single item structure or array of items
     *
     * @return array
     */
    public function import(array $income, bool $validateBeforeSend = true)
    {
        if (!array_key_exists('items', $income)) {
            $income = $this->ensureCollection($income);
            $income = ['items' => $income];
        }

        $income = ArrayHelper::pick($income, ['items']);

        if ($validateBeforeSend) {
            $pv = new ProductValidator('create', 2);
            foreach ($income['items'] as &$item) {
                $item = $pv->validateItem($item);
            }
        }

        return $this->request('POST', "{$this->path}/import", $income);
    }

    public function importStocks(array $filter, string $lastId = '', int $limit = 100)
    {
        assert($limit > 0 && $limit <= 1000);

        $body = [
            'filter'  => ArrayHelper::pick($filter, ['offer_id', 'product_id', 'visibility']),
            'last_id' => $lastId ?? '',
            'limit'   => $limit,
        ];

        return $this->request('POST', "{$this->path}s/stocks", $body);
    }

    public function infoAttributes(array $filter, string $lastId = '', int $limit = 100, string $sortBy = 'product_id', string $sortDir = SortDirection::DESC)
    {
        $body = [
            'filter'   => ArrayHelper::pick($filter, ['offer_id', 'product_id', 'visibility']),
            'last_id'  => $lastId ?? '',
            'limit'    => $limit,
            'sort_by'  => $sortBy,
            'sort_dir' => $sortDir,
        ];

        return $this->request('POST', "{$this->path}s/info/attributes", $body);
    }
}
