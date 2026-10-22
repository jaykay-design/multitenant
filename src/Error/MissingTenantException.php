<?php
declare(strict_types=1);

namespace MultiTenant\Error;

use Cake\Http\Exception\NotFoundException;

/**
 * No active tenant matches the request's host.
 *
 * A 404 rather than a server error: these requests come from hosts that are not (or no
 * longer) tenants, such as bots replaying old subdomains. Add this class to the app's
 * `Error.skipLog` to keep it out of the error log.
 */
class MissingTenantException extends NotFoundException
{
    public function __construct(?string $message = null, ?int $code = null, ?\Throwable $previous = null)
    {
        parent::__construct($message ?? 'Missing tenant', $code, $previous);
    }
}
