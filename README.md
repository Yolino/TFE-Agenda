# agenda

Voici les étapes nécessaires pour pouvoir lancer l'application sur votre machine.

1.  Mettre à jour les dépendances
2.  Lancer l'application

_(installation de Laravel détaillée, juste pour rappel)_

## Installation (déjà faite, ne _pas_ exécuter)

Installer **Laravel** :

    composer create-project laravel/laravel egestione
    cd egestione

Installer **Laravel UI** :

    composer require laravel/ui

Installer **Livewire** :

    composer require livewire/livewire

Installer **Glide (pour le croping des images)** :

    composer require league/glide-laravel

Installer **TailwindCSS** :

    npm install
    npm install -D tailwindcss postcss autoprefixer
    npx tailwindcss init -p

-   Suivre [ce lien](https://tailwindcss.com/docs/installation) pour les étapes de configuration.

Installer **DaisyUI** :

    npm i -D daisyui@latest

-   Suivre [ce lien](https://daisyui.com/docs/install) pour les étapes de configuration.

## Etape 1 : Mettre à jour les dépendances

Pour mettre à jour les dépendances de **composer** (+ dossier vendor):

    composer update

Pour mettre à jour les dépendances de **npm** (+ dossier node_modules) :

    npm install

## Etape 2 : Lancer l'application

Pour lancer le serveur de développement de Laravel et Vite en même temps :

    npm run watch
