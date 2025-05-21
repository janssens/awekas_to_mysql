# Station Météo

Ce projet est un système de surveillance météorologique qui collecte, stocke et affiche les données d'une station météo AWEKAS. Il comprend également un système d'alertes configurable avec notifications push et Telegram.

## Fonctionnalités

- Collecte automatique des données météorologiques via l'API AWEKAS
- Affichage en temps réel des mesures météorologiques
- Support pour plusieurs capteurs (température, humidité, pression, etc.)
- Système d'alertes configurable
- Notifications push via le navigateur
- Notifications Telegram
- Surveillance automatique de l'état de la station
- Interface de gestion des alertes
- Statistiques et historique des mesures

## Structure du Projet

- `/config` : Fichiers de configuration (measurements.php, etc.)
- `/cron` : Scripts d'automatisation (mise à jour des données, vérification des alertes)
- `/includes` : Classes et fonctions PHP
- `/js` : Scripts JavaScript (service worker, notifications push)
- `/sql` : Scripts SQL et migrations

## Configuration Requise

- PHP 7.4 ou supérieur
- MySQL/MariaDB
- Serveur Web (Apache/Nginx)
- Accès à l'API AWEKAS
- Clés VAPID pour les notifications push
- Bot Telegram (optionnel)

## Installation

1. Cloner le dépôt
2. Copier `config.example.php` vers `config.php` et configurer les paramètres
3. Importer les scripts SQL nécessaires
4. Configurer les tâches cron pour la mise à jour automatique
5. Configurer les clés VAPID pour les notifications push
6. Configurer le bot Telegram (optionnel)

## Tâches Cron

Voir le fichier `/cron/README.md` pour plus de détails sur la configuration des tâches automatisées. 