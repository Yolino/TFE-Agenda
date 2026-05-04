<?php

return [
    'accepted'             => 'Le champ :attribute doit être accepté.',
    'email'                => 'Le champ :attribute doit être une adresse email valide.',
    'min'                  => [
        'string' => 'Le champ :attribute doit contenir au minimum :min caractères.',
    ],
    'max'                  => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'required'             => 'Le champ :attribute est obligatoire.',
    'confirmed'            => 'La confirmation du champ :attribute ne correspond pas.',
    'unique'               => 'Cette valeur est déjà utilisée pour le champ :attribute.',
    'in'                   => 'La valeur sélectionnée pour :attribute est invalide.',
    'string'               => 'Le champ :attribute doit être une chaîne de caractères.',
    'boolean'              => 'Le champ :attribute doit être vrai ou faux.',
    'integer'              => 'Le champ :attribute doit être un entier.',
    'numeric'              => 'Le champ :attribute doit être un nombre.',
    'date'                 => 'Le champ :attribute doit être une date valide.',
    'file'                 => 'Le champ :attribute doit être un fichier.',
    'mimes'                => 'Le champ :attribute doit être un fichier de type : :values.',
    'exists'               => 'La valeur sélectionnée pour :attribute est invalide.',

    'attributes' => [
        'email'                 => 'adresse email',
        'password'              => 'mot de passe',
        'name'                  => 'nom',
        'firstname'             => 'prénom',
        'role'                  => 'rôle',
        'type'                  => 'type',
        'token'                 => 'jeton',
        'recipient'             => 'destinataire',
        'password_confirmation' => 'confirmation du mot de passe',
    ],
];
