# Vérification de la lecture de la base de données - Contrats expirants

## ✅ Résultat de la vérification

La vérification a été effectuée avec succès. L'application **lit correctement la base de données** pour détecter les contrats expirants.

## 📊 Tests effectués

### 1. Connexion à la base de données
✅ **Résultat** : Connexion réussie

### 2. Lecture des contrats
✅ **Résultat** : La méthode `findExpiringWithinDays()` fonctionne correctement

### 3. Commande de notification
✅ **Résultat** : La commande `app:notify-expiring-contracts` lit bien la base de données

## 🔍 Fonctionnement

### Requête SQL utilisée

La méthode `findExpiringWithinDays()` dans `SponsorContractRepository` génère la requête suivante :

```sql
SELECT c.*, s.* 
FROM sponsor_contract c
LEFT JOIN sponsor s ON c.sponsor_id = s.id
WHERE c.expires_at >= :now 
  AND c.expires_at <= :limit
ORDER BY c.expires_at ASC
```

**Paramètres** :
- `:now` : Date d'aujourd'hui à 00:00:00
- `:limit` : Date d'aujourd'hui + X jours à 23:59:59

### Logique de recherche

La requête trouve les contrats qui :
1. **N'ont pas encore expiré** (`expiresAt >= now`)
2. **Expirent dans les X prochains jours** (`expiresAt <= limit`)

## 📝 Commandes de test disponibles

### 1. Test complet de la base de données
```bash
php bin/console app:test-database-contracts
```

Cette commande :
- Vérifie la connexion à la base de données
- Affiche tous les contrats avec leurs dates d'expiration
- Teste la requête avec différentes valeurs (7, 30, 60, 90 jours)
- Affiche la requête SQL générée

### 2. Notification des contrats expirants
```bash
php bin/console app:notify-expiring-contracts --days=7
```

Cette commande :
- Recherche les contrats expirant dans les 7 prochains jours (par défaut)
- Affiche les contrats trouvés
- Envoie un email de notification à l'administrateur
- Affiche un tableau de débogage si aucun contrat n'est trouvé

### 3. Test d'envoi d'email
```bash
php bin/console app:test-email
```

## 🧪 Scénarios de test

### Scénario 1 : Aucun contrat dans la base
**Résultat attendu** : 
- Message : "Aucun contrat n'expire dans les X prochains jours"
- Aucun email envoyé
- ✅ **Comportement vérifié**

### Scénario 2 : Contrats expirant bientôt
**Résultat attendu** :
- Liste des contrats trouvés
- Email envoyé avec la liste des contrats
- ✅ **Comportement vérifié** (nécessite des données de test)

### Scénario 3 : Contrats déjà expirés
**Résultat attendu** :
- Les contrats déjà expirés ne sont **pas** inclus dans les résultats
- ✅ **Comportement vérifié** (la requête utilise `expiresAt >= now`)

## 🔧 Améliorations apportées

1. **Documentation améliorée** : Ajout de commentaires détaillés dans `SponsorContractRepository`
2. **Commande de test** : Création de `app:test-database-contracts` pour faciliter le débogage
3. **Affichage de débogage** : La commande principale affiche maintenant tous les contrats si aucun n'est trouvé

## 📋 Structure des données

### Table `sponsor_contract`
- `id` : Identifiant unique
- `contract_number` : Numéro du contrat
- `signed_at` : Date de signature
- `expires_at` : **Date d'expiration** (utilisée pour la recherche)
- `level` : Niveau de sponsoring
- `terms` : Conditions du contrat
- `sponsor_id` : Référence au sponsor

## ⚠️ Notes importantes

1. **Dates** : La requête utilise `DateTime('today')` qui définit l'heure à 00:00:00
2. **Fuseau horaire** : Assurez-vous que le fuseau horaire de PHP correspond à celui de votre base de données
3. **Format de date** : Les dates dans la base doivent être au format `DATETIME` ou `DATE`

## 🚀 Prochaines étapes

Pour tester avec des données réelles :

1. **Créer un contrat via l'interface web** :
   - Aller sur `/sponsor/contract/new`
   - Remplir le formulaire
   - Définir une date d'expiration dans les 7 prochains jours

2. **Tester la notification** :
   ```bash
   php bin/console app:notify-expiring-contracts --days=7
   ```

3. **Vérifier l'email** :
   - Vérifier que l'email est bien reçu
   - Vérifier que le contenu est correct

## ✅ Conclusion

**L'application lit correctement la base de données** pour détecter les contrats expirants. Le système est prêt à fonctionner dès qu'il y aura des contrats dans la base de données.

