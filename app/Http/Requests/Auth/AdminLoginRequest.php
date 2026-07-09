<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class AdminLoginRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [

            'email'     => 'sometimes|required|email|exists:users,email',
            'password'  => 'sometimes|required',

        ];
    }

    public function messages()
    {
        return [
            'email.exists' => 'No account found with this email address.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ];
    }
}
