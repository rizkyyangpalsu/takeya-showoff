<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jalameta\Patcher\Patch;
use Symfony\Component\Finder\Finder;

class FixAutoincrementRestoreTable extends Patch
{
    /**
     * Run patch script.
     *
     * @return void
     */
    public function patch()
    {
        $finder = new Finder();

        $finder->files()->in(__DIR__.'/../database/seeders/data/restore');
        $finder->sortByName();

        $tables = collect();

        foreach ($finder as $file) {
            $tableName = Str::after($file->getFilenameWithoutExtension(), 'tiaramas_');

            $tables->push($tableName);
        }

        collect(DB::select("SELECT c.relname FROM pg_class c WHERE c.relkind = 'S';"))
            ->map(fn ($row) => [
                'relname' => $row->relname,
                'table_name' => Str::before($row->relname, '_id_seq'),
            ])
            ->filter(fn ($row) => $tables->contains($row['table_name']))
            ->map(fn ($row) => array_merge($row, [
                'last_id' => (int) DB::selectOne('SELECT id FROM '.$row['table_name'].' ORDER BY id DESC')?->id,
            ]))
            ->each(fn ($row) => DB::statement('ALTER SEQUENCE '.$row['relname'].' RESTART WITH '.($row['last_id'] + 1)));
    }
}
