<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Http;

use TaskForge\Notifications\Domain\Exception\NotFoundException;

/**
 * Routeur minimal maison.
 *
 * Associe un couple (methode, motif de chemin) a un gestionnaire. Les
 * segments dynamiques sont declares avec la syntaxe {nom} et transmis au
 * gestionnaire sous forme de tableau de parametres. Volontairement sans
 * dependance externe pour garder le microservice leger.
 */
final class Router
{
    /**
     * @var list<array{methode: string, regex: string, params: list<string>, gestionnaire: callable}>
     */
    private array $routes = [];

    /**
     * @param callable(Request, array<string, string>): Response $gestionnaire
     */
    public function ajouter(string $methode, string $motif, callable $gestionnaire): void
    {
        [$regex, $params] = $this->compiler($motif);

        $this->routes[] = [
            'methode' => strtoupper($methode),
            'regex' => $regex,
            'params' => $params,
            'gestionnaire' => $gestionnaire,
        ];
    }

    /**
     * Resout la requete vers un gestionnaire et renvoie sa reponse.
     *
     * @throws NotFoundException si aucune route ne correspond
     */
    public function resoudre(Request $requete): Response
    {
        foreach ($this->routes as $route) {
            if ($route['methode'] !== $requete->methode) {
                continue;
            }

            if (preg_match($route['regex'], $requete->chemin, $correspondances) !== 1) {
                continue;
            }

            $params = [];

            foreach ($route['params'] as $nom) {
                $params[$nom] = $correspondances[$nom];
            }

            return ($route['gestionnaire'])($requete, $params);
        }

        throw new NotFoundException('Route introuvable.');
    }

    /**
     * Compile un motif "/notifications/{id}" en expression reguliere.
     *
     * @return array{0: string, 1: list<string>}
     */
    private function compiler(string $motif): array
    {
        $params = [];

        $regex = preg_replace_callback(
            '#\{([a-zA-Z_]+)\}#',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];

                return '(?P<' . $m[1] . '>[^/]+)';
            },
            $motif,
        );

        return ['#^' . $regex . '$#', $params];
    }
}
