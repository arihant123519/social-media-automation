<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Competitor Post Intelligence — tracked handles per client
    |--------------------------------------------------------------------------
    | Map each client's database id to 3-5 competitor Instagram handles (without
    | the leading @). The Monday scheduler (`competitors:weekly-brief`) reads this
    | list and generates a fresh "what's working this week" brief for each client.
    |
    | This config is FILE-BASED on purpose — competitor tracking config and the
    | generated briefs are intentionally kept OUT of the database.
    |
    | Example:
    |   'clients' => [
    |       1 => ['drbatra_official', 'kayaclinic', 'oliva.clinic'],
    |       2 => ['cult.fit', 'healthifyme'],
    |   ],
    */

    'clients' => [
        // client_id => ['handle1', 'handle2', 'handle3'],
    ],

];
