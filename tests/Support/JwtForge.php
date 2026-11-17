<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Tests\Support;

/**
 * Fabrique de jetons JWT HS256 pour les tests.
 *
 * Reproduit cote test le format emis par taskforge-api afin de valider
 * le JwtValidator sans dependance a une librairie externe.
 */
final class JwtForge
{
    /**
     * @param array<string, mixed> $charge
     */
    public static function signer(array $charge, string $secret, string $alg = 'HS256'): string
    {
        $entete = self::encoder(['alg' => $alg, 'typ' => 'JWT']);
        $corps = self::encoder($charge);

        $signature = hash_hmac('sha256', $entete . '.' . $corps, $secret, true);

        return $entete . '.' . $corps . '.' . self::base64Url($signature);
    }

    /**
     * @param array<string, mixed> $donnees
     */
    private static function encoder(array $donnees): string
    {
        return self::base64Url(json_encode($donnees, JSON_THROW_ON_ERROR));
    }

    private static function base64Url(string $valeur): string
    {
        return rtrim(strtr(base64_encode($valeur), '+/', '-_'), '=');
    }
}
