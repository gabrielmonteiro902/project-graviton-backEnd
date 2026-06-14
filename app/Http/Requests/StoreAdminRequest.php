<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = auth('admin')->user()->tenant_id;

        return [
            'name_admin'     => 'required|string|max:255',
            'email_admin'    => [
                'required',
                'email',
                Rule::unique('admins', 'email_admin')->where('tenant_id', $tenantId),
            ],
            'password_admin' => 'required|string|min:6',
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
