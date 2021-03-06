<?php

namespace App\Http\Controllers\Addresses;

use App\Tables\Builders\AddrTable;
use Illuminate\Routing\Controller;
use LaravelEnso\Tables\Traits\Init;

class InitTable extends Controller
{
    use Init;

    protected string $tableClass = AddrTable::class;
}
