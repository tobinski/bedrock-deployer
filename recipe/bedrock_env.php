<?php

/**
 * Manages the Bedrock .env file on the server. If not previous
 * release is available, the task asks for credentials and options,
 * generates salts and stores them in a new .env file in the current
 * release.
 *
 * If a previous release is available, the .env file is copied from
 * that release to the current release.
 */

namespace Deployer;

/*
 * Tries to copy .env file from previous release to current release.
 * If not available, the .env file is created while prompting the
 * user for credentials.
 */
desc( 'Makes sure, .env file for Bedrock is available' );
task( 'bedrock:env', function () {

    // Try to copy .env file from previous release to current release
    if ( has( 'previous_release' ) ) {
        if ( test( "[ -f {{previous_release}}/.env ]" ) ) {
            run( "cp {{previous_release}}/.env {{release_path}}" );
            return;
        }
    }

    // If previous .env file is not available, create one

    /**
     * Generates a random token with a length of 64 chars.
     *
     * Bases on wp_generate_password() function.
     *
     * @return string
     */
    function generate_salt() {
        $chars              = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~+=,.;:/?|';
        $char_option_length = strlen( $chars ) - 1;

        $password = '';
        for ( $i = 0; $i < 64; $i ++ ) {
            $password .= substr( $chars, random_int( 0, $char_option_length ), 1 );
        }

        return $password;
    }

    // Keys that require a salt token
    $salt_keys = [
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT',
    ];

    writeln( '<comment>Generating .env file</comment>' );

    // Ask for credentials
    $db_name = ask( get( 'stage' ) . ' server WordPress DB name: ' );
    $db_user = ask( get( 'stage' ) . ' server WordPress DB user: ' );
    $db_pass = ask( get( 'stage' ) . ' server WordPress DB password: ' );
    $db_host = ask( get( 'stage' ) . ' server WordPress DB host: ' );
    $wp_env  = ask( get( 'stage' ) . ' server WordPress DB ENV (one of "development", "staging" or "production"): ' );
    $wp_home = ask( get( 'stage' ) . ' server WordPress DB home url (ie http://domain.com): ' );


    ob_start();

    echo <<<EOL
DB_NAME='{$db_name}'
DB_USER='{$db_user}'
DB_PASSWORD='{$db_pass}'
DB_HOST='{$db_host}'
WP_ENV='{$wp_env}'
WP_HOME='{$wp_home}'
WP_SITEURL=\${WP_HOME}/wp

EOL;

    foreach ( $salt_keys as $key ) {
        echo $key . "='" . generate_salt() . "'" . PHP_EOL;
    }

    $content = ob_get_clean();

    run( 'echo "' . $content . '" > {{release_path}}/.env' );
} );