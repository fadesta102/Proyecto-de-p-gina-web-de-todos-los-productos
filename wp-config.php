<?php

// BEGIN iThemes Security - No modifiques ni borres esta línea
// iThemes Security Config Details: 2
define( 'DISALLOW_FILE_EDIT', true ); // Desactivar editor de archivos - Seguridad > Ajustes > Ajustes WordPress > Editor de archivos
// END iThemes Security - No modifiques ni borres esta línea

/**
 * Configuración básica de WordPress.
 *
 * Este archivo contiene las siguientes configuraciones: ajustes de MySQL, prefijo de tablas,
 * claves secretas, idioma de WordPress y ABSPATH. Para obtener más información,
 * visita la página del Codex{@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} . Los ajustes de MySQL te los proporcionará tu proveedor de alojamiento web.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** Ajustes de MySQL. Solicita estos datos a tu proveedor de alojamiento web. ** //
/** El nombre de tu base de datos de WordPress */
define( 'DB_NAME', 'tienda' );

/** Tu nombre de usuario de MySQL */
define( 'DB_USER', 'root' );

/** Tu contraseña de MySQL */
define( 'DB_PASSWORD', '' );

/** Host de MySQL (es muy probable que no necesites cambiarlo) */
define( 'DB_HOST', 'localhost' );

/** Codificación de caracteres para la base de datos. */
define( 'DB_CHARSET', 'utf8mb4' );

/** Cotejamiento de la base de datos. No lo modifiques si tienes dudas. */
define('DB_COLLATE', '');

/**#@+
 * Claves únicas de autentificación.
 *
 * Define cada clave secreta con una frase aleatoria distinta.
 * Puedes generarlas usando el {@link https://api.wordpress.org/secret-key/1.1/salt/ servicio de claves secretas de WordPress}
 * Puedes cambiar las claves en cualquier momento para invalidar todas las cookies existentes. Esto forzará a todos los usuarios a volver a hacer login.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY', 'awm)Y}.V}0%eV9_U^,FNa-66oU>gbRO~2$5n.t7EAU}9 )f%Wq,lRDl!_ frH4KR' );
define( 'SECURE_AUTH_KEY', '?eJ7@/od**V^dm!i;C2Q5M=&#ad89e1Xr@q|&q>%$nOS_:i46FSAYO#0#W6DTf6n' );
define( 'LOGGED_IN_KEY', 'p#d2j9JGrl7Mm#np[v$OAWa._^,g3*$<v9]dDn5PwLgxv;yZX!tC5l*e!>9tL,>P' );
define( 'NONCE_KEY', 'V0x zSr;%JG(PDd@m`MS1Z}@o?o)Cvte>FTmHMGbk3_}voZ%n^(,~Z.W*E3e;_$6' );
define( 'AUTH_SALT', 'w);uzZk!9BBW.WB~~5)_ ^du&FZ<<!2?&MT3z+m?o/#1<i.Nc101B)rff`pPOqLJ' );
define( 'SECURE_AUTH_SALT', 'I8+Qv!TPp|auD1w%$isE`gX*g(H2VmX{1A GaYG=vhMZ)&EjBRe$^Pc^0IL[PgKQ' );
define( 'LOGGED_IN_SALT', 'E3e/is[+_uuhJ:3-CyI>uj>c@5UY; =I#lu-orKs$Ck^txX9_NelCJh;neG<TS;L' );
define( 'NONCE_SALT', '=hcc}@ZCo [|!q/T)(CT6cuQ*7j@5EF|4L>)tt~fMiY#*Q<7xRjqPo]4AzI R8X]' );

/**#@-*/

/**
 * Prefijo de la base de datos de WordPress.
 *
 * Cambia el prefijo si deseas instalar multiples blogs en una sola base de datos.
 * Emplea solo números, letras y guión bajo.
 */
$table_prefix = 'wp_';


/**
 * Para desarrolladores: modo debug de WordPress.
 *
 * Cambia esto a true para activar la muestra de avisos durante el desarrollo.
 * Se recomienda encarecidamente a los desarrolladores de temas y plugins que usen WP_DEBUG
 * en sus entornos de desarrollo.
 */
define('WP_DEBUG', false);

/* ¡Eso es todo, deja de editar! Feliz blogging */

/** WordPress absolute path to the Wordpress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

