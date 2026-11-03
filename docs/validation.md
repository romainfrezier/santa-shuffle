# Validation locale — 3 novembre 2026

## Socle

Squelette officiel Laravel créé avec Composer. Versions verrouillées : Laravel 13.40.0, inertia-laravel 3.7.0, React 19.3.1, adaptateur React Inertia 3.11.0, Vite 8.3.5, TypeScript 7.0.2. Runtime local PHP 8.5.6 et Node 24.22.1. Les guides officiels utilisés sont liés dans README.md.

## Résultats

- SQLite : `php artisan test` — 18 tests, 3 090 assertions, succès.
- PostgreSQL 18 isolé : même suite — 18 tests, 3 090 assertions, succès. Base de test distincte, port local 55439.
- Cas couverts : unicité du destinataire, interdiction de s’offrir un cadeau, exclusions, impossibilité, confirmations nécessaires, tirage unique, confidentialité des réponses, expiration des accès, mutations après tirage interdites, limite de requêtes séparée par action, chiffrage des jobs et rollback complet si l’envoi en file échoue.
- TypeScript, build Vite, Pint, Prettier et validation stricte Composer : succès.
- Composer audit et npm audit des dépendances de production : aucun avis connu remonté au moment du contrôle. Ce n’est pas un audit de sécurité exhaustif.
- Image Docker reconstruite avec les derniers correctifs ; app saine, worker démarré, accueil HTTP 200, exécution en UID 33 (`www-data`). Création réelle d’un événement fictif depuis le navigateur Docker : session, redirection vers la gestion et persistance validées. POST sans jeton CSRF : HTTP 419, localement et dans Docker.
- Fichiers Compose SQLite et fusion PostgreSQL valides. Migrations SQLite exécutées dans le conteneur. La variante Compose PostgreSQL n’a pas été démarrée intégralement : PostgreSQL a été vérifié par la suite applicative sur son conteneur isolé.
- Navigateur : création d’un événement et de trois participants fictifs ; confirmation des liens produits par la file ; ajout d’une exclusion ; tirage ; révélation privée réussie. L’organisateur voit les ouvertures sans recevoir les destinataires.
- Rendu desktop 1280 px et mobile 390 px inspecté. Absence de débordement horizontal à 390 px. Enveloppe mobile corrigée après inspection. Captures locales dans `artifacts/` (ignorées par Git).
- Sept e-mails fictifs traités dans le parcours hors Docker via le transport local `log` (création, trois confirmations et trois annonces de tirage). Aucun e-mail externe envoyé.

## Points découverts et corrigés

Les limites numériques Laravel partageaient un compteur par IP, ce qui bloquait le premier tirage après plusieurs confirmations. Elles sont maintenant nommées et séparées par action ; un test reproduit tout le parcours. L’ajout d’une exclusion valide ses participants sous le même verrou que le tirage. L’historique Inertia est chiffré et effacé à la déconnexion.

## Limites de la preuve

Aucun dépôt distant créé, aucun workflow GitHub Actions exécuté, aucune image publiée, aucun déploiement VPS. Le SMTP réel, le domaine, le proxy HTTPS et les sauvegardes de production restent à configurer et à vérifier avant les invitations de l’équipe.

Sur ce Mac, les plages automatiques Docker étaient épuisées : seul le réseau local Santa Shuffle a été créé avec le sous-réseau 10.231.127.0/24. Aucun réseau ou conteneur existant d’un autre projet n’a été supprimé. Cette particularité locale n’est pas imposée dans le Compose distribué.
