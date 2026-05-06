# 2PHPD TOURNOIS SPORT EN SYMFONY
Projet réalisé avec **PHP 8.5** et **Symfony 6.4** afin de gerer les tournois.
L’application permet de :

gérer des utilisateurs,
gérer des tournois,
gérer les inscriptions,
gérer les matchs,
gérer les scores,
attribuer un gagnant,
accéder à une interface d’administration.

### Prérequis 
Pour réaliser ce projet vous aurez besoins de :
PHP 8.5
Symfony CLI
Wamp ou Xamp(PhpMyAdmin)
Composer

### Technologies utilisées
Voici un recapitulatif de toutes les technologie utilisés pour ce projet:
PHP 8.5
Symfony 6.4
Doctrine ORM
Doctrine Migrations
PhpMyAdmin
Composer
Symfony Security
PHPUnit

### Extensions PHP 
Dans le fichier php.ini il faut modifier ses extensions afin de les activer:
extension=pdo_mysql
extension=mysqli
extension=ctype
extension=iconv

## Installation du projet
 Une fois le fichier dézippé il faudra lancer les commandes suivantes:

 ```bash 
cd tournois-sport-api
composer install
 ```

Dans le fichier .env modifier:

```
DATABASE_URL="mysql://root:@127.0.0.1:3306/tournois_sport_api"

```
Adapter :

le nom d’utilisateur MySQL,
le mot de passe,
le nom de la base de données.

## Création de la base de données

Pour créer la base de donner il faurt lancer la commande suivante:

```bash
php bin/console doctrine:database:create
```

## Génération des migrations

D'abord il faut créer les migrations avec la commande suivante:
```bash
php bin/console make:migration
```

et pour les executer :

```bash
php bin/console doctrine:migrations:migrate
```
## Lancer le projet

Pour lancer le projet il faut d'abord démarrer le serveur Symfony:

```bash
symfony server:start
```
## Authentification

Le projet utilise le système de sécurité Symfony.

Routes principales :

|Route            | Description              |
| --------------- | ------------------------ |
| `/login-admin`  | Connexion administrateur |
| `/logout-admin` | Déconnexion              |
| `/admin-ui`     | Dashboard administrateur |

## Routes principales admin

| Méthode | Route                                  | Description               |
| ------- | -------------------------------------- | ------------------------- |
| GET     | `/login-admin`                         | Page de connexion         |
| GET     | `/admin-ui`                            | Dashboard administrateur  |
| POST    | `/admin-ui/registrations/{id}/confirm` | Confirmer une inscription |
| POST    | `/admin-ui/matches/{id}/scores`        | Modifier les scores       |
| POST    | `/admin-ui/tournaments/{id}/winner`    | Définir un vainqueur      |

## Structure du projet

```txt
src/
 ├── Controller/
 ├── Entity/
 ├── Repository/
 ├── Service/
 ├── DataFixtures/
 └── Command/

config/
public/
migrations/
tests/
```

## Commandes utiles

### Pour voir les routes

#### Depuis la console

```bash
php bin/console debug:router
```

#### Depuis le swagger

Accéder à cette route :

```bash
http://127.0.0.1:8000/swagger.html
```

### Pour effectuer les tests

```bash
php bin/phpunit
```