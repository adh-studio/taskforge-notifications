<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Auth;

use TaskForge\Notifications\Domain\Exception\AuthenticationException;
use TaskForge\Notifications\Support\Clock;

/**
 * Validateur de jeton JWT signe en HS256 par taskforge-api.
 *
 * La cle de signature est partagee entre les deux services et fournie
 * par variable d'environnement : aucun secret n'est code en dur (OWASP A02
 * et A07). La verification est faite en temps constant via hash_equals
 * pour eviter les attaques temporelles sur la signature.
 */
final class JwtValidator
{
    public function __construct(
        private readonly string $cleSecrete,
        private readonly Clock $clock,
    ) {
        if ($this->cleSecrete === '') {
            throw new AuthenticationException('Cle de signature JWT non configuree.');
        }
    }

    /**
     * Valide un en-tete Authorization de forme "Bearer <jwt>" et renvoie
     * l'identifiant utilisateur porte par la revendication "sub".
     *
     * @throws AuthenticationException en cas de jeton absent, malforme,
     *                                 expire ou de signature invalide
     */
    public function utilisateurDepuisEntete(?string $enteteAuthorization): int
    {
        if ($enteteAuthorization === null || !str_starts_with($enteteAuthorization, 'Bearer ')) {
            throw new AuthenticationException('En-tete Authorization Bearer manquant.');
        }

        $jwt = substr($enteteAuthorization, 7);

        return $this->validerEtExtraireUtilisateur($jwt);
    }

    /**
     * Valide un JWT brut et renvoie l'identifiant utilisateur (claim "sub").
     *
     * @throws AuthenticationException
     */
    public function validerEtExtraireUtilisateur(string $jwt): int
    {
        $segments = explode('.', $jwt);

        if (count($segments) !== 3) {
            throw new AuthenticationException('Jeton JWT malforme.');
        }

        [$enteteEncodee, $chargeEncodee, $signatureEncodee] = $segments;

        $entete = $this->decoderJson($enteteEncodee, 'en-tete');

        if (($entete['alg'] ?? null) !== 'HS256') {
            // On refuse explicitement tout algorithme autre que HS256,
            // notamment "none", pour contrer la confusion d'algorithme.
            throw new AuthenticationException('Algorithme de signature non autorise.');
        }

        $signatureAttendue = hash_hmac(
            'sha256',
            $enteteEncodee . '.' . $chargeEncodee,
            $this->cleSecrete,
            true,
        );
        $signatureFournie = $this->base64UrlDecode($signatureEncodee);

        if (!hash_equals($signatureAttendue, $signatureFournie)) {
            throw new AuthenticationException('Signature JWT invalide.');
        }

        $charge = $this->decoderJson($chargeEncodee, 'charge utile');

        $this->verifierExpiration($charge);

        if (!isset($charge['sub']) || !is_numeric($charge['sub'])) {
            throw new AuthenticationException('Revendication "sub" absente ou invalide.');
        }

        $userId = (int) $charge['sub'];

        if ($userId <= 0) {
            throw new AuthenticationException('Identifiant utilisateur invalide.');
        }

        return $userId;
    }

    /**
     * Verifie que le jeton n'est ni expire (exp) ni utilise avant sa date
     * d'activation (nbf), lorsque ces revendications sont presentes.
     *
     * @param array<string, mixed> $charge
     */
    private function verifierExpiration(array $charge): void
    {
        $maintenant = $this->clock->maintenant()->getTimestamp();

        if (isset($charge['exp']) && is_numeric($charge['exp']) && $maintenant >= (int) $charge['exp']) {
            throw new AuthenticationException('Jeton JWT expire.');
        }

        if (isset($charge['nbf']) && is_numeric($charge['nbf']) && $maintenant < (int) $charge['nbf']) {
            throw new AuthenticationException('Jeton JWT pas encore valide.');
        }
    }

    /**
     * Decode un segment base64url en tableau associatif JSON.
     *
     * @return array<string, mixed>
     */
    private function decoderJson(string $segment, string $libelle): array
    {
        $json = $this->base64UrlDecode($segment);
        $donnees = json_decode($json, true);

        if (!is_array($donnees)) {
            throw new AuthenticationException(sprintf('Segment %s du JWT illisible.', $libelle));
        }

        /** @var array<string, mixed> $donnees */
        return $donnees;
    }

    private function base64UrlDecode(string $valeur): string
    {
        $remplissage = strlen($valeur) % 4;

        if ($remplissage > 0) {
            $valeur .= str_repeat('=', 4 - $remplissage);
        }

        $decode = base64_decode(strtr($valeur, '-_', '+/'), true);

        if ($decode === false) {
            throw new AuthenticationException('Encodage base64url du JWT invalide.');
        }

        return $decode;
    }
}
