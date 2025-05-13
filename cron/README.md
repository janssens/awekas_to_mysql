# Scripts CRON

Ce répertoire contient les scripts destinés à être exécutés périodiquement via cron.

## Vérification de l'âge des données (`check_data_age.php`)

Ce script vérifie si les données météo sont à jour et envoie des notifications si nécessaire.

### Configuration du CRON

Pour exécuter la vérification toutes les 15 minutes, ajoutez la ligne suivante à votre crontab :

```bash
*/15 * * * * /usr/bin/php /chemin/vers/meteo_farm/cron/check_data_age.php >> /var/log/meteo_farm/data_check.log 2>&1
```

Pour éditer votre crontab :
```bash
crontab -e
```

### Codes de retour

Le script utilise différents codes de retour pour indiquer son état :
- 0 : Les données sont à jour
- 1 : Les données sont obsolètes (> 1 heure)
- 2 : Erreur lors de l'exécution

### Journalisation

Le script produit des messages détaillés qui peuvent être redirigés vers un fichier de log :
- État de la vérification
- Horodatage de la dernière mise à jour
- Messages d'erreur éventuels

### Notifications

Si les données sont obsolètes :
- Une notification Telegram sera envoyée (si configurée)
- Un délai minimum d'une heure est respecté entre les notifications
- L'état de la dernière notification est stocké dans `/data/last_stale_notification.txt`

### Prérequis

- PHP CLI
- Accès à la base de données
- Configuration Telegram (optionnel)
- Droits d'écriture sur le dossier `/data` 