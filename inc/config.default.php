<?php
/**
 * This file is part of the PHPLucidFrame library.
 * This is a system-specific configuration file. All site general configuration are done here.
 *
 * @package     PHPLucidFrame\App
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

# $lc_env: The setting for running environment: `development` or `production` (read from .lcenv)
$lc_env = _p();
# $lc_timeZone: Default Time Zone (See https://www.php.net/manual/en/timezones.php)
$lc_timeZone = 'Asia/Yangon';
# $lc_memoryLimit: The maximum amount of memory in bytes that a script is allowed to allocate.
$lc_memoryLimit = '128M';
# $lc_maxExecTime: The maximum time in seconds a script is allowed to run before it is terminated by the parser.
$lc_maxExecTime = 300;
# $lc_debugLevel: The debug level. If $lc_env = 'production', this is not considered.
# `0` - error reporting is disabled
# `1` - show fatal errors, parse errors, but no PHP startup errors
# `2` - show fatal errors, parse errors, warnings and notices
# `3` - show all errors and warnings, except of level E_STRICT prior to PHP 5.4.0.
# `int level` - set your own error reporting level. The parameter is either an integer representing a bit field, or named constants
#  @see http://php.net/manual/en/errorfunc.configuration.php#ini.error-reporting
$lc_debugLevel = _p('debugLevel');

# $lc_siteName: Site Name
$lc_siteName = 'PHPLucidFrame';
# $lc_baseURL: No trailing slash (only if it is located in a sub-directory)
# Leave blank if it is located in the document root
# Update this in `/inc/parameter/*.php`
$lc_baseURL = _p('baseURL');
# $lc_siteDomain: Site Domain Name
# Update this in `/inc/parameter/*.php`
$lc_siteDomain = _p('siteDomain');
# $lc_ssl: SSL enabled or not
$lc_ssl = _p('ssl');

/**
 * Session configuration.
 *
 * Contains an array of settings to use for session configuration.
 * Any settings declared here will override the settings of the default config.
 *
 * ## Options
 *
 * - `table`: The table name without prefix that stores the session data. It is only applicable to database session
 * - `name`: The name of the session to use. Defaults to 'LCSESSID'
 * - `gc_maxlifetime`: The number of minutes after which data will be seen as 'garbage' and potentially cleaned up. Defaults to 240 minutes.
 * - `cookie_lifetime`: The number of minutes you want session cookies live for. The value 0 means "until the browser is closed.". Defaults to 180 minutes.
 * - `cookie_path`: The path to set in the session cookie. Defaults to '/'
 * - `save_path`: The path of the directory used to save session data. It is only applicable to default file handler session management. Defaults to ''.
 * - @see
 *    more options at http://php.net/manual/en/session.configuration.php
 *    you can set any valid option without the prefix `session.`
 *
 * ## Minimum table schema requirement for database session
 *
 *    CREATE TABLE `lc_sessions` (
 *      `sid` varchar(64) NOT NULL DEFAULT '',
 *      `host` varchar(128) NOT NULL DEFAULT '',
 *      `timestamp` int(11) unsigned DEFAULT NULL,
 *      `session` longblob NOT NULL DEFAULT '',
 *      `useragent` varchar(255) NOT NULL DEFAULT '',
 *      PRIMARY KEY (`sid`)
 *    );
 *
 * The hook `session_beforeStart()` is available to define in /app/helpers/session_helper.php
 * so that you could do something before session starts.
 */
$lc_session = array(
    'type' => 'default', // default or database
    'options' => array(
        'name'            => 'LC_SESSID',
        // 'table'           => 'lc_sessions', # when type is database
        'gc_maxlifetime'  => 24, # in minutes
        'cookie_lifetime' => 180, # in minutes
        'cookie_samesite' => 'Lax', # Lax, Strict, None (available only in PHP 7.3+)
    )
);

# $lc_databases: The array specifies the database connection
# Update this in `/inc/parameter/*.php`
$lc_databases = _p('db');
# $lc_defaultDbSource: The default database connection
$lc_defaultDbSource = 'default';

# $lc_sites: consider sub-directories as additional site roots and namespaces
/**
 * ### Syntax
 *    array(
 *      'virtual_folder_name (namespace)'  => 'path/to/physical_folder_name_directly_under_app_directory'
 *    )
 * For example, if you have the configuration `'admin' => 'admin'` here, you let LucidFrame know to include the files
 * from those directories below without specifying the directory name explicitly in every include:
 *   /app/admin/css
 *   /app/admin/inc
 *   /app/admin/helpers
 *   /app/admin/js
 * you could also set 'lc-admin' => 'admin', then you can access http://localhost/phplucidframe/lc-admin
 * @see https://github.com/phplucidframe/phplucidframe/wiki/Configuration-for-The-Sample-Administration-Module
 */
$lc_sites = array(
    /* 'virtual_folder_name (namespace)'  => 'path/to/physical_folder_name_directly_under_app_directory' */
    'admin' => 'admin'
);
# $lc_shareNamespaces: specify the shared session name group for each sub-site
$lc_sharedNamespaces = array(
    /* 'virtual_folder_name (namespace)'  => 'shared namespace' */
    // 'admin' => 'default', // if you enable this, the admin site will use 'default' for session name group, otherwise 'admin' as defined above
);
# $lc_translationEnabled - Enable/Disable language translation
$lc_translationEnabled = true;
# $lc_languages: Site languages (leave this as an empty array for single-language site)
/**
 * ### Syntax
 *    array(
 *      'lang_code' => 'Language Name'
 *    )
 * ### Example
 *    array(
 *      'en' => 'English',
 *      'my' => 'Myanmar',
 *      'zh-CN' => 'Chinese'
 *    )
 * Make this an empty array for single-language site
 */
$lc_languages = array(
    /* 'lang_code' => 'Language Name' */
    'en' => 'English',
    'my' => 'Myanmar'
);
# $lc_defaultLang: Default site language (leave blank for single-language site)
# One of the key of $lc_languages
$lc_defaultLang = 'en';
# $lc_lang: Current selected language
$lc_lang = $lc_defaultLang;
# $lc_cleanURL: Enable/Disable clean URL
$lc_cleanURL = true;
# $lc_cipher: OpenSSL cipher method to use for encryption
$lc_cipher = 'AES-256-CBC';
# $lc_securitySecret: the key with which the data will be encrypted
# default hash string is located at ./inc/.secret
# It is strongly recommended to change this and use the hash functions to create a key from a string.
# For enhanced security, you may move the file or create a new file outside your document root;
# and set full path to `__secret()`, for example,
# $lc_securitySecret = __secret('/home/example/.secret');
$lc_securitySecret = __secret();
# $lc_formTokenName - CSRF token name for Cross Site Request Forgery protection.
$lc_formTokenName = 'csrf';
# $lc_csrfHeaderTokenName - Header name for Cross Site Request Forgery protection.
$lc_csrfHeaderTokenName = 'X-CSRF-TOKEN';
# $lc_minifyHTML: Compacting HTML code, including any inline JavaScript and CSS contained in it,
# can save many bytes of data and speed up downloading, parsing, and execution time.
# It is forced to `false` when $lc_env = 'development'
$lc_minifyHTML = true;
# $lc_imageFilterSet: Default image filter setting that applies to image upload
$lc_imageFilterSet = array(
    'maxDimension' => '800x600', // or null for client original image size to keep, but not recommended
    'resizeMode'   => FILE_RESIZE_BOTH,
    'jpgQuality'   => 75
);
# $lc_layoutMode: Enable layout mode or not
$lc_layoutMode = true;
# $lc_layoutMode: Default layout file name
$lc_layoutName = 'layout';
# The site meta description for search engines
$lc_metaDescription = 'PHPLucidFrame (a.k.a LucidFrame) is a PHP application development framework that is simple, easy, lightweight and yet powerful.';
# The site contact email address - This address will be used as "To" for all incoming mails
# Update this in `/inc/parameter/*.php`
$lc_siteReceiverEmail = _p('siteReceiverEmail');
# The site sender email address - This address will be used as "From" for all outgoing mails
# Update this in `/inc/parameter/*.php`
$lc_siteSenderEmail = _p('siteSenderEmail');
# $lc_titleSeparator - Page title separator
$lc_titleSeparator = '-';
# $lc_breadcrumbSeparator - Breadcrumb separator
$lc_breadcrumbSeparator = '&raquo;';
# $lc_dateFormat: Date format
$lc_dateFormat = 'd/m/Y';
# $lc_dateTimeFormat: Date Time format
$lc_dateTimeFormat = 'd/m/Y h:ia';
# $lc_pageNumLimit: number of page numbers to be shown in pager
$lc_pageNumLimit = 10;
# $lc_itemsPerPage: number of items per page in pager
$lc_itemsPerPage = 15;
# $lc_assetVersion: Versioning for css/js file includes
$lc_assetVersion = 1;
/**
 * Auth Module Configuration
 */
# $lc_auth: configuration for the user authentication
$lc_auth = array(
    'table' => '', // table name, for example, user
    'fields' => array(
        'id'    => '',  // PK field name, for example, user_id
        'role'  => ''   // User role field name, for example, user_role
    ),
    'permissions'  => array(
        // permissions allowed
        // role name => array of permission names
        // For example,
        // 'admin' => array('post-list', 'post-add', 'post-edit', 'post-delete'),
        // 'editor' => array('post-list', 'post-add', 'post-edit') // editor is not allowed for post deletion
        // If you store permissions in your db, implement auth_permissions($role) in /app/helpers/auth_helper.php
        // to return the permission list from your db
    ),
);
