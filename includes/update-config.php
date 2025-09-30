<?php
/**
 * Configuration pour le système de mise à jour automatique depuis GitHub
 * @author Fabrice Simonet | Webglobal
 */

if ( ! defined('ABSPATH') ) exit;

return [
    // Informations GitHub
    'github' => [
        'username' => 'Weblogbal',
        'repository' => 'gestion-cookies',
        'branch' => 'main'
    ],
    
    // Configuration du plugin
    'plugin' => [
        'name' => 'Gestion Cookies',
        'slug' => 'gestion-cookies',
        'text_domain' => 'gestion-cookies',
        'author' => 'Fabrice Simonet | Webglobal',
        'author_uri' => 'https://webglobal.fr',
        'plugin_uri' => 'https://github.com/Weblogbal/gestion-cookies',
        'requires_wp' => '5.0',
        'tested_up_to' => '6.3',
        'requires_php' => '7.4'
    ],
    
    // Configuration des mises à jour
    'updates' => [
        'check_interval' => 12, // heures
        'enable_cache' => true,
        'show_update_notice' => true,
        'auto_check_updates' => true
    ],
    
    // URLs de base
    'urls' => [
        'version_file' => 'https://raw.githubusercontent.com/Weblogbal/gestion-cookies/main/version.json',
        'download_zip' => 'https://github.com/Weblogbal/gestion-cookies/archive/refs/heads/main.zip',
        'repository' => 'https://github.com/Weblogbal/gestion-cookies',
        'changelog' => 'https://github.com/Weblogbal/gestion-cookies/blob/main/CHANGELOG.md',
        'issues' => 'https://github.com/Weblogbal/gestion-cookies/issues'
    ]
];