<?php

/*
|--------------------------------------------------------------------------
| #14 — Monthly Calendar Template: recurring weekday content themes
|--------------------------------------------------------------------------
| Maps weekday (0=Sun … 6=Sat) → content theme. The team fills in the
| specific topic; the theme keeps each week's mix balanced and on-strategy.
| `default` applies to any industry; add an industry key to override.
*/

return [

    'default' => [
        1 => 'Awareness',      // Monday
        2 => 'Procedure',      // Tuesday
        3 => 'Testimonial',    // Wednesday
        4 => 'Educational',    // Thursday
        5 => 'Engagement',     // Friday
        6 => 'Behind the Scenes', // Saturday
        0 => 'Myth Buster',    // Sunday
    ],

    'dermatologist' => [
        1 => 'Skin Awareness',
        2 => 'Treatment Spotlight',
        3 => 'Patient Testimonial',
        4 => 'Skincare Tip',
        5 => 'Before & After',
        6 => 'Clinic Behind the Scenes',
        0 => 'Skincare Myth Buster',
    ],

    'ivf' => [
        1 => 'Fertility Awareness',
        2 => 'Procedure Explainer',
        3 => 'Success Story',
        4 => 'Educational Q&A',
        5 => 'Emotional Support',
        6 => 'Meet the Team',
        0 => 'Fertility Myth Buster',
    ],

];
