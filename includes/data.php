<?php
/* Ruta Nómada — mock content data (translated from data.jsx) */

$DESTINATIONS = [
  ['name' => 'Kyoto',              'country' => 'Japón',      'category' => 'Cultura',        'tint' => 'cultura',   'icon' => 'cultura',
   'desc' => 'Sumérgete en la historia antigua y templos serenos.',                            'price' => '$24,800 MXN', 'rating' => '4.9', 'fav' => false],
  ['name' => 'Santorini',          'country' => 'Grecia',     'category' => 'Romance',        'tint' => 'romance',   'icon' => 'romance',
   'desc' => 'Vistas espectaculares y puestas de sol inolvidables.',                           'price' => '$31,200 MXN', 'rating' => '4.8', 'fav' => true],
  ['name' => 'Patagonia',          'country' => 'Argentina',  'category' => 'Aventura',       'tint' => 'aventura',  'icon' => 'aventura',
   'desc' => 'Naturaleza salvaje en el fin del mundo.',                                        'price' => '$28,900 MXN', 'rating' => '4.9', 'fav' => false],
  ['name' => 'Marruecos',          'country' => 'Marrakech',  'category' => 'Descubrimiento', 'tint' => 'desierto',  'icon' => 'desierto',
   'desc' => 'Misticismo y colores vibrantes en el desierto.',                                 'price' => '$19,500 MXN', 'rating' => '4.7', 'fav' => false],
  ['name' => 'Cancún',             'country' => 'México',     'category' => 'Playa',          'tint' => 'agua',      'icon' => 'beach',
   'desc' => 'Playas paradisíacas, cultura maya y vida nocturna.',                             'price' => '$8,500 MXN',  'rating' => '4.8', 'fav' => false],
  ['name' => 'Costa Amalfitana',   'country' => 'Italia',     'category' => 'Romance',        'tint' => 'romance',   'icon' => 'romance',
   'desc' => 'Pueblos colgados del mar y limoncello al atardecer.',                            'price' => '$33,400 MXN', 'rating' => '4.9', 'fav' => false],
  ['name' => 'Bosques de Bavaria', 'country' => 'Alemania',   'category' => 'Aventura',       'tint' => 'bosque',    'icon' => 'bosque',
   'desc' => 'Senderos entre niebla, castillos y aire de pino.',                               'price' => '$26,100 MXN', 'rating' => '4.6', 'fav' => false],
  ['name' => 'Lisboa',             'country' => 'Portugal',   'category' => 'Cultura',        'tint' => 'ciudad',    'icon' => 'ciudad',
   'desc' => 'Tranvías, azulejos y miradores sobre el Tajo.',                                  'price' => '$22,700 MXN', 'rating' => '4.8', 'fav' => false],
];

$CATEGORIES = [
  ['id' => 'todos',          'label' => 'Todos',          'icon' => 'explore'],
  ['id' => 'Cultura',        'label' => 'Cultura',        'icon' => 'temple_buddhist'],
  ['id' => 'Romance',        'label' => 'Romance',        'icon' => 'favorite'],
  ['id' => 'Aventura',       'label' => 'Aventura',       'icon' => 'hiking'],
  ['id' => 'Descubrimiento', 'label' => 'Descubrimiento', 'icon' => 'travel_explore'],
  ['id' => 'Playa',          'label' => 'Playa',          'icon' => 'beach_access'],
];

$STORIES = [
  ['when' => 'Hace 2 días',     'title' => 'Fin de semana en París',           'by' => 'Ana López',  'tint' => 'ciudad',  'icon' => 'ciudad'],
  ['when' => 'Hace 5 días',     'title' => 'Ruta Gastronómica: Asia',          'by' => 'Carlos M.',  'tint' => 'cultura', 'icon' => 'food'],
  ['when' => 'Hace 1 semana',   'title' => 'Desconexión en el Bosque',         'by' => 'Elena G.',   'tint' => 'bosque',  'icon' => 'bosque'],
  ['when' => 'Hace 2 semanas',  'title' => 'Verano en la Costa Amalfitana',    'by' => 'David R.',   'tint' => 'romance', 'icon' => 'romance'],
];

$MY_TRIPS = [
  ['name' => 'Cancún, México',         'dates' => '12 – 19 Jul 2026', 'status' => 'Confirmado', 'tint' => 'agua',     'icon' => 'beach',    'budget' => '$8,500',  'people' => 2],
  ['name' => 'Kyoto, Japón',           'dates' => '03 – 14 Oct 2026', 'status' => 'Planeando',  'tint' => 'cultura',  'icon' => 'cultura',  'budget' => '$24,800', 'people' => 1],
  ['name' => 'Patagonia, Argentina',    'dates' => 'Por definir',      'status' => 'Borrador',   'tint' => 'aventura', 'icon' => 'aventura', 'budget' => '—',       'people' => 4],
];
