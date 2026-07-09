<?php

namespace App\Base\Filters\Admin;

use App\Base\Libraries\QueryFilter\FilterContract;
use Carbon\Carbon;

class PromotionTemplateFilter implements FilterContract
{
    /**
     * Allowed filters from request
     */
    public function filters()
    {
        return [
            'search',
        ];
    }

    /**
     * Default sort
     */
    public function defaultSort()
    {
        return '-created_at';
    }

    /**
     * Search by subject
     */
    public function search($builder, $value = null)
    {
        if (!$value) {
            return;
        }

        $builder->where('subject', 'LIKE', '%' . $value . '%');
    }


}
