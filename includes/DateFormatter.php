<?php

class DateFormatter {
    private static $joursSemaine = [
        'Monday' => 'lundi',
        'Tuesday' => 'mardi',
        'Wednesday' => 'mercredi',
        'Thursday' => 'jeudi',
        'Friday' => 'vendredi',
        'Saturday' => 'samedi',
        'Sunday' => 'dimanche'
    ];
    
    private static $mois = [
        'January' => 'janvier',
        'February' => 'février',
        'March' => 'mars',
        'April' => 'avril',
        'May' => 'mai',
        'June' => 'juin',
        'July' => 'juillet',
        'August' => 'août',
        'September' => 'septembre',
        'October' => 'octobre',
        'November' => 'novembre',
        'December' => 'décembre'
    ];

    public static function formatFrench($timestamp) {
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        
        $jourSemaine = self::$joursSemaine[$date->format('l')];
        $jour = $date->format('j');
        $mois = self::$mois[$date->format('F')];
        $heure = $date->format('H');
        $minutes = $date->format('i');
        
        return "$jourSemaine $jour $mois à {$heure}h$minutes";
    }
} 