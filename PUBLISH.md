# Publication du depot

Commandes pour publier ce projet sur GitHub (compte `adh-studio`).
Le depot local est deja initialise et commite.

## Creer le depot distant et pousser

```bash
# Depuis la racine du projet (dossier contenant composer.json)
gh repo create taskforge-notifications \
  --public \
  --description "Microservice PHP de notifications et rapports pour TaskForge (PHP 8.2, MySQL, JWT, PHPUnit, CI)" \
  --source . \
  --push
```

## Verifier l'integration continue

Le workflow `.github/workflows/ci.yml` se declenche automatiquement au push.
Pour suivre son execution :

```bash
gh run watch
# ou
gh run list --limit 1
```

La CI installe PHP 8.2 et MySQL 8, verifie le schema de production puis execute
la suite PHPUnit. Elle doit passer au vert des le premier push.

## Note

Ce projet n'a pas d'artefact visuel (service d'API) : l'activation de GitHub
Pages n'est pas necessaire.
