# Clever Cloud Mini Dashboard

Petite app **Symfony 8** qui démontre le SDK [`clevercloud/sdk`](../) :

- Login **OAuth 1.0a 3-legged** complet (seuls les consumer key/secret sont
  pré-configurés ; le token utilisateur est obtenu automatiquement au login)
- Liste des organisations
- Liste des applications (perso + par organisation)
- Liste des add-ons (perso + par organisation, avec provider + plan typés)
- Bouton **Redéployer** (avec / sans cache) et **Stop** sur chaque application

![Capture d'écran à venir](https://placehold.co/600x300?text=Clever+Cloud+Mini+Dashboard)

## Pré-requis

- PHP **8.5+** (le SDK utilise property hooks, asymmetric visibility, etc.)
- [Symfony CLI](https://symfony.com/download) pour le serveur de dev TLS
- Un compte Clever Cloud

## Étape 1 — Créer un OAuth consumer dans la console

1. Connecte-toi à la [console Clever Cloud](https://console.clever-cloud.com/).
2. Ouvre les paramètres de ton organisation (ou ton compte perso) →
   *OAuth consumers* → *Create a consumer*.
3. Renseigne :
   - **Name** : `Mini Dashboard` (n'importe quoi)
   - **URL** : `https://localhost:8765/`
   - **Base URL** : `https://localhost:8765/`
   - **Picture** : optionnel
   - **Rights / Scopes** : coche au moins `Manage applications` (pour le
     redéploiement) et `Read applications`, `Read addons`, `Read organisations`
4. Valide → la console te montre **un consumer key et un consumer secret**.
   Note-les, on en a besoin à l'étape suivante.

> Si tu vois plus tard le message *« OAuth callback is invalid »* en lançant
> la démo, c'est que l'URL du consumer ne correspond pas exactement à ce que
> l'app envoie comme callback (`https://localhost:8765/oauth/callback`).
> Édite le consumer dans la console pour aligner l'URL.

## Étape 2 — Installer et configurer la démo

```bash
cd demo
composer install
cp .env.local.dist .env.local
$EDITOR .env.local
```

Dans `.env.local`, colle uniquement :

```dotenv
CC_CONSUMER_KEY=ton-consumer-key
CC_CONSUMER_SECRET=ton-consumer-secret
```

Le user token et son secret seront obtenus automatiquement via le flow OAuth.

## Étape 3 — Lancer le serveur

```bash
symfony server:ca:install       # une seule fois, pour le TLS local
symfony server:start --port=8765
```

Ouvre **[https://localhost:8765](https://localhost:8765)** :

1. tu seras redirigé vers `/login`
2. clique sur *Se connecter*
3. tu arrives sur Clever Cloud, autorise l'app
4. tu reviens sur la démo, loggé

## Flow OAuth en détails

```
        ┌─────────────────────────────────────────────────────────────┐
        │                                                             │
GET /login                                                            │
    │                                                                 │
    │   POST https://api.clever-cloud.com/v2/oauth/request_token      │
    │   (signé HMAC-SHA512 avec consumer key/secret + oauth_callback) │
    ▼                                                                 │
[request_token + secret] ─── stockés en session ────────┐             │
    │                                                   │             │
    │   302 → https://api.clever-cloud.com/v2/oauth/    │             │
    │         authorize?oauth_token=…                   │             │
    ▼                                                   │             │
Utilisateur autorise sur la console Clever Cloud        │             │
    │                                                   │             │
    │   302 → /oauth/callback?oauth_token=…             │             │
    │         &oauth_verifier=…                         │             │
    ▼                                                   ▼             │
GET /oauth/callback                                                   │
    │                                                                 │
    │   POST .../v2/oauth/access_token                                │
    │   (signé avec request_token + verifier)                         │
    ▼                                                                 │
[user_token + secret] ─── stockés en session ─────────────────────────┘
    │
    │   302 → /
    ▼
Dashboard (Client SDK construit à partir de la session)
```

## Comment c'est câblé

| Fichier                                          | Rôle                                                            |
| ------------------------------------------------ | --------------------------------------------------------------- |
| `src/Service/ClevercloudClientFactory.php`       | Construit le `CleverCloud\Sdk\Client` à partir env + session     |
| `src/Controller/SecurityController.php`          | Orchestre le 3-legged dance via `CleverCloud\Sdk\Auth\OAuthFlow` |
| `src/Exception/NotAuthenticatedException.php`    | Levée si pas de user token en session                            |
| `src/EventListener/NotAuthenticatedListener.php` | Catche l'exception et redirige vers `/login`                     |
| `config/services.yaml`                           | Wiring DI + alias PSR-17/18 + `Client` en `lazy: true`           |

## Routes

| Route                                | Méthode | Action                                |
| ------------------------------------ | ------- | ------------------------------------- |
| `/login`                             | GET     | Démarre le flow OAuth                 |
| `/oauth/callback`                    | GET     | Reçoit le verifier, échange le token  |
| `/logout`                            | POST    | Vide la session                       |
| `/`                                  | GET     | Accueil (user + organisations)        |
| `/organisations`                     | GET     | Liste détaillée                       |
| `/applications?owner=<id>`           | GET     | Apps (self ou organisation)           |
| `/applications/{id}/restart`         | POST    | Redéploiement (avec/sans cache)       |
| `/applications/{id}/stop`            | POST    | Arrêt                                 |
| `/addons?owner=<id>`                 | GET     | Add-ons (self ou organisation)        |

## Notes

- **Le user token est stocké dans la session PHP** (fichier sur disque par
  défaut). Si tu veux qu'il survive aux redémarrages du serveur, il faut un
  store persistant (Redis, DB, etc.).
- **Le redéploiement** appelle `POST /v2/{owner}/applications/{id}/instances`
  via `CleverCloud\Sdk\Client::applications->restart()`. Le bouton « sans
  cache » ajoute `?useCache=no`.
- **Pas de Doctrine** dans la démo (la dépendance est tirée par le pack
  webapp ; on ne s'en sert pas).
