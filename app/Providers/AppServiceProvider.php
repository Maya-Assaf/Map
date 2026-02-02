<?php

namespace App\Providers;

use App\Models\LocationLine;
use App\Models\LocationPoint;
use App\Models\LocationPolygon;
use App\Models\LocationSpace;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            'point' => LocationPoint::class,
            'line' => LocationLine::class,
            'space' => LocationSpace::class,
            'polygon' => LocationPolygon::class,
        ]);

    }
}
