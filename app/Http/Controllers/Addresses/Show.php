<?php

namespace App\Http\Controllers\Addresses;

use App\Addr;
use Illuminate\Routing\Controller;

class Show extends Controller
{
    public function __invoke(Addr $addr)
    {
        return ['addr' => $addr];
    }
}
