<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Security\Schnorr\SchnorrParameterProviderInterface;
use Illuminate\Http\JsonResponse;

/**
 * Reference: api_spec.md §6.8 (Schnorr Parameters), crypto_design.md §4.4.2
 */
class SchnorrParameterController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SchnorrParameterProviderInterface $parameters,
    ) {}

    public function index(): JsonResponse
    {
        $params = $this->parameters->current();

        return $this->success('Parameter Schnorr berhasil diambil.', $params->toArray());
    }
}
