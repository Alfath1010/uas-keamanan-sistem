<?php

namespace App\Exceptions\Business;

/**
 * ADDENDUM to api_spec.md §6.9: not covered by the spec at all, but
 * needed since conversations are explicitly one-to-one (FR-003) —
 * a user conversing with themself isn't a meaningful state.
 */
class CannotConverseWithSelfException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Anda tidak dapat memulai percakapan dengan diri sendiri.',
            errorCode: 'CONV001',
            statusCode: 422,
        );
    }
}
