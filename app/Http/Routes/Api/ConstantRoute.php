<?php

namespace App\Http\Routes\Api;

use App\Models\Constant;
use Illuminate\Http\Request;
use Dentro\Yalr\BaseRoute;
use Illuminate\Database\Eloquent\Builder;

class ConstantRoute extends BaseRoute
{
    protected string $prefix = 'constant';

    protected string $name = 'api.constant';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix(), [
            'as' => $this->name,
            'uses' => fn (Request $request) => Constant::query()
                ->when(
                    $request->has('name'),
                    fn (Builder $builder) => $builder->where('name', $request->input('name'))
                )
                ->get(),
        ]);
    }
}
