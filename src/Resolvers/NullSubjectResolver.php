<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Resolvers;

use Illuminate\Contracts\Auth\Authenticatable;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Contracts\SubjectResolver;
use Padosoft\Rebel\Core\Identifiers\AuthIdentifier;

/**
 * Default resolver: it does not resolve any user (returns null).
 * The application provides its own implementation that maps email → customer.
 */
final class NullSubjectResolver implements SubjectResolver
{
    public function resolve(AuthIdentifier $identifier, SecurityContext $context): ?Authenticatable
    {
        return null;
    }
}
