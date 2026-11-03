# Santa Shuffle

Un cadeau. Un secret. Un bon moment.

Secret Santa open source, auto-hébergeable, en français. Laravel 13, React 19, Inertia 3 et TypeScript. SQLite par défaut, PostgreSQL au choix. Licence MIT.

## Le parcours

Créez un événement (nom, date, budget en euros, message), partagez son lien, puis attendez les confirmations par e-mail. Les participants peuvent ajouter leurs envies. L’organisateur définit des exclusions directionnelles et lance un tirage définitif. Chaque personne découvre uniquement son destinataire dans une enveloppe privée. De 3 à 100 participants.

## Développement local

Prérequis : PHP 8.4+ avec PDO SQLite (PDO PostgreSQL pour PostgreSQL), Composer 2, Node.js 24 et npm. Le verrou Composer a été résolu et testé sous PHP 8.5.

```sh
cp .env.example .env
composer install
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
npm ci
npm run build
composer run dev
```

Ouvrez http://localhost:8000. `composer run dev` démarre le serveur, Vite et le worker de file. `MAIL_MAILER=log` écrit les e-mails dans `storage/logs/laravel.log` **en développement seulement**. Cela inclut les liens privés. Pour de vrais envois, configurez `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME` et `MAIL_FROM_ADDRESS`. Pour un serveur SMTP local sans authentification, utilisez des identifiants vides.

## Docker : SQLite

```sh
cp .env.docker.example .env.docker
docker compose --env-file .env.docker build
# Copiez la clé générée dans APP_KEY de .env.docker, une seule fois.
docker compose --env-file .env.docker run --rm --no-deps --entrypoint php app artisan key:generate --show
docker compose --env-file .env.docker run --rm --no-deps app php artisan migrate --force
docker compose --env-file .env.docker up -d
```

L’application écoute sur http://localhost:8080, uniquement en loopback. L’application et son worker utilisent le même volume persistant `storage`. Les migrations sont une action explicite, jamais exécutées automatiquement au démarrage.

Avant les invitations réelles, configurez le SMTP. Pour le VPS, placez votre reverse proxy HTTPS devant le port 8080, définissez `APP_URL=https://votre-domaine` et `SESSION_SECURE_COOKIE=true`. Les URL sont forcées sur l’origine APP_URL en production, sans faire confiance à tous les proxys. Si votre proxy est dans Docker, reliez-le au réseau de l’application et pointez vers `app:8080`. Ne publiez pas directement HTTP sur Internet.

## Docker : PostgreSQL

Définissez `POSTGRES_PASSWORD` dans `.env.docker`, puis utilisez **les deux fichiers à chaque commande** :

```sh
docker compose --env-file .env.docker -f compose.yaml -f compose.postgres.yaml up -d postgres
docker compose --env-file .env.docker -f compose.yaml -f compose.postgres.yaml run --rm app php artisan migrate --force
docker compose --env-file .env.docker -f compose.yaml -f compose.postgres.yaml up -d
```

Construisez d’abord l’image et générez APP_KEY comme dans la section SQLite. Le second fichier remplace la connexion des deux services par PostgreSQL 18 et ajoute un volume persistant. Pour une base externe, gardez le premier fichier et renseignez les variables Laravel `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` dans `.env.docker`. Changer le moteur ne migre pas les données existantes.

## Vérifications

```sh
php artisan test
vendor/bin/pint --test
npm run typecheck
npm run format:check
npm run build
```

La CI exécute les mêmes tests avec SQLite et PostgreSQL. Sa configuration est fournie ; aucun succès GitHub Actions n’est revendiqué sans exécution sur GitHub. Voir [docs/validation.md](docs/validation.md) pour les vérifications réellement effectuées.

## Exploitation

- État : `docker compose --env-file .env.docker ps` ; `/up` vérifie que Laravel démarre (ce n’est pas une preuve de bon fonctionnement du SMTP).
- E-mails : `docker compose --env-file .env.docker exec worker php artisan queue:failed`, puis `queue:retry all` une fois le SMTP corrigé. Trois tentatives automatiques ; un doublon reste possible si le serveur accepte un e-mail avant une coupure de connexion. Les liens sont réutilisables.
- Mise à jour : sauvegardez, reconstruisez l’image, lancez explicitement les migrations, puis `up -d`. Redémarrez le worker avec l’application. Les tags de base Docker suivent leur branche stable ; conservez le digest de l’image déployée pour un retour arrière reproductible.
- Sauvegarde SQLite : arrêtez temporairement **app et worker**, sauvegardez le volume `storage` et `.env.docker` dans un emplacement privé, puis redémarrez. Pour PostgreSQL utilisez `pg_dump` et sauvegardez également APP_KEY. Testez la restauration hors production.
- N’exécutez pas `down -v` sur une installation à conserver : cela supprime ses volumes.
- Les accès expirent 30 jours après la date des cadeaux. Les données restent en base jusqu’à suppression par l’exploitant ; aucune purge automatique dans cette version.

## Confidentialité et limites

Les liens personnels sont des secrets d’accès : seuls les destinataires doivent les recevoir. Tokens aléatoires, empreintes SHA-256 pour recherche, tokens participants et jobs chiffrés au repos avec APP_KEY. Confirmation explicite par POST pour éviter qu’un scanner d’e-mail confirme une inscription en visitant un lien. Sessions HTTP-only, CSRF Laravel, limites de requêtes, réponses privées non mises en cache et politique sans référent. Ne journalisez pas les URL `/access/*` dans votre reverse proxy.

L’interface organisateur ne reçoit ni les e-mails des participants, ni leurs envies, ni les attributions. **L’administrateur du serveur peut lire la base et les attributions** : il ne s’agit pas de chiffrement de bout en bout. Une personne qui possède le lien d’invitation peut s’inscrire ; partagez-le uniquement avec le groupe. Les protections anti-abus conviennent à une instance d’équipe ; un service public à grande échelle demanderait une politique d’accès supplémentaire.

Le tirage utilise un couplage biparti à chemins augmentants avec ordre aléatoire cryptographiquement sûr. Il trouve une solution si elle existe, sans boucle de tentatives aléatoires. Chaque participant donne et reçoit exactement une fois. Il n’est pas uniforme sur l’ensemble des tirages valides. Les exclusions sont directionnelles. Le tirage ne peut pas être relancé, et les inscriptions/modifications sont fermées après celui-ci.

## Documentation officielle utilisée

Le setup suit les documentations des versions installées, consultées le 3 novembre 2026 :

- [Laravel : installation](https://laravel.com/docs/13.x/installation), [starter kits React](https://laravel.com/docs/13.x/starter-kits), [base de données](https://laravel.com/docs/13.x/database), [déploiement](https://laravel.com/docs/13.x/deployment), [files de tâches](https://laravel.com/docs/13.x/queues).
- [Inertia 3 : installation serveur](https://inertiajs.com/docs/v3/installation/server-side-setup), [client React et Vite](https://inertiajs.com/docs/v3/installation/client-side-setup), [formulaires](https://inertiajs.com/docs/v3/the-basics/forms).
- [React : créer une application](https://react.dev/learn/creating-a-react-app).
- [FrankenPHP : image Docker](https://frankenphp.dev/docs/docker/), [Docker Compose : fusion de fichiers](https://docs.docker.com/compose/how-tos/multiple-compose-files/merge/), [image officielle PostgreSQL](https://hub.docker.com/_/postgres).

Le squelette provient de `composer create-project laravel/laravel`. Inertia est installé manuellement selon son guide officiel, pour conserver un parcours par liens privés sans comptes utilisateurs. CSS natif, police Manrope auto-hébergée via Fontsource (licence OFL incluse dans le paquet). Aucun asset Infomaniak réutilisé.
