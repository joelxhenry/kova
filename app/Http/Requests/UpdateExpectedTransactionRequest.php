<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesExpectedTransaction;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpectedTransactionRequest extends FormRequest
{
    use ValidatesExpectedTransaction;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->expectedRules();
    }
}
