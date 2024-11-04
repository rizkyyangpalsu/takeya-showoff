<?php

namespace App\Concerns\Collection;

use League\Csv\Reader;
use League\Csv\Statement;
use Illuminate\Support\Collection;

trait CsvIntoCollection
{
    /**
     * @param $filePath
     * @return \Illuminate\Support\Collection
     * @throws \League\Csv\Exception
     */
    public function loadFiles($filePath): Collection
    {
        $collection = new Collection();

        $csv = Reader::createFromPath($filePath);
        $csv->setHeaderOffset(0);

        foreach ((new Statement())->process($csv) as $item) {
            $collection->add($item);
        }

        return $collection;
    }
}
