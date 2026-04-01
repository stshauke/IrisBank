# IrisBank

Application bancaire de simulation développée avec **Symfony 7.4** et **PHP 8.2+**.

---

## Fonctionnalités

### Espace client
- Inscription et connexion sécurisée (CSRF, throttling)
- Réinitialisation de mot de passe par email
- Tableau de bord avec solde total, comptes et transactions récentes
- Création de comptes bancaires (Courant, Livret A, PEL)
- Dépôt, retrait (max 1 000 €), virement (entre comptes ou par IBAN)
- Historique complet des transactions
- Gestion du profil (infos personnelles, changement de mot de passe)

### Espace administrateur
- Vue globale : clients, comptes, dépôts totaux, comptes bloqués
- Liste et recherche des clients
- Blocage / déblocage de comptes
- Consultation des transactions par compte

---

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | Symfony 7.4, PHP 8.2 |
| Base de données | MySQL 8 (Doctrine ORM) |
| Templates | Twig |
| Frontend | CSS custom, Stimulus, Turbo |
| Mailer | Symfony Mailer + MailHog (dev) |
| Auth | Symfony Security (form login, CSRF, remember me) |

---

## Prérequis

- PHP 8.2+
- Composer
- MySQL 8
- Symfony CLI (optionnel)
- MailHog (pour les emails en dev)

---

## Installation

```bash
# 1. Cloner le dépôt
git clone <url-du-repo>
cd IrisBank

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env .env.local
# Éditer .env.local : DATABASE_URL, APP_SECRET, MAILER_DSN
```

### Configuration `.env.local`

```env
APP_SECRET=votre_secret_ici

# MySQL
DATABASE_URL="mysql://root:@127.0.0.1:3306/irisbank?serverVersion=8.0&charset=utf8mb4"

# MailHog (dev)
MAILER_DSN=smtp://localhost:1025
```

### Base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load   # optionnel — données de test
```

---

## Lancer l'application

```bash
# Serveur de développement
symfony server:start
# ou
php -S localhost:8000 -t public/

# MailHog (emails en dev)
MailHog.exe
# Interface web : http://localhost:8025
```

---

## Comptes de test (fixtures)

| Rôle | Email | Mot de passe |
|---|---|---|
| Admin | admin@irisbank.fr | — |
| Client | alice@test.fr | — |

> Les mots de passe sont définis dans `src/DataFixtures/AppFixtures.php`.

---

## Architecture

```
src/
├── Controller/
│   ├── SecurityController.php        # Login, register
│   ├── PasswordResetController.php   # Reset password
│   ├── DashboardController.php
│   ├── CompteController.php
│   ├── TransactionController.php
│   ├── ProfilController.php
│   └── AdminController.php
├── Entity/
│   ├── User.php
│   ├── CompteBancaire.php
│   ├── Transaction.php
│   └── PasswordResetToken.php
├── Service/
│   └── BanqueService.php             # Toute la logique métier
└── Form/
    ├── RegistrationFormType.php
    ├── ProfilType.php
    ├── ChangePasswordType.php
    ├── RequestPasswordResetType.php
    ├── ResetPasswordType.php
    ├── DepotType.php
    ├── RetraitType.php
    └── VirementType.php

templates/
├── base.html.twig
├── dashboard/
├── compte/
├── transaction/
├── profil/
├── security/
├── admin/
└── emails/

public/css/irisbank.css               # Design system complet (light + dark)
```

### Règle fondamentale

> Toute la logique métier (IBAN, soldes, virements, limites) passe exclusivement par `BanqueService`. Les controllers ne contiennent aucune logique métier.

---

## Commandes utiles

```bash
# Cache
php bin/console cache:clear

# Migrations
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate

# Tests
php bin/phpunit
php bin/phpunit --coverage-html var/coverage

# Génération de code
php bin/console make:controller
php bin/console make:entity
php bin/console make:form
```

---

## Sécurité

- Authentification par formulaire avec CSRF
- Throttling login : 5 tentatives / 15 min
- Hachage bcrypt/argon2 des mots de passe
- Tokens de reset à usage unique, expiration 1h
- Contrôle d'accès par rôle (`ROLE_USER`, `ROLE_ADMIN`)
