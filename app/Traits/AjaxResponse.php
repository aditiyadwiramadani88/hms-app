<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Request;

trait AjaxResponse
{
    /**
     * Determine if the request is an AJAX request.
     *
     * @return bool
     */
    protected function isAjaxRequest(): bool
    {
        return Request::ajax() || Request::wantsJson();
    }

    /**
     * Return a success AJAX response.
     *
     * @param string $message
     * @param mixed $data
     * @param int $code
     * @return JsonResponse
     */
    protected function ajaxSuccess(string $message = 'Success', $data = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Return an error AJAX response.
     *
     * @param string $message
     * @param mixed $errors
     * @param int $code
     * @return JsonResponse
     */
    protected function ajaxError(string $message = 'Error', $errors = null, int $code = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    /**
     * Handle response based on request type (AJAX or Redirect).
     *
     * @param string $message
     * @param string $redirectUrl
     * @param mixed $data
     * @param int $successCode 200 or 201
     * @return JsonResponse|RedirectResponse
     */
    protected function ajaxOrRedirect(string $message, string $redirectUrl, $data = null, int $successCode = 200)
    {
        if ($this->isAjaxRequest()) {
            return $this->ajaxSuccess($message, $data, $successCode);
        }

        return redirect($redirectUrl)->with('success', $message);
    }
}
