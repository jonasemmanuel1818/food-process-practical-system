<?php

/*
|--------------------------------------------------------------------------
| RATE LIMITER
|--------------------------------------------------------------------------
| General file-based rate limiter for the Food Process System.
|
| Two main uses:
|
| 1. Normal rate limiting
|    check_rate_limit()
|
|    Example:
|    5 requests every 60 seconds
|
| 2. Failed-attempt rate limiting
|    get_rate_limit_status()
|    record_rate_limit_attempt()
|    clear_rate_limit()
|
|    This allows login protection to count ONLY failed attempts.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| GET STORAGE DIRECTORY
|--------------------------------------------------------------------------
*/

function get_rate_limit_storage_directory(): string
{
    $storage_dir = __DIR__ . DIRECTORY_SEPARATOR . "rate_limit_storage";

    if (!is_dir($storage_dir)) {

        @mkdir(
            $storage_dir,
            0755,
            true
        );
    }

    return $storage_dir;
}


/*
|--------------------------------------------------------------------------
| GET RATE LIMIT FILE
|--------------------------------------------------------------------------
*/

function get_rate_limit_file(string $key): string
{
    $storage_dir = get_rate_limit_storage_directory();

    $file_name = hash(
        "sha256",
        $key
    ) . ".json";

    return $storage_dir
        . DIRECTORY_SEPARATOR
        . $file_name;
}


/*
|--------------------------------------------------------------------------
| READ ATTEMPTS
|--------------------------------------------------------------------------
*/

function read_rate_limit_attempts(string $key): array
{
    $file_path = get_rate_limit_file($key);

    if (!file_exists($file_path)) {
        return [];
    }

    $contents = @file_get_contents($file_path);

    if ($contents === false || $contents === '') {
        return [];
    }

    $decoded = json_decode(
        $contents,
        true
    );

    if (!is_array($decoded)) {
        return [];
    }

    $attempts = [];

    foreach ($decoded as $timestamp) {

        if (is_numeric($timestamp)) {

            $attempts[] = (int) $timestamp;
        }
    }

    return $attempts;
}


/*
|--------------------------------------------------------------------------
| REMOVE EXPIRED ATTEMPTS
|--------------------------------------------------------------------------
*/

function clean_rate_limit_attempts(
    array $attempts,
    int $window
): array {

    $now = time();

    $valid_attempts = [];

    foreach ($attempts as $timestamp) {

        if (
            ($now - $timestamp) < $window
        ) {

            $valid_attempts[] = $timestamp;
        }
    }

    return $valid_attempts;
}


/*
|--------------------------------------------------------------------------
| SAVE ATTEMPTS
|--------------------------------------------------------------------------
*/

function save_rate_limit_attempts(
    string $key,
    array $attempts
): bool {

    $file_path = get_rate_limit_file($key);

    $json = json_encode(
        array_values($attempts)
    );

    if ($json === false) {
        return false;
    }

    return @file_put_contents(
        $file_path,
        $json,
        LOCK_EX
    ) !== false;
}


/*
|--------------------------------------------------------------------------
| CHECK RATE LIMIT STATUS
|--------------------------------------------------------------------------
|
| IMPORTANT:
| This function DOES NOT record a new attempt.
|
| This is what allows login.php to check the limit first and then
| record an attempt only when the login actually fails.
|--------------------------------------------------------------------------
*/

function get_rate_limit_status(
    string $key,
    int $limit,
    int $window
): array {

    $attempts = read_rate_limit_attempts($key);

    $attempts = clean_rate_limit_attempts(
        $attempts,
        $window
    );


    /*
    |--------------------------------------------------------------------------
    | Save cleaned list
    |--------------------------------------------------------------------------
    */

    save_rate_limit_attempts(
        $key,
        $attempts
    );


    /*
    |--------------------------------------------------------------------------
    | LIMIT REACHED
    |--------------------------------------------------------------------------
    */

    if (count($attempts) >= $limit) {

        $oldest_attempt = min(
            $attempts
        );

        $retry_after = max(
            1,
            $window - (
                time() - $oldest_attempt
            )
        );

        return [
            "allowed" => false,
            "retry_after" => $retry_after,
            "remaining" => 0,
            "attempts" => count($attempts)
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | LIMIT NOT REACHED
    |--------------------------------------------------------------------------
    */

    return [
        "allowed" => true,
        "retry_after" => 0,
        "remaining" => max(
            0,
            $limit - count($attempts)
        ),
        "attempts" => count($attempts)
    ];
}


/*
|--------------------------------------------------------------------------
| RECORD ONE RATE-LIMIT ATTEMPT
|--------------------------------------------------------------------------
*/

function record_rate_limit_attempt(
    string $key,
    int $limit,
    int $window
): array {

    $attempts = read_rate_limit_attempts($key);

    $attempts = clean_rate_limit_attempts(
        $attempts,
        $window
    );


    /*
    |--------------------------------------------------------------------------
    | Add Current Attempt
    |--------------------------------------------------------------------------
    */

    $attempts[] = time();


    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    save_rate_limit_attempts(
        $key,
        $attempts
    );


    /*
    |--------------------------------------------------------------------------
    | Calculate Remaining
    |--------------------------------------------------------------------------
    */

    $count = count($attempts);

    $allowed = $count <= $limit;

    $retry_after = 0;

    if (!$allowed) {

        $oldest_attempt = min(
            $attempts
        );

        $retry_after = max(
            1,
            $window - (
                time() - $oldest_attempt
            )
        );
    }


    return [
        "allowed" => $allowed,
        "retry_after" => $retry_after,
        "remaining" => max(
            0,
            $limit - $count
        ),
        "attempts" => $count
    ];
}


/*
|--------------------------------------------------------------------------
| CLEAR RATE LIMIT
|--------------------------------------------------------------------------
|
| Used after a successful login.
|--------------------------------------------------------------------------
*/

function clear_rate_limit(string $key): bool
{
    $file_path = get_rate_limit_file($key);

    if (file_exists($file_path)) {

        return @unlink(
            $file_path
        );
    }

    return true;
}


/*
|--------------------------------------------------------------------------
| NORMAL RATE LIMIT
|--------------------------------------------------------------------------
|
| Used by Practical 2, 3 and 4.
|
| This function checks AND records the request.
|
|--------------------------------------------------------------------------
*/

function check_rate_limit(
    string $key,
    int $limit,
    int $window
): array {

    $status = get_rate_limit_status(
        $key,
        $limit,
        $window
    );


    /*
    |--------------------------------------------------------------------------
    | Already blocked
    |--------------------------------------------------------------------------
    */

    if (!$status['allowed']) {

        return $status;
    }


    /*
    |--------------------------------------------------------------------------
    | Record Request
    |--------------------------------------------------------------------------
    */

    return record_rate_limit_attempt(
        $key,
        $limit,
        $window
    );
}

?>