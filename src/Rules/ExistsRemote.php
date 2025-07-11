<?php

namespace Kroderdev\LaravelMicroserviceCore\Rules;

use Illuminate\Contracts\Validation\Rule;

class ExistsRemote implements Rule
{
    protected string $model;
    protected string $column;

    public function __construct(string $model, string $column = 'id')
    {
        $this->model = $model;
        $this->column = $column;
    }

    public function passes($attribute, $value): bool
    {
        $values = is_array($value) ? $value : [$value];

        foreach ($values as $v) {
            if (! $this->model::find($v)) {
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return trans('validation.exists');
    }
}
