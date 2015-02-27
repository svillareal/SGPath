<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'alexvill_wo2569');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

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
define('AUTH_KEY', 'tqX&>Yypxy>qw!!rW{PUjkdNPBZtE!tMVr=YUU(RuV^nTsV(]mg&TTlC)I&!ORX$BKE*d[nsoTxyqrqP^F<Qc/KjPCTT?LZbalLI;lcL$h<v;RUDee)fVK&eCc$PIu%X');
define('SECURE_AUTH_KEY', 'p]-Ddz%biQKsdEnAEZj?|b><=g+Re&|vM}crzD@MZn]ZRyD?rK!UdUK@)W]!Xb;ga>k-XYaoF|&EQLV[=?AOXOF+KPCrd])lRIR$?fZmfxx|jAdjlw;nBok;g(Nn?w]>');
define('LOGGED_IN_KEY', 'Q&n+wTE<^{BJuH(@/>Xemv=L_KfMbXjx;!nebBqFh|xcnjrt;z}m;]h^K^*b{$ehHJvw_Tsthv*Q&i@L;Lh%h!)*r;y)hFK!VD!?U;[$TQoNH[)D;qAHpPK{KZ+gmCLS');
define('NONCE_KEY', '+Iqixe=ekX-fUK>)UK+g&ntO!OgTd{{YY]YKY{_^;^QI*z>)|Kb[(IJ!cTs>zcNZ)O@R>KgTb@VeUar|zJ<aunMQEx%cP^LOji{^f_+MLj}jumnpZ(oO*X&nnE?]&LRm');
define('AUTH_SALT', '-vxc;K+i&i^MQM|xvaJ>*oFM-IiUavkpM={Rq<rxUW?hthf$eX&{mD=nCJsRJ>p[NW]x=Tu-@cFo*kFRNJOM//Xmq>)@{*EJP}I([bcjSnGFMgP*;ZY/*+j%qJGESRMv');
define('SECURE_AUTH_SALT', '{Ba&n&lH{PL&P+M}HZ({WE[&UB^)F!bU=kagNlGBa}X{hP@CN$ubH?+qc&]WS@]jP>XbFeeMr?(r/;(^Lykde+={lbeZj}S?Hwm>z(aQy%w+mR|$A*-JNp%%pUwAYgVV');
define('LOGGED_IN_SALT', '+pf%-ZF&%EJ(DDfXMA;WuloQvCcOhMk&tyYiJO_q(BTfTnExU@Psdy(lD_=X&qUMpcEBbQ*&@xzxVGb/Pc/g<aVrDX}[+i&EBAFhko;fTC$<qaCLTdK+OicUb+_d[FrC');
define('NONCE_SALT', 'x=CLKQLZ<l[rrz>Y|@{i+X[|$yNQ_u_ZXkG<;CaYryCm&VLtmWRESht|_g^aCQxqx/x/SQRz%=BC{q[KJ_m/^{c{NGFl|a{mB&=RLuh{iN+fIO^k>(f>JBoS*lFd{YUm');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_jykk_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

/**
 * Include tweaks requested by hosting providers.  You can safely
 * remove either the file or comment out the lines below to get
 * to a vanilla state.
 */
if (file_exists(ABSPATH . 'hosting_provider_filters.php')) {
	include('hosting_provider_filters.php');
}
