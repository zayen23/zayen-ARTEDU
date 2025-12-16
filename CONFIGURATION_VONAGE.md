# Configuration Vonage/Nexmo pour les SMS

## Prérequis

1. Créer un compte sur [Vonage](https://www.vonage.com/) (anciennement Nexmo)
2. Obtenir vos clés API :
   - API Key
   - API Secret
   - Numéro d'expéditeur (From Number)

## Configuration

### 1. Variables d'environnement

Ajoutez dans votre fichier `.env.local` :

```env
# Configuration Vonage/Nexmo
VONAGE_DSN=vonage://VOTRE_API_KEY:VOTRE_API_SECRET@default?from=VOTRE_NUMERO_EXPEDITEUR

# Numéro de téléphone de l'administrateur (format international avec +)
ADMIN_PHONE=+21629526614
```

### 2. Format du DSN Vonage

Le DSN Vonage suit ce format :
```
vonage://API_KEY:API_SECRET@default?from=FROM_NUMBER
```

Exemple :
```
vonage://12345678:abcdefghijklmnop@default?from=+33123456789
```

### 3. Format du numéro d'expéditeur

- Le numéro d'expéditeur doit être au format international avec le préfixe `+`
- Exemple pour la France : `+33123456789`
- Exemple pour la Tunisie : `+21612345678`

### 4. Format du numéro de destination

- Le numéro de destination (`ADMIN_PHONE`) doit également être au format international avec le préfixe `+`
- Exemple : `+21629526614`

## Test

Pour tester l'envoi de SMS :

```bash
php bin/console app:notify-expiring-contracts
```

Ou avec un nombre de jours spécifique :

```bash
php bin/console app:notify-expiring-contracts --days=7
```

## Vérification

1. Vérifiez les logs dans `var/log/dev.log` ou `var/log/prod.log`
2. Vérifiez votre téléphone pour recevoir le SMS
3. Vérifiez le tableau de bord Vonage pour voir les statistiques d'envoi

## Dépannage

### Erreur : "Invalid credentials"
- Vérifiez que votre `VONAGE_DSN` contient bien les bonnes clés API
- Vérifiez que les clés API sont actives dans votre compte Vonage

### Erreur : "Invalid from number"
- Vérifiez que le numéro d'expéditeur est valide et vérifié dans votre compte Vonage
- Assurez-vous que le format est correct (avec le préfixe `+`)

### SMS non reçu
- Vérifiez que le numéro de destination est au format international
- Vérifiez que votre compte Vonage a des crédits suffisants
- Vérifiez les logs pour voir les erreurs détaillées

## Envoi synchrone

**⚠️ IMPORTANT :** Les SMS sont envoyés de manière **synchrone** (immédiatement) car :

- MariaDB ne supporte pas la fonctionnalité `SKIP LOCKED` utilisée par Symfony Messenger
- Cela garantit que les SMS sont envoyés immédiatement sans dépendre d'un worker
- **Vous n'avez PAS besoin de lancer `messenger:consume` pour les SMS**
- L'erreur `SKIP LOCKED` que vous pourriez voir en lançant le worker **n'affecte PAS l'envoi des SMS**

**Note :** Si vous utilisez MySQL au lieu de MariaDB, vous pouvez activer l'envoi asynchrone en décommentant le routage dans `config/packages/messenger.yaml` :

```yaml
routing:
    'Symfony\Component\Notifier\Message\SmsMessage': async
```

Puis lancer le worker :
```bash
php bin/console messenger:consume async -vv
```

## Support

Pour plus d'informations, consultez :
- [Documentation Vonage](https://developer.vonage.com/)
- [Documentation Symfony Notifier](https://symfony.com/doc/current/notifier.html)

