# TaskForge Notifications

Microservice PHP charge des notifications et des rapports de la plateforme
TaskForge. Il recoit les evenements metier emis par `taskforge-api` (creation
et cloture de taches), en derive des notifications persistees en base, puis
expose une API RESTful pour les consulter, les marquer comme lues et produire
un rapport agrege par statut.

## Contexte

Dans une architecture orientee services, l'envoi et le suivi des notifications
sont isoles du service principal de gestion des taches. Ce depot illustre ce
decoupage : un service autonome, sans framework lourd, avec son propre schema
de base, son authentification par jeton partage et sa suite de tests.

## Stack technique

- PHP 8.2, typage strict (`declare(strict_types=1)`), conforme PSR-12
- Autoload PSR-4 via Composer
- MySQL 8 en production (PDO, requetes preparees)
- SQLite en memoire pour les tests, grace a un SQL portable
- Routeur minimal maison (aucune dependance HTTP externe)
- PHPUnit 11 pour les tests unitaires et d'integration
- GitHub Actions pour l'integration continue

## Architecture

Organisation en couches, avec inversion des dependances (les services
dependent d'interfaces, pas d'implementations concretes) :

```
src/
  Domain/        Entites et regles metier (Notification, enums, exceptions)
  Repository/    Contrat de persistance + implementation PDO
  Service/       Logique applicative (NotificationService, ReportService)
  Auth/          Validation des jetons JWT HS256
  Http/          Request, Response, Router, Kernel, Controller
  Database/      Fabrique PDO et schema SQLite de test
  Support/       Abstraction d'horloge (Clock) pour un temps testable
public/
  index.php      Point d'entree et composition des dependances
migrations/      Schema MySQL versionne (.sql)
docs/API.md      Documentation des endpoints
tests/           Suite PHPUnit
```

Principes appliques : responsabilite unique (un service par preoccupation),
ouverture/fermeture et inversion de dependances (interfaces de repository et
d'horloge injectees), entites immuables, gestion centralisee des erreurs.

### Endpoints

| Methode | Chemin | Role |
|---------|--------|------|
| POST  | `/events` | Enregistre un evenement et cree la notification |
| GET   | `/notifications` | Liste les notifications (filtre `?status=`) |
| PATCH | `/notifications/{id}/read` | Marque une notification comme lue |
| GET   | `/reports/summary` | Rapport agrege par statut |

Detail complet dans [`docs/API.md`](docs/API.md).

## Securite

- Authentification par JWT HS256 signe avec une cle partagee avec
  `taskforge-api` ; verification de signature en temps constant (`hash_equals`)
  et refus explicite de tout algorithme autre que HS256 (anti confusion
  d'algorithme, dont `none`).
- Aucun secret code en dur : cle JWT et identifiants de base proviennent de
  l'environnement (voir `.env.example`).
- Requetes SQL exclusivement preparees et parametrees, emulation desactivee
  (protection contre l'injection, OWASP A03).
- Controle d'acces horizontal : un utilisateur n'agit que sur ses propres
  notifications, verifie avant toute mutation (OWASP A01).
- En-tetes de securite sur chaque reponse (`X-Content-Type-Options`,
  `X-Frame-Options`, `Cache-Control`, `Referrer-Policy`) et messages d'erreur
  sans fuite de details techniques.

## Installation

Prerequis : PHP 8.2+ et Composer.

```bash
composer install
cp .env.example .env   # renseigner la cle JWT et l'acces MySQL
```

Creation de la base de production :

```bash
mysql -u root -p taskforge_notifications < migrations/001_create_notifications.sql
```

Lancement local :

```bash
php -S 127.0.0.1:8080 -t public
```

## Tests

Commande unique :

```bash
composer test
```

Les tests s'executent sur une base SQLite en memoire : rapides, isoles, sans
service externe. Le repository etant ecrit en SQL portable, le meme code
alimente SQLite (tests) et MySQL (production).

> Note sur l'environnement : la toolchain PHP n'est pas disponible sur le poste
> de developpement utilise pour ce projet. La suite de tests est donc executee
> par l'integration continue (GitHub Actions), qui installe PHP 8.2 et MySQL 8,
> verifie le schema de production puis lance PHPUnit a chaque push. Le workflow
> est defini dans `.github/workflows/ci.yml`.

## Ce que ce projet demontre pour un poste Full Stack

- **Backend PHP** : microservice PHP 8.2 structure, typage strict, PSR-4/PSR-12,
  sans framework lourd, avec routeur et noyau HTTP maison.
- **Bases SQL** : modelisation, schema versionne (migrations), acces PDO avec
  requetes preparees, portabilite MySQL/SQLite, index adaptes aux requetes.
- **API RESTful** : ressources, verbes HTTP adaptes, codes de statut coherents,
  documentation d'API dediee.
- **Authentification / autorisation** : verification de JWT HS256, controle
  d'acces par ressource, defense en temps constant.
- **Securite et OWASP Top 10** : anti-injection, gestion des secrets, controle
  d'acces, en-tetes securises, non divulgation d'informations.
- **Genie logiciel** : principes SOLID, clean architecture en couches,
  injection de dependances, entites immuables, exceptions metier typees.
- **Tests** : suite unitaire et d'integration PHPUnit (domaine, repository,
  services, authentification), horloge injectable pour un temps deterministe.
- **DevOps / CI** : pipeline GitHub Actions reproductible avec service MySQL,
  verification de schema et execution automatique des tests.
- **Documentation technique** : README de production, documentation d'API,
  schema de base versionne.
