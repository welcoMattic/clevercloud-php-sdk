# Clever Cloud Mini Dashboard

Petite app **Symfony 8** qui démontre tout le SDK [`clevercloud/sdk`](../) :

- Double authentification : **API token (Bearer)** ou **OAuth 1.0a 3-legged**
- Liste des organisations
- Liste des applications (perso + par organisation)
- **Page de détail d'application** : domaines, branches Git, redirections TCP,
  déploiement avec/sans SHA, restart, stop
- Liste des add-ons (perso + par organisation)
- **Page de détail d'add-on** : migrations (annulation incluse), backups
  (téléchargement + restauration)
- **Gestion des tokens API** via `api-bridge.clever-cloud.com` (CRUD)

![Capture d'écran à venir](https://placehold.co/600x300?text=Clever+Cloud+Mini+Dashboard)

## Pré-requis

- PHP **8.5+** (le SDK utilise property hooks, asymmetric visibility, etc.)
- [Symfony CLI](https://symfony.com/download) pour le serveur de dev TLS
- Un compte Clever Cloud

## Authentification

Deux modes au choix, tous deux disponibles sur `/login`.

### Mode 1 — API token (recommandé)

Le plus simple : pas de configuration côté démo.

1. Connecte-toi à la [console Clever Cloud](https://console.clever-cloud.com/).
2. Section *Personal API tokens* → crée un token (donne-lui un nom, des scopes
   adaptés à ce que tu veux tester).
3. Copie la valeur en clair (elle ne sera plus affichée).
4. Lance la démo, va sur `/login` → *Se connecter avec un token* → colle.

Tu peux ensuite gérer tes autres tokens directement depuis la page **API tokens**
de la démo (CRUD complet via `api-bridge.clever-cloud.com`).

### Mode 2 — OAuth 1.0a 3-legged

Flow historique. Demande de créer un *OAuth consumer* dans la console et de
configurer la démo.

1. Console Clever Cloud → ton organisation (ou compte perso) → *OAuth consumers*
   → *Create a consumer*.
2. Renseigne :
   - **Name** : `Mini Dashboard` (libre)
   - **URL** et **Base URL** : `https://localhost:8765/`
   - **Rights / Scopes** : au minimum `Read applications`, `Manage applications`,
     `Read addons`, `Read organisations`
3. La console te montre **un consumer key et un consumer secret**.
4. Configure la démo :

   ```bash
   cd demo
   composer install
   cp .env.local.dist .env.local
   $EDITOR .env.local
   ```

   ```dotenv
   CC_CONSUMER_KEY=ton-consumer-key
   CC_CONSUMER_SECRET=ton-consumer-secret
   ```

5. Lance la démo → `/login` → *Se connecter via OAuth* → autorise sur la console
   → tu reviens loggé.

> Si tu vois *« OAuth callback is invalid »*, l'URL du consumer n'est pas
> alignée avec ce que la démo envoie (`https://localhost:8765/oauth/callback`).
> Édite le consumer dans la console.

## Lancer le serveur

```bash
symfony server:ca:install       # une seule fois, pour le TLS local
symfony server:start --port=8765
```

Ouvre **[https://localhost:8765](https://localhost:8765)**.

## Comment c'est câblé

| Fichier                                          | Rôle                                                            |
| ------------------------------------------------ | --------------------------------------------------------------- |
| `src/Service/ClevercloudClientFactory.php`       | Construit le `Client` à partir des creds session (Bearer OU OAuth1) |
| `src/Controller/SecurityController.php`          | Login UI + OAuth1 flow + API token form                          |
| `src/Controller/ApplicationController.php`       | List + show (vhosts/branches/TCP) + deploy/restart/stop          |
| `src/Controller/AddonController.php`             | List + show (migrations/backups) + restore/cancel                |
| `src/Controller/ApiTokensController.php`         | CRUD tokens via api-bridge                                       |
| `src/Exception/NotAuthenticatedException.php`    | Levée si pas de creds en session                                 |
| `src/EventListener/NotAuthenticatedListener.php` | Catche et redirige vers `/login`                                 |
| `config/services.yaml`                           | DI + `Client` en `lazy: true`                                    |

## Routes

| Route                                                 | Méthode  | Action                                              |
| ----------------------------------------------------- | -------- | --------------------------------------------------- |
| `/login`                                              | GET      | Page de choix de méthode (Bearer / OAuth1)          |
| `/login/oauth`                                        | POST     | Démarre le flow OAuth 1.0a                          |
| `/oauth/callback`                                     | GET      | Reçoit le verifier, échange le token                |
| `/login/token`                                        | GET/POST | Formulaire d'API token + sauvegarde session        |
| `/logout`                                             | POST     | Vide la session                                     |
| `/`                                                   | GET      | Accueil (user + organisations)                      |
| `/organisations`                                      | GET      | Liste détaillée                                     |
| `/applications`                                       | GET      | Apps de l'owner sélectionné                         |
| `/applications/{id}`                                  | GET      | Détail d'app (vhosts + branches + TCP redirs)       |
| `/applications/{id}/deploy`                           | POST     | Déploiement avec / sans SHA de commit               |
| `/applications/{id}/restart`                          | POST     | Redéploiement HEAD                                  |
| `/applications/{id}/stop`                             | POST     | Arrêt                                               |
| `/applications/{id}/tcp-redirs`                       | POST     | Ouverture d'un port TCP sur un namespace            |
| `/applications/{id}/tcp-redirs/{port}`                | POST     | Fermeture d'un port TCP                             |
| `/addons`                                             | GET      | Add-ons de l'owner sélectionné                      |
| `/addons/{id}`                                        | GET      | Détail d'add-on (migrations + backups)              |
| `/addons/{id}/backups/{backupId}/restore`             | POST     | Restauration d'un backup                            |
| `/addons/{id}/migrations/{migrationId}/cancel`        | POST     | Annulation d'une migration en cours                 |
| `/api-tokens`                                         | GET      | Liste des tokens (api-bridge, Bearer requis)        |
| `/api-tokens`                                         | POST     | Création d'un nouveau token                         |
| `/api-tokens/{id}`                                    | POST     | Révocation d'un token                               |

## Notes

- **Le token / les creds sont stockés dans la session PHP** (fichier sur disque
  par défaut). Pour qu'ils survivent aux redémarrages, branche un store
  persistant (Redis, DB).
- **API tokens** : la page `/api-tokens` n'est accessible qu'aux sessions
  authentifiées en Bearer. Un utilisateur connecté en OAuth1 voit un message
  l'invitant à se reconnecter via token.
- **Backups** : disponibles seulement si l'add-on a un `provider` et un `realId`
  exploitables côté api-bridge. La page affiche une note neutre si le provider
  ne supporte pas l'endpoint.
- **Pas de Doctrine** dans la démo (la dépendance est tirée par le pack
  webapp ; on ne s'en sert pas).
