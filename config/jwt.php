<?php

/*
 * This file is part of jwt-auth.
 *
 * (c) Sean Tymon <tymon148@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Secret
    |--------------------------------------------------------------------------
    |
    | Don't forget to set this in your .env file, as it will be used to sign
    | your tokens. A helper command is provided for this: `php artisan jwt:secret`
    |
    */

    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Keys
    |--------------------------------------------------------------------------
    |
    | Here you can specify the keys that will be used to sign your tokens.
    | If the secret is null, then the keys will be used instead.
    |
    | You should generate a public and private key pair.
    | A helper command is provided for this: `php artisan jwt:secret --keys`
    |
    */

    'keys' => [
        'public' => env('JWT_PUBLIC_KEY'),
        'private' => env('JWT_PRIVATE_KEY'),
        'passphrase' => env('JWT_PASSPHRASE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT time to live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token will be valid for.
    |
    */

    'ttl' => 60,

    /*
    |--------------------------------------------------------------------------
    | Refresh time to live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token can be refreshed
    | within.
    |
    | You can additionally set this to 0 to allow tokens to be refreshed
    | indefinitely.
    |
    */

    'refresh_ttl' => 20160,

    /*
    |--------------------------------------------------------------------------
    | JWT hashing algorithm
    |--------------------------------------------------------------------------
    |
    | Specify the hashing algorithm that will be used to sign the token.
    |
    | See here: https://github.com/namshi/jose/tree/master/src/Namshi/JOSE/Signer
    | for a list of supported algorithms.
    |
    */

    'algo' => 'HS256',

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    |
    | Specify the claims that must be present in the token.
    |
    */

    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent Claims
    |--------------------------------------------------------------------------
    |
    | Specify the claims that are persisted when refreshing a token.
    | `sub` and `jti` are persisted by default.
    |
    | Note: custom claims are not persisted when using the `custom_claims`
    | setting below. You must add them to this array.
    |
    */

    'persistent_claims' => [
        // 'foo',
        // 'bar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock Subject
    |--------------------------------------------------------------------------
    |
    | This will determine whether the subject claim is "locked" when refreshing a
    | token. If set to true, the subject claim of the new token will be the
    | same as the subject claim on the old token.
    |
    | As a result, you are unable to use a refresh token to get a new token for
    | a different user.
    |
    */

    'lock_subject' => true,

    /*
    |--------------------------------------------------------------------------
    | Leeway
    |--------------------------------------------------------------------------
    |
    | This will allow you to specify a leeway time (in seconds) to account for
    | any clock skew between servers.
    |
    */

    'leeway' => 0,

    /*
    |--------------------------------------------------------------------------
    | Blacklist
    |--------------------------------------------------------------------------
    |
    | In order to invalidate tokens, you must have the blacklist enabled.
    | If you do not want or need this functionality, then you can disable it.
    |
    */

    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Grace Period
    |--------------------------------------------------------------------------
    |
    | When multiple concurrent requests are made with the same JWT,
    | one of the requests may invalidate the token, leading to
    | the other requests failing.
    |
    | This setting allows you to set a grace period (in seconds)
    | during which the token can still be used, even after
    | it has been added to the blacklist.
    |
    | A sensible default is 0 seconds.
    |
    */

    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    /*
    |--------------------------------------------------------------------------
    | Cookies
    |--------------------------------------------------------------------------
    |
    | You can enable the use of cookies to transport the token.
    |
    */

    'use_cookies' => false,

    /*
    |--------------------------------------------------------------------------
    | Show blacklisted token error
    |--------------------------------------------------------------------------
    |
    | This will determine whether a `TokenBlacklistedException` is thrown
    | when a token is used that has been blacklisted.
    |
    */

    'show_blacklisted_token_error' => false,

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Specify the providers that are used to power the JWT authentication.
    |
    */

    'providers' => [

        /*
        |--------------------------------------------------------------------------
        | JWT
        |--------------------------------------------------------------------------
        |
        | The JWT provider is responsible for encoding and decoding the tokens.
        |
        */

        'jwt' => Tymon\JWTAuth\Providers\JWT\Lcobucci::class,

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        |
        | The Authentication provider is responsible for authenticating users.
        |
        */

        'auth' => Tymon\JWTAuth\Providers\Auth\Illuminate::class,

        /*
        |--------------------------------------------------------------------------
        | Storage
        |--------------------------------------------------------------------------
        |
        | The Storage provider is responsible for storing the tokens in the
        | blacklist.
        |
        */

        'storage' => Tymon\JWTAuth\Providers\Storage\Illuminate::class,

    ],

];
