<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Str;

class ContactService
{
    public static function findOrCreate(string $email, ?string $display = null): Contact
    {
        $email = Str::lower(trim($email));

        if ($contact = Contact::whereEmail($email)->first()) {
            return $contact;
        }

        return Contact::create([
            'email' => $email,
            'display_name' => $display,
        ]);
    }
}
