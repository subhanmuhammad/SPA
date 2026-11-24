<?php

namespace App\View\Composers;

use App\Models\Branch;
use Illuminate\View\View;

class CustomerLayoutComposer
{
    public function compose(View $view): void
    {
        $view->with('siteBranches', once(fn () => Branch::active()->orderBy('id')->get(['id', 'name', 'address', 'phone', 'opening_time', 'closing_time'])));
    }
}
