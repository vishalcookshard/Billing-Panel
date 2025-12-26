<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SafeHtml implements Rule
{
    protected array $allowedTags = ['svg', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'g', 'title'];

    public function passes($attribute, $value)
    {
        // Allow only a limited set of tags by stripping others and comparing
        $cleaned = strip_tags((string)$value, '<' . implode('><', $this->allowedTags) . '>');
        return $cleaned === (string)$value;
    }

    public function message()
    {
        return 'The :attribute contains unsafe HTML.';
    }
}
