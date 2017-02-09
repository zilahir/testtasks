<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'ergomania');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'fSTH@Dn|Jy/bY!Hki?8[iCLor-6D=` cS7{PNbFg6UwL/*=!N`c%wrD</ @]!=d:');
define('SECURE_AUTH_KEY',  't)YqE^RQ EZb@_W7Pivxf<v}cWN#Yp%T&d5ER#QEP}4v?$d|2Kpe2Uko5H%B<o[f');
define('LOGGED_IN_KEY',    'Ls !~&gv;Z8zCP=|Pd3$uTkLZ5 _PO%6{d*i1xEE<GqG06Vi&0$f`<K_hra|}K/k');
define('NONCE_KEY',        '~CZ)!Q>7Z *w{vHho).Q&yn_eR+rJp: c!`:`b`k[+I4h`u3.C29ceZNC`vj{!p=');
define('AUTH_SALT',        '^{{:pMR*/6nuO #MhlcyHNwdQTNuE7y&@,/=^`MOm$llGc}oi:q^A;y3O2#.O`+j');
define('SECURE_AUTH_SALT', '(Jsep-gnIQPUdHOp80+}zPC^`>hX4*4LZSRFM8?yu`Jeu 9k0bd<6Uih$<vQs1U<');
define('LOGGED_IN_SALT',   '%,UkLM71(>83lW~?Z?DrcR/S=>2XlRSQ/g7&whNn;y`I|b,}S-Y9#h:&(=T%;+_U');
define('NONCE_SALT',       'j^&Ts%w@#rL&,m:H8v{Fk>vMzC4fk0]E`}L7(+#)yOG?X(Pdt@,*j%4VTW_z D8P');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
