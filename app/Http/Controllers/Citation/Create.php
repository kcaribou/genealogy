<?php

namespace App\Http\Controllers\Citation;

use Illuminate\Routing\Controller;
use App\Forms\Builders\CitationForm;

class Create extends Controller
{
    public function __invoke(CitationForm $form)
    {
        return ['form' => $form->create()];
    }
}