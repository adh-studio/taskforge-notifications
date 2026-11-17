# Documentation de l'API

Microservice de notifications de TaskForge. Toutes les routes attendent et
renvoient du JSON. L'authentification se fait par jeton JWT (HS256) emis par
`taskforge-api`, transmis dans l'en-tete `Authorization: Bearer <jwt>`.

La revendication `sub` du jeton identifie l'utilisateur courant. Les routes de
lecture et de marquage operent uniquement sur les notifications de cet
utilisateur.

## Codes de statut communs

| Code | Signification |
|------|---------------|
| 200  | Succes |
| 201  | Ressource creee |
| 401  | Jeton absent, expire ou invalide |
| 403  | Acces refuse (gere via 404 pour ne pas divulguer l'existence) |
| 404  | Ressource ou route introuvable |
| 422  | Charge utile invalide |
| 500  | Erreur interne |

## POST /events

Enregistre un evenement metier et cree la notification associee. Appele par
`taskforge-api` lorsqu'une tache est creee ou terminee.

Corps de la requete :

```json
{
  "user_id": 10,
  "task_id": 55,
  "event_type": "task.created",
  "task_title": "Rediger la specification"
}
```

`event_type` accepte `task.created` ou `task.completed`.

Reponse `201` :

```json
{
  "id": 1,
  "user_id": 10,
  "event_type": "task.created",
  "task_id": 55,
  "message": "Nouvelle tache creee : Rediger la specification",
  "status": "unread",
  "created_at": "2026-01-10T12:00:00+00:00",
  "read_at": null
}
```

## GET /notifications

Liste les notifications de l'utilisateur du jeton, les plus recentes d'abord.

Parametre de requete optionnel : `status` = `unread` ou `read`.

Reponse `200` :

```json
{
  "data": [
    {
      "id": 2,
      "user_id": 10,
      "event_type": "task.completed",
      "task_id": 55,
      "message": "Tache terminee : Rediger la specification",
      "status": "unread",
      "created_at": "2026-01-11T09:00:00+00:00",
      "read_at": null
    }
  ]
}
```

## PATCH /notifications/{id}/read

Marque une notification comme lue. Operation idempotente : un second appel
renvoie l'etat courant sans modifier la date de lecture initiale. Une
notification appartenant a un autre utilisateur est traitee comme introuvable.

Reponse `200` : la notification mise a jour (meme structure que ci-dessus,
avec `status` a `read` et `read_at` renseigne).

## GET /reports/summary

Rapport agrege du nombre de notifications par statut pour l'utilisateur.

Reponse `200` :

```json
{
  "user_id": 10,
  "total": 3,
  "par_statut": {
    "unread": 2,
    "read": 1
  }
}
```
