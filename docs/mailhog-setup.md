# Setup MailHog — Emails en développement

MailHog intercepte tous les emails envoyés par l'application en local.  
Sans lui, la fonctionnalité **"Mot de passe oublié"** ne fonctionnera pas.

---

## Pourquoi MailHog ?

En développement, les emails ne sont **jamais envoyés réellement**.  
MailHog joue le rôle d'un faux serveur SMTP qui capture les emails et les affiche dans une interface web.

```
Symfony → SMTP :1025 → MailHog → Interface web :8025
```

---

## 1. Configuration du projet (déjà faite)

Votre `.env.local` doit contenir :

```env
MAILER_DSN=smtp://localhost:1025
```

> ⚠️ Ne modifiez **pas** le `.env` du projet. Créez un `.env.local` s'il n'existe pas.

---

## 2. Installation de MailHog

### Windows

Télécharger le binaire et le placer dans un dossier de votre `PATH` :

```powershell
# Télécharger
Invoke-WebRequest -Uri "https://github.com/mailhog/MailHog/releases/latest/download/MailHog_windows_amd64.exe" -OutFile "$env:USERPROFILE\bin\MailHog.exe"
```

Ou téléchargez manuellement depuis :  
**https://github.com/mailhog/MailHog/releases/latest** → `MailHog_windows_amd64.exe`

---

### macOS

```bash
brew install mailhog
```

---

### Linux (Debian/Ubuntu)

```bash
# Via Go
go install github.com/mailhog/MailHog@latest

# Ou téléchargement direct
wget https://github.com/mailhog/MailHog/releases/latest/download/MailHog_linux_amd64 -O ~/bin/mailhog
chmod +x ~/bin/mailhog
```

---

### Docker (toutes plateformes)

```bash
docker run -d --name mailhog -p 1025:1025 -p 8025:8025 mailhog/mailhog
```

---

## 3. Lancer MailHog

### Windows

```powershell
# Option 1 — Fenêtre normale
MailHog.exe

# Option 2 — En arrière-plan (minimisé)
Start-Process MailHog.exe -WindowStyle Minimized
```

### macOS / Linux

```bash
mailhog
# ou en arrière-plan
mailhog &
```

### Docker

```bash
# Démarrer
docker start mailhog

# Arrêter
docker stop mailhog
```

---

## 4. Vérifier que MailHog tourne

Ouvrez dans votre navigateur :

**http://localhost:8025**

Vous devez voir l'interface MailHog :

```
┌─────────────────────────────────────┐
│  MailHog   Inbox (0)                │
│  ─────────────────────────────────  │
│  No messages                        │
└─────────────────────────────────────┘
```

---

## 5. Tester

1. Lancez le serveur Symfony : `symfony server:start`
2. Allez sur **http://localhost:8000/mot-de-passe-oublie**
3. Entrez l'email d'un utilisateur existant (ex: `alice@test.fr`)
4. Cliquez **Envoyer le lien**
5. L'email apparaît instantanément dans **http://localhost:8025**

---

## 6. Dépannage

### "Connection refused" sur le port 1025

MailHog n'est pas lancé. Démarrez-le (voir étape 3).

### Le formulaire soumet mais aucun email dans MailHog

Vérifiez votre `.env.local` :
```env
MAILER_DSN=smtp://localhost:1025
```
Puis videz le cache :
```bash
php bin/console cache:clear
```

### Port 1025 ou 8025 déjà utilisé

Lancez MailHog sur des ports alternatifs et adaptez votre `.env.local` :

```bash
# Windows
MailHog.exe -smtp-bind-addr=0.0.0.0:2025 -ui-bind-addr=0.0.0.0:9025

# macOS/Linux
mailhog -smtp-bind-addr=0.0.0.0:2025 -ui-bind-addr=0.0.0.0:9025
```

```env
MAILER_DSN=smtp://localhost:2025
```

---

## Résumé

| Étape | Commande |
|---|---|
| Installer (Windows) | Télécharger `MailHog_windows_amd64.exe` |
| Installer (macOS) | `brew install mailhog` |
| Installer (Docker) | `docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog` |
| Lancer | `mailhog` ou `MailHog.exe` |
| Interface web | http://localhost:8025 |
| Config `.env.local` | `MAILER_DSN=smtp://localhost:1025` |
