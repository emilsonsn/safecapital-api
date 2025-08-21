<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class EnumRule implements Rule
{
    protected string $enumClass;

    public function __construct(string $enumClass)
    {
        $this->enumClass = $enumClass;
    }

    public function passes($attribute, $value): bool
    {
        if (!enum_exists($this->enumClass)) {
            return false;
        }

        foreach ($this->enumClass::cases() as $case) {
            if ($value === $case || $value === $case->value || $value === $case->name) {
                return true;
            }
        }

        return false;
    }

    public function message(): string
    {
        return 'O campo :attribute é inválido.';
    }
}
