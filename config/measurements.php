<?php
return [
    // Mesures principales
    'temperature' => ['name' => 'Température extérieure', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'humidity' => ['name' => 'Humidité extérieure', 'unit' => '%', 'format' => function($val) { return round($val); }],
    'dewpoint' => ['name' => 'Point de rosée', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'windchill' => ['name' => 'Température ressentie', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'wetbulbtemperature' => ['name' => 'Température humide', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'windspeed' => ['name' => 'Vitesse du vent', 'unit' => 'km/h', 'format' => function($val) { return number_format($val, 1); }],
    'gustspeed' => ['name' => 'Rafales', 'unit' => 'km/h', 'format' => function($val) { return number_format($val, 1); }],
    'winddirection' => ['name' => 'Direction du vent', 'unit' => '°', 'format' => function($val) { return round($val); }],
    'uv' => ['name' => 'Index UV', 'unit' => '', 'format' => function($val) { return number_format($val, 1); }],
    'solar' => ['name' => 'Rayonnement solaire', 'unit' => 'W/m²', 'format' => function($val) { return round($val); }],
    'precipitation' => ['name' => 'Précipitations', 'unit' => 'mm', 'format' => function($val) { return number_format($val, 1); }],

    // Sondes de température additionnelles
    'temp1' => ['name' => 'Température sonde 1', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'temp2' => ['name' => 'Température sonde 2', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'temp3' => ['name' => 'Température serre 1 (canal 3)', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'temp4' => ['name' => 'Température sonde 4', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],

    // Sondes d'humidité additionnelles
    'humidity1' => ['name' => 'Humidité sonde 1', 'unit' => '%', 'format' => function($val) { return round($val); }],
    'humidity2' => ['name' => 'Humidité sonde 2', 'unit' => '%', 'format' => function($val) { return round($val); }],
    'humidity3' => ['name' => 'Humidité serre 1 (canal 3)', 'unit' => '%', 'format' => function($val) { return round($val); }],
    'humidity4' => ['name' => 'Humidité sonde 4', 'unit' => '%', 'format' => function($val) { return round($val); }],

    // Mesures intérieures
    'indoortemperature' => ['name' => 'Température intérieure', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'indoorhumidity' => ['name' => 'Humidité intérieure', 'unit' => '%', 'format' => function($val) { return round($val); }],

    // Autres mesures
    'brightness' => ['name' => 'Luminosité', 'unit' => 'lux', 'format' => function($val) { return round($val); }],
    'suntime' => ['name' => 'Ensoleillement', 'unit' => 'min', 'format' => function($val) { return round($val); }],
    'soilmoisture1' => ['name' => 'Humidité sol 1', 'unit' => '%', 'format' => function($val) { return round($val); }],
    'soilmoisture2' => ['name' => 'Humidité sol 2', 'unit' => '%', 'format' => function($val) { return round($val); }],
    'soilmoisture3' => ['name' => 'Humidité sol 3', 'unit' => '%', 'format' => function($val) { return round($val); }],
    'soilmoisture4' => ['name' => 'Humidité sol 4', 'unit' => '%', 'format' => function($val) { return round($val); }],
    'leafwetness1' => ['name' => 'Humidité feuille 1', 'unit' => '%', 'format' => function($val) { return round($val); }],
    'leafwetness2' => ['name' => 'Humidité feuille 2', 'unit' => '%', 'format' => function($val) { return round($val); }]
]; 