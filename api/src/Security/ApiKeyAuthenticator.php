<?php
// src/Security/ApiKeyAuthenticator.php
namespace App\Security;

use App\Entity\ApiKey;
use App\Security\ApiKeyUser;
use App\Repository\ApiKeyRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public const HEADER_NAME = 'X-API-KEY';

    public function __construct(private readonly ApiKeyRepository $repo) {}

    /**
     * 1) Nur aktiv werden, wenn X-API-KEY-Header vorhanden ist
     */
    public function supports(Request $request): ?bool
    {
        return $request->headers->has(self::HEADER_NAME);
    }

    /**
     * 2) API-Key validieren und ApiKeyUser erzeugen
     */
    public function authenticate(Request $request): SelfValidatingPassport
    {
        $rawToken = $request->headers->get(self::HEADER_NAME);
        if (!$rawToken) {
            throw new AuthenticationException('API Key header not provided.');
        }

        $hash = hash('sha256', $rawToken);

        /** @var ApiKey|null $apiKeyEntity */
        $apiKeyEntity = $this->repo->findOneBy([
            'tokenHash' => $hash,
            'isActive'  => true,
        ]);
        if (!$apiKeyEntity) {
            throw new AuthenticationException('Invalid API Key.');
        }

        // Ein ApiKeyUser, der die Entity enthält
        $apiKeyUser = new ApiKeyUser($apiKeyEntity);

        // für Controller: Partner direkt im Request
        $request->attributes->set('_partner', $apiKeyUser->getPartner());

        return new SelfValidatingPassport(
            // UserBadge-Callback liefert jetzt ApiKeyUser, nicht InMemoryUser
            new UserBadge($hash, fn() => $apiKeyUser)
        );
    }

    /**
     * 3) Erfolg → weiter zum Controller
     */
    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        return null;
    }

    /**
     * 4) Fehler → JSON-Response 401
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse(
            ['error' => 'API Key Authentication Failed', 'detail' => $exception->getMessage()],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
