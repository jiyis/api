<?php

namespace Dingo\Api\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Dingo\Api\Exception\ValidationHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Foundation\Http\FormRequest as IlluminateFormRequest;

class FormRequest extends IlluminateFormRequest
{

    /**
     * The input keys that should not be flashed on redirect.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Validate the request.
     */
    public function validate()
    {
        if ($this->authorize() === false) {
            throw new AccessDeniedHttpException();
        }

        $validator = app('validator')->make($this->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            throw new ValidationHttpException($validator->errors());
        }
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     *
     * @return void
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->container['request'] instanceof Request) {
            throw new ValidationHttpException($validator->errors());
        }

        parent::failedValidation($validator);
    }

    /**
     * Get the proper failed validation response for the request.
     *
     * @param  array  $errors
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function response(array $errors)
    {
        if ($this->expectsJson()) {
            return new JsonResponse($errors, 422);
        }

        return $this->redirector->to($this->getRedirectUrl())
            ->withInput($this->except($this->dontFlash))
            ->withErrors($errors, $this->errorBag);
    }

    /**
     * Format the errors from the given Validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     *
     * @return array
     */
    protected function formatErrors(Validator $validator)
    {
        return $validator->getMessageBag()->toArray();
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     */
    protected function failedAuthorization()
    {
        if ($this->container['request'] instanceof Request) {
            throw new HttpException(403);
        }

        parent::failedAuthorization();
    }

}
