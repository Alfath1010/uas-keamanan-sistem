<?php

namespace App\Exceptions\Business;

/**
 * Reference: api_spec.md §6.9 (MSG002), FT-CONV-003, DEM-008
 */
class NotConversationParticipantException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Anda bukan peserta dari percakapan ini.',
            errorCode: 'MSG002',
            statusCode: 403,
        );
    }
}
