# Documentation Technique - ARTEDU

## Table des matières

1. [Présentation du projet](#présentation-du-projet)
2. [Installation et configuration](#installation-et-configuration)
3. [Création de la base de données](#création-de-la-base-de-données)
4. [Architecture du projet](#architecture-du-projet)
5. [Fonctionnement du CRUD](#fonctionnement-du-crud)
6. [Entités et relations](#entités-et-relations)
7. [Commandes disponibles](#commandes-disponibles)
8. [Structure des fichiers](#structure-des-fichiers)

---

## Présentation du projet

**ARTEDU** est une application web de gestion de sponsoring développée avec **Symfony 6.4**. Elle permet de gérer :
- Les sponsors
- Les contrats de sponsoring
- Les parrainages
- Les événements

### Technologies utilisées

- **Framework** : Symfony 6.4
- **Base de données** : MySQL/MariaDB
- **ORM** : Doctrine
- **Templates** : Twig
- **Frontend** : Bootstrap 5, CSS personnalisé
- **PHP** : >= 8.1

---

## Installation et configuration

### Prérequis

- PHP >= 8.1
- Composer
- MySQL/MariaDB
- Serveur web (Apache/Nginx) ou serveur de développement Symfony

### Installation

1. **Cloner le projet** (si applicable)
   ```bash
   git clone <repository-url>
   cd my_ARTEDU
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer les variables d'environnement**
   
   Copiez le fichier `.env` en `.env.local` et configurez :
   ```env
   # Base de données
   DATABASE_URL="mysql://root:password@127.0.0.1:3306/art?serverVersion=8.0.32&charset=utf8mb4"
   
   # Email
   MAILER_DSN="smtp://user:password@smtp.gmail.com:587"
   ADMIN_EMAIL="votre-email@example.com"
   ADMIN_NAME="Votre Nom"
   
   # Secret de l'application
   APP_SECRET=votre_secret_aleatoire
   ```

---

## Création de la base de données

### Méthode 1 : Via les migrations Doctrine (Recommandé)

1. **Créer la base de données manuellement**
   ```sql
   CREATE DATABASE art CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Vérifier la configuration dans `.env.local`**
   ```env
   DATABASE_URL="mysql://root:password@127.0.0.1:3306/art?serverVersion=8.0.32&charset=utf8mb4"
   ```

3. **Exécuter les migrations**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

   Cette commande va :
   - Créer toutes les tables nécessaires
   - Appliquer les contraintes de clés étrangères
   - Créer les index

### Méthode 2 : Via Doctrine Schema Update

```bash
# Mettre à jour le schéma directement depuis les entités
php bin/console doctrine:schema:update --force
```

### Structure de la base de données

Le système crée automatiquement les tables suivantes :

- **sponsor** : Informations sur les sponsors
- **sponsor_contract** : Contrats de sponsoring
- **sponsorship** : Parrainages
- **event** : Événements
- **event_sponsor** : Table de liaison entre événements et sponsors
- **messenger_messages** : Messages pour le système de messagerie asynchrone

### Vérification

Pour vérifier que la base de données est correctement créée :

```bash
php bin/console doctrine:schema:validate
```

---

## Architecture du projet

### Structure MVC

Le projet suit l'architecture **MVC (Model-View-Controller)** :

```
src/
├── Controller/     # Contrôleurs (logique métier)
├── Entity/         # Modèles (entités Doctrine)
├── Repository/     # Accès aux données
├── Form/           # Formulaires Symfony
├── Service/        # Services métier
└── Command/        # Commandes console
```

### Flux de données

```
Requête HTTP
    ↓
Route (routes.yaml ou attributs)
    ↓
Controller
    ↓
Repository (si besoin de requêtes complexes)
    ↓
Entity (modèle de données)
    ↓
Template Twig (vue)
    ↓
Réponse HTTP
```

---

## Fonctionnement du CRUD

### 1. CREATE (Création)

#### Exemple : Créer un sponsor

**Route** : `GET /sponsor/new` → `POST /sponsor/new`

**Controller** : `SponsorController::new()`

**Processus** :

1. **Affichage du formulaire** (`GET`)
   ```php
   // src/Controller/SponsorController.php
   public function new(Request $request, ...): Response
   {
       $sponsor = new Sponsor();
       $form = $this->createForm(SponsorType::class, $sponsor);
       // Affiche le formulaire
   }
   ```

2. **Traitement du formulaire** (`POST`)
   ```php
   $form->handleRequest($request);
   if ($form->isSubmitted() && $form->isValid()) {
       // Upload du logo si présent
       $logoFile = $form->get('logoFile')->getData();
       if ($logoFile) {
           $logoFilename = $fileUploader->upload($logoFile, 'sponsor_logo');
           $sponsor->setLogo($logoFilename);
       }
       
       // Sauvegarde en base
       $entityManager->persist($sponsor);
       $entityManager->flush();
       
       // Redirection avec message de succès
       $this->addFlash('success', 'Le sponsor a été créé avec succès.');
       return $this->redirectToRoute('app_sponsor_index');
   }
   ```

**Fichiers impliqués** :
- `src/Controller/SponsorController.php` (méthode `new()`)
- `src/Form/SponsorType.php` (définition du formulaire)
- `templates/sponsor/new.html.twig` (template du formulaire)
- `src/Entity/Sponsor.php` (entité avec validations)

### 2. READ (Lecture)

#### Exemple : Lister les sponsors

**Route** : `GET /sponsor/`

**Controller** : `SponsorController::index()`

**Processus** :

```php
public function index(Request $request, SponsorRepository $sponsorRepository): Response
{
    // Recherche depuis la navbar
    $searchQuery = $request->query->get('q', '');
    
    // Récupération des sponsors
    $sponsors = $searchQuery
        ? $sponsorRepository->searchByCriteria(['search' => $searchQuery])
        : $sponsorRepository->findAll();
    
    return $this->render('sponsor/index.html.twig', [
        'sponsors' => $sponsors,
    ]);
}
```

**Repository** : `SponsorRepository::findAll()` ou `searchByCriteria()`

**Template** : `templates/sponsor/index.html.twig`

#### Exemple : Voir un sponsor

**Route** : `GET /sponsor/{id}`

**Controller** : `SponsorController::show()`

```php
public function show(Sponsor $sponsor): Response
{
    return $this->render('sponsor/show.html.twig', [
        'sponsor' => $sponsor,
    ]);
}
```

**Note** : Symfony utilise le **ParamConverter** pour récupérer automatiquement l'entité depuis l'ID.

### 3. UPDATE (Modification)

#### Exemple : Modifier un sponsor

**Route** : `GET /sponsor/{id}/edit` → `POST /sponsor/{id}/edit`

**Controller** : `SponsorController::edit()`

**Processus** :

```php
public function edit(Request $request, Sponsor $sponsor, ...): Response
{
    $oldLogo = $sponsor->getLogo();
    $form = $this->createForm(SponsorType::class, $sponsor);
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        // Gestion du logo
        $logoFile = $form->get('logoFile')->getData();
        if ($logoFile) {
            // Supprimer l'ancien logo
            if ($oldLogo) {
                $fileUploader->remove($oldLogo);
            }
            // Upload du nouveau logo
            $logoFilename = $fileUploader->upload($logoFile, 'sponsor_logo');
            $sponsor->setLogo($logoFilename);
        }
        
        // Mise à jour en base
        $entityManager->flush();
        
        $this->addFlash('success', 'Le sponsor a été modifié avec succès.');
        return $this->redirectToRoute('app_sponsor_index');
    }
    
    return $this->render('sponsor/edit.html.twig', [
        'sponsor' => $sponsor,
        'form' => $form,
    ]);
}
```

### 4. DELETE (Suppression)

#### Exemple : Supprimer un sponsor

**Route** : `POST /sponsor/{id}/delete`

**Controller** : `SponsorController::delete()`

**Processus** :

```php
public function delete(Request $request, Sponsor $sponsor, ...): Response
{
    // Vérification du token CSRF
    if ($this->isCsrfTokenValid('delete'.$sponsor->getId(), 
        $request->request->getString('_token'))) {
        
        // Supprimer le logo si existe
        if ($sponsor->getLogo()) {
            $fileUploader->remove($sponsor->getLogo());
        }
        
        // Suppression en base
        $entityManager->remove($sponsor);
        $entityManager->flush();
        
        $this->addFlash('success', 'Le sponsor a été supprimé avec succès.');
    }
    
    return $this->redirectToRoute('app_sponsor_index');
}
```

**Template** : `templates/sponsor/_delete_form.html.twig` (formulaire de suppression)

---

## Entités et relations

### Diagramme des relations

```
Sponsor (1) ────< (N) SponsorContract
   │
   │ (1)
   │
   └───< (N) Sponsorship

Event (N) ────< (N) Sponsor (via event_sponsor)
```

### Entités principales

#### 1. Sponsor

**Fichier** : `src/Entity/Sponsor.php`

**Propriétés** :
- `id` : Identifiant unique
- `name` : Nom du sponsor
- `email` : Email (validé)
- `phone` : Téléphone
- `city` : Ville
- `type` : Type (Enum : ENTREPRISE, INDIVIDU, ASSOCIATION)
- `logo` : Nom du fichier logo
- `website` : Site web

**Relations** :
- `OneToMany` avec `SponsorContract`
- `OneToMany` avec `Sponsorship`
- `ManyToMany` avec `Event`

#### 2. SponsorContract

**Fichier** : `src/Entity/SponsorContract.php`

**Propriétés** :
- `id` : Identifiant unique
- `contractNumber` : Numéro de contrat
- `signedAt` : Date de signature
- `expiresAt` : Date d'expiration
- `level` : Niveau (Enum : BRONZE, SILVER, GOLD, PLATINUM)
- `terms` : Conditions du contrat

**Relations** :
- `ManyToOne` avec `Sponsor`

#### 3. Sponsorship

**Fichier** : `src/Entity/Sponsorship.php`

**Propriétés** :
- `id` : Identifiant unique
- `description` : Description
- `type` : Type de sponsor
- `sponsorshipType` : Type de parrainage (Enum)
- `amount` : Montant

**Relations** :
- `ManyToOne` avec `Sponsor`

#### 4. Event

**Fichier** : `src/Entity/Event.php`

**Propriétés** :
- `id` : Identifiant unique
- `title` : Titre
- `description` : Description
- `startDate` : Date de début
- `endDate` : Date de fin
- `location` : Lieu

**Relations** :
- `ManyToMany` avec `Sponsor`

---

## Commandes disponibles

### Commandes de gestion

```bash
# Vider le cache
php bin/console cache:clear

# Valider le schéma de base de données
php bin/console doctrine:schema:validate

# Créer une migration
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Mettre à jour le schéma
php bin/console doctrine:schema:update --force
```

### Commandes métier

```bash
# Notifier les contrats expirants (7 jours par défaut)
php bin/console app:notify-expiring-contracts

# Notifier avec un nombre de jours personnalisé
php bin/console app:notify-expiring-contracts --days=10

# Tester l'envoi d'email
php bin/console app:test-email

# Tester les notifications
php bin/console app:test-notification
```

---

## Structure des fichiers

### Contrôleurs

```
src/Controller/
├── SponsorController.php          # CRUD Sponsors
├── SponsorContractController.php  # CRUD Contrats
├── SponsorshipController.php      # CRUD Parrainages
├── EventController.php            # CRUD Événements
├── HomeController.php             # Page d'accueil
└── SponsoringController.php       # Page d'accueil sponsoring
```

### Entités

```
src/Entity/
├── Sponsor.php           # Entité Sponsor
├── SponsorContract.php   # Entité Contrat
├── Sponsorship.php       # Entité Parrainage
└── Event.php             # Entité Événement
```

### Formulaires

```
src/Form/
├── SponsorType.php           # Formulaire Sponsor
├── SponsorContractType.php  # Formulaire Contrat
├── SponsorshipType.php       # Formulaire Parrainage
├── EventType.php             # Formulaire Événement
└── SponsorSearchType.php     # Formulaire de recherche
```

### Repositories

```
src/Repository/
├── SponsorRepository.php          # Requêtes Sponsors
├── SponsorContractRepository.php  # Requêtes Contrats
├── SponsorshipRepository.php      # Requêtes Parrainages
└── EventRepository.php            # Requêtes Événements
```

### Services

```
src/Service/
├── FileUploader.php                # Gestion des uploads
└── ContractNotificationService.php # Notifications par email
```

### Templates

```
templates/
├── base.html.twig              # Template de base
├── partials/                   # Partials réutilisables
│   ├── _navbar.html.twig
│   ├── _sidebar.html.twig
│   └── _footer.html.twig
├── sponsor/                    # Templates Sponsors
│   ├── index.html.twig
│   ├── new.html.twig
│   ├── edit.html.twig
│   ├── show.html.twig
│   └── _form.html.twig
├── sponsor_contract/           # Templates Contrats
├── sponsorship/                # Templates Parrainages
└── event/                      # Templates Événements
```

---

## Validation des données

### Exemple : Validation d'un Sponsor

Les validations sont définies directement dans l'entité :

```php
#[ORM\Column(length: 255)]
#[Assert\NotBlank(message: 'Le nom est obligatoire')]
#[Assert\Regex(
    pattern: '/^[a-zA-ZÀ-ÿ\s\-\']+$/u',
    message: 'Le nom ne peut contenir que des lettres...'
)]
private ?string $name = null;
```

**Types de validations utilisées** :
- `NotBlank` : Champ obligatoire
- `Length` : Longueur min/max
- `Regex` : Format spécifique
- `Email` : Format email
- `Positive` : Nombre positif
- `Callback` : Validation personnalisée

---

## Upload de fichiers

### Service FileUploader

**Fichier** : `src/Service/FileUploader.php`

**Utilisation** :

```php
// Dans le controller
$logoFile = $form->get('logoFile')->getData();
if ($logoFile) {
    $logoFilename = $fileUploader->upload($logoFile, 'sponsor_logo');
    $sponsor->setLogo($logoFilename);
}
```

**Répertoire** : `public/uploads/sponsors/`

---

## Notifications par email

### Service ContractNotificationService

**Fichier** : `src/Service/ContractNotificationService.php`

**Fonctionnalité** : Envoie des emails aux administrateurs pour les contrats expirant bientôt.

**Configuration** :
- `ADMIN_EMAIL` : Email de l'administrateur
- `ADMIN_NAME` : Nom de l'administrateur
- `MAILER_DSN` : Configuration SMTP

**Commande** :
```bash
php bin/console app:notify-expiring-contracts --days=7
```

---

## Sécurité

### Protection CSRF

Tous les formulaires incluent automatiquement un token CSRF :

```twig
{{ form_widget(form._token) }}
```

### Validation des tokens

```php
if ($this->isCsrfTokenValid('delete'.$entity->getId(), 
    $request->request->getString('_token'))) {
    // Action sécurisée
}
```

---

## Routes

### Définition des routes

Les routes sont définies via **attributs PHP 8** :

```php
#[Route('/sponsor')]
final class SponsorController extends AbstractController
{
    #[Route('/', name: 'app_sponsor_index')]
    public function index(): Response { ... }
    
    #[Route('/new', name: 'app_sponsor_new')]
    public function new(): Response { ... }
    
    #[Route('/{id}', name: 'app_sponsor_show')]
    public function show(Sponsor $sponsor): Response { ... }
    
    #[Route('/{id}/edit', name: 'app_sponsor_edit')]
    public function edit(): Response { ... }
    
    #[Route('/{id}/delete', name: 'app_sponsor_delete', methods: ['POST'])]
    public function delete(): Response { ... }
}
```

### Liste des routes principales

- `/` : Page d'accueil
- `/sponsor/` : Liste des sponsors
- `/sponsor/new` : Créer un sponsor
- `/sponsor/{id}` : Voir un sponsor
- `/sponsor/{id}/edit` : Modifier un sponsor
- `/sponsor/contract/` : Liste des contrats
- `/sponsorship/` : Liste des parrainages
- `/event/` : Liste des événements

---

## Débogage

### Mode debug

Le mode debug est activé en développement (`APP_ENV=dev`).

### Logs

Les logs sont disponibles dans :
- `var/log/dev.log` (environnement de développement)
- `var/log/prod.log` (environnement de production)

### Web Profiler

En mode développement, le Web Profiler Symfony est disponible pour :
- Analyser les requêtes
- Voir les requêtes SQL
- Déboguer les performances

---

## Support et maintenance

### Commandes utiles

```bash
# Vérifier la configuration
php bin/console debug:container

# Voir les routes
php bin/console debug:router

# Voir les services
php bin/console debug:autowiring

# Vider le cache
php bin/console cache:clear
```

### Problèmes courants

1. **Erreur de connexion à la base de données**
   - Vérifier `DATABASE_URL` dans `.env.local`
   - Vérifier que MySQL/MariaDB est démarré

2. **Erreur de permissions**
   - Vérifier les permissions sur `var/` et `public/uploads/`

3. **Cache non vidé**
   - Exécuter `php bin/console cache:clear`

---

## Conclusion

Cette documentation couvre les aspects essentiels du projet ARTEDU. Pour plus d'informations, consultez :
- [Documentation Symfony](https://symfony.com/doc/6.4/)
- [Documentation Doctrine](https://www.doctrine-project.org/)

**Version** : 1.0  
**Dernière mise à jour** : Décembre 2025


