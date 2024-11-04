<?php

namespace App\Http\Controllers\Api;

use App\Models\Geo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GeoController extends Controller
{
    public function index(Request $request, $geoName): LengthAwarePaginator
    {
        $queries = [
            'country' => Geo\Country::query()->when(
                $request->input('keyword'),
                fn (Builder $query, $keyword) => $query->where(
                    fn (Builder $query) => $query
                        ->orWhere('name', $this->getMatchLikeClause(Geo\Country::query()), '%'.$keyword.'%')
                        ->orWhere('alpha2', $this->getMatchLikeClause(Geo\Country::query()), '%'.$keyword.'%')
                        ->orWhere('alpha3', $this->getMatchLikeClause(Geo\Country::query()), '%'.$keyword.'%')
                        ->orWhere('numeric', $this->getMatchLikeClause(Geo\Country::query()), '%'.$keyword.'%')
                )
            ),
            'province' => Geo\Province::query()->when(
                $request->input('keyword'),
                fn (Builder $query, $keyword) => $query->where(
                    fn (Builder $query) => $query
                        ->orWhere('name', $this->getMatchLikeClause(Geo\Province::query()), '%'.$keyword.'%')
                        ->orWhere('iso_code', $this->getMatchLikeClause(Geo\Province::query()), '%'.$keyword.'%')
                )
            ),
            'regency' => Geo\Regency::query()->when(
                $request->input('keyword'),
                fn (Builder $query, $keyword) => $query->where(
                    fn (Builder $query) => $query
                        ->orWhere('name', $this->getMatchLikeClause(Geo\Regency::query()), '%'.$keyword.'%')
                        ->orWhere('capital', $this->getMatchLikeClause(Geo\Regency::query()), '%'.$keyword.'%')
                        ->orWhere('bsn_code', $this->getMatchLikeClause(Geo\Regency::query()), '%'.$keyword.'%')
                )
            ),
        ];

        abort_if(! array_key_exists($geoName, $queries), 404);

        $query = $queries[$geoName];

        return $query->paginate($request->input('per_page'));
    }
}
