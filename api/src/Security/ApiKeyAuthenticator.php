<?php
// api/src/Security/ApiKeyAuthenticator.php

namespace App\Security;

use App\Repository\ApiKeyRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(private ApiKeyRepository $repo) {}

    public function supports(Request $request): ?bool
    {
        // Wir unterstützen jede Anfrage, die den Header X-API-KEY enthält
        return $request->headers->has('X-API-KEY');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $rawToken = $request->headers->get('X-API-KEY', '');
        $hash     = hash('sha256', $rawToken);

        return new SelfValidatingPassport(
            new UserBadge($hash, function(string $hash) {
                $apiKey = $this->repo->findOneBy([
                    'tokenHash' => $hash,
                    'isActive'  => true,
                ]);

                if (!$apiKey) {
                    // schlägt Auth ab
                    throw new AuthenticationException('Invalid API Key');
                }

                // geben wir einen InMemoryUser mit ROLE_API zurück
                return new InMemoryUser('api_client', null, ['ROLE_API']);
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        // bei Erfolg einfach weiter zur Controller-Action
        return null;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): JsonResponse {
        return new JsonResponse(
            ['error' => 'API Key Authentication Failed'],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
