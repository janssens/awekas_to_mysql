# Tâches Automatisées

Ce dossier contient les scripts qui doivent être exécutés périodiquement pour maintenir le système de surveillance météo à jour.

## Scripts Disponibles

### update_weather_data.php

Script principal de mise à jour des données météorologiques. Il :
- Récupère les dernières données depuis l'API AWEKAS
- Les enregistre dans la base de données
- Vérifie l'état de la station (en ligne/hors ligne)
- Envoie des notifications si la station change d'état

**Fréquence recommandée** : Toutes les 5 minutes

```bash
*/5 * * * * php /chemin/vers/cron/update_weather_data.php
```

### check_alerts.php

Vérifie les conditions d'alerte configurées et envoie des notifications si nécessaire.
- Compare les mesures actuelles avec les seuils configurés
- Envoie des notifications push et/ou Telegram si les conditions sont remplies
- Gère l'historique des alertes

**Fréquence recommandée** : Toutes les 5 minutes

```bash
*/5 * * * * php /chemin/vers/cron/check_alerts.php
```

## Configuration des Tâches Cron

1. Ouvrir l'éditeur crontab :
```bash
crontab -e
```

2. Ajouter les lignes suivantes (en ajustant les chemins) :
```bash
# Mise à jour des données météo
*/5 * * * * php /chemin/complet/vers/cron/update_weather_data.php

# Vérification des alertes
*/5 * * * * php /chemin/complet/vers/cron/check_alerts.php
```

## Logs

Les scripts génèrent des messages de log standards qui peuvent être redirigés vers un fichier :

```bash
*/5 * * * * php /chemin/vers/cron/update_weather_data.php >> /var/log/meteo_farm/update.log 2>&1
*/5 * * * * php /chemin/vers/cron/check_alerts.php >> /var/log/meteo_farm/alerts.log 2>&1
```

## Dépannage

Si les scripts ne semblent pas s'exécuter :
1. Vérifier les permissions des fichiers
2. Vérifier les logs pour les erreurs
3. S'assurer que PHP CLI est installé
4. Vérifier que les chemins sont corrects
5. Vérifier la configuration dans `config.php` 