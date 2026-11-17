<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Http;

/**
 * Representation immuable d'une requete HTTP entrante.
 *
 * Encapsule la methode, le chemin, les parametres de requete, les en-tetes
 * et le corps JSON deja decode, pour decoupler les controleurs des
 * superglobales PHP.
 */
final class Request
{
    /**
     * @param array<string, string> $query
     * @param array<string, mixed>  $corps
     * @param array<string, string> $entetes
     */
    public function __construct(
        public readonly string $methode,
        public readonly string $chemin,
        public readonly array $query,
        public readonly array $corps,
        public readonly array $entetes,
    ) {
    }

    /**
     * Construit une requete depuis l'environnement serveur courant.
     */
    public static function depuisGlobals(): self
    {
        $methode = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $chemin = parse_url($uri, PHP_URL_PATH) ?: '/';

        $corpsBrut = file_get_contents('php://input') ?: '';
        $corps = [];

        if ($corpsBrut !== '') {
            $decode = json_decode($corpsBrut, true);
            $corps = is_array($decode) ? $decode : [];
        }

        return new self(
            methode: strtoupper((string) $methode),
            chemin: $chemin,
            query: self::nettoyerChaines($_GET),
            corps: $corps,
            entetes: self::entetesDepuisServeur(),
        );
    }

    public function entete(string $nom): ?string
    {
        return $this->entetes[strtolower($nom)] ?? null;
    }

    public function parametreQuery(string $nom): ?string
    {
        return $this->query[$nom] ?? null;
    }

    /**
     * @param array<mixed> $source
     *
     * @return array<string, string>
     */
    private static function nettoyerChaines(array $source): array
    {
        $resultat = [];

        foreach ($source as $cle => $valeur) {
            if (is_string($valeur)) {
                $resultat[(string) $cle] = $valeur;
            }
        }

        return $resultat;
    }

    /**
     * @return array<string, string>
     */
    private static function entetesDepuisServeur(): array
    {
        $entetes = [];

        foreach ($_SERVER as $cle => $valeur) {
            if (is_string($cle) && str_starts_with($cle, 'HTTP_') && is_string($valeur)) {
                $nom = strtolower(str_replace('_', '-', substr($cle, 5)));
                $entetes[$nom] = $valeur;
            }
        }

        return $entetes;
    }
}
