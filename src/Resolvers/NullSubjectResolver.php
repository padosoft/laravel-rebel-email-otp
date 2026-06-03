<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Resolvers;

use Illuminate\Contracts\Auth\Authenticatable;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Contracts\SubjectResolver;
use Padosoft\Rebel\Core\Identifiers\AuthIdentifier;

/**
 * Resolver di default: non risolve nessun utente (ritorna null).
 * L'app (es. Gescat) fornisce la propria implementazione che mappa email → cliente.
 */
final class NullSubjectResolver implements SubjectResolver
{
    public function resolve(AuthIdentifier $identifier, SecurityContext $context): ?Authenticatable
    {
        return null;
    }
}
