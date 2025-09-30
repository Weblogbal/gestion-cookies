<?php
/**
 * Système de mise à jour automatique pour extensions WordPress depuis GitHub
 * @author Fabrice Simonet | Webglobal
 * @version 1.0.1
 */

if ( ! defined('ABSPATH') ) exit;

class Webglobal_Update_Checker {
    
    private $github_username;
    private $github_repository;
    private $plugin_file;
    private $plugin_slug;
    private $plugin_version;
    private $version_json_url;
    private $zip_download_url;
    private $cache_key;
    private $cache_allowed;
    
    /**
     * Constructeur
     * @param string $plugin_file Chemin complet vers le fichier principal du plugin
     * @param string $github_username Nom d'utilisateur GitHub
     * @param string $github_repository Nom du repository GitHub
     */
    public function __construct($plugin_file, $github_username, $github_repository) {
        $this->plugin_file = $plugin_file;
        $this->github_username = $github_username;
        $this->github_repository = $github_repository;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->plugin_version = $this->get_plugin_version();
        $this->version_json_url = "https://raw.githubusercontent.com/{$github_username}/{$github_repository}/main/version.json";
        $this->zip_download_url = "https://github.com/{$github_username}/{$github_repository}/archive/refs/heads/main.zip";
        $this->cache_key = md5($this->plugin_slug . '_update_checker');
        $this->cache_allowed = true;
        
        $this->init_hooks();
    }
    
    /**
     * Initialise les hooks WordPress
     */
    private function init_hooks() {
        add_filter('plugins_api', [$this, 'plugin_popup'], 10, 3);
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_updates']);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
        add_action('upgrader_process_complete', [$this, 'purge_cache'], 10, 2);
    }
    
    /**
     * Récupère la version actuelle du plugin
     */
    private function get_plugin_version() {
        $plugin_data = get_file_data($this->plugin_file, ['Version' => 'Version'], 'plugin');
        return $plugin_data['Version'];
    }
    
    /**
     * Vérifie s'il y a des mises à jour disponibles
     */
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $remote_version = $this->get_remote_version();
        
        if ($remote_version && version_compare($this->plugin_version, $remote_version->version, '<')) {
            $plugin_folder = dirname($this->plugin_slug);
            if ($plugin_folder === '.') {
                $plugin_folder = pathinfo($this->plugin_slug, PATHINFO_FILENAME);
            }
            
            $transient->response[$this->plugin_slug] = (object) [
                'slug' => $plugin_folder,
                'plugin' => $this->plugin_slug,
                'new_version' => $remote_version->version,
                'url' => $remote_version->details_url,
                'package' => $remote_version->download_url,
                'tested' => $remote_version->tested,
                'requires_php' => $remote_version->requires_php,
                'compatibility' => new stdClass()
            ];
        }
        
        return $transient;
    }
    
    /**
     * Récupère les informations de version depuis GitHub
     */
    private function get_remote_version() {
        $cached_version = $this->cache_allowed ? get_transient($this->cache_key) : false;
        
        if ($cached_version !== false) {
            return $cached_version;
        }
        
        $remote_get = wp_remote_get($this->version_json_url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($remote_get) || wp_remote_retrieve_response_code($remote_get) !== 200) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($remote_get);
        $version_data = json_decode($body);
        
        if (json_last_error() !== JSON_ERROR_NONE || !$version_data) {
            return false;
        }
        
        // Cache pendant 12 heures
        if ($this->cache_allowed) {
            set_transient($this->cache_key, $version_data, 12 * 3600); // 12 heures en secondes
        }
        
        return $version_data;
    }
    
    /**
     * Gère l'affichage des informations du plugin dans la popup
     */
    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        $plugin_folder = dirname($this->plugin_slug);
        if ($plugin_folder === '.') {
            $plugin_folder = pathinfo($this->plugin_slug, PATHINFO_FILENAME);
        }
        
        if (!isset($args->slug) || $args->slug !== $plugin_folder) {
            return $result;
        }
        
        $remote_version = $this->get_remote_version();
        
        if (!$remote_version) {
            return $result;
        }
        
        return (object) [
            'name' => $remote_version->name,
            'slug' => $remote_version->slug,
            'version' => $remote_version->version,
            'author' => $remote_version->author,
            'author_profile' => $remote_version->author_profile ?? '',
            'requires' => $remote_version->requires,
            'tested' => $remote_version->tested,
            'requires_php' => $remote_version->requires_php,
            'download_link' => $remote_version->download_url,
            'trunk' => $remote_version->download_url,
            'last_updated' => $remote_version->last_updated,
            'sections' => (array) $remote_version->sections,
            'banners' => (array) ($remote_version->banners ?? []),
            'icons' => (array) ($remote_version->icons ?? []),
            'rating' => 0,
            'ratings' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
            'num_ratings' => 0,
            'support_threads' => 0,
            'support_threads_resolved' => 0,
            'active_installs' => 0,
            'downloaded' => 0,
            'homepage' => $remote_version->details_url,
            'tags' => [],
            'donate_link' => ''
        ];
    }
    
    /**
     * Actions après l'installation/mise à jour (simplifié pour structure plate)
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        $install_directory = plugin_dir_path($this->plugin_file);
        
        // Avec la nouvelle structure plate, on déplace directement
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;
        
        if ($this->cache_allowed) {
            wp_clean_plugins_cache();
        }
        
        return $result;
    }
    
    /**
     * Vide le cache après une mise à jour
     */
    public function purge_cache($upgrader, $options) {
        if ($this->cache_allowed && 
            $options['action'] === 'update' && 
            $options['type'] === 'plugin' && 
            !empty($options['plugins'])) {
            
            foreach ($options['plugins'] as $plugin) {
                if ($plugin === $this->plugin_slug) {
                    delete_transient($this->cache_key);
                }
            }
        }
    }
    
    /**
     * Désactive temporairement le cache (utile pour les tests)
     */
    public function disable_cache() {
        $this->cache_allowed = false;
        delete_transient($this->cache_key);
    }
    
    /**
     * Force la vérification d'une nouvelle version
     */
    public function force_check() {
        delete_transient($this->cache_key);
        return $this->get_remote_version();
    }
    
    /**
     * Récupère les données de version distante (méthode publique)
     */
    public function get_remote_version_data() {
        return $this->get_remote_version();
    }
    
    /**
     * Retourne l'URL du changelog
     */
    public function get_changelog_url() {
        return "https://github.com/{$this->github_username}/{$this->github_repository}/blob/main/CHANGELOG.md";
    }
    
    /**
     * Retourne les informations de debug
     */
    public function get_debug_info() {
        return [
            'plugin_file' => $this->plugin_file,
            'plugin_slug' => $this->plugin_slug,
            'current_version' => $this->plugin_version,
            'github_url' => "https://github.com/{$this->github_username}/{$this->github_repository}",
            'version_json_url' => $this->version_json_url,
            'download_url' => $this->zip_download_url,
            'cache_key' => $this->cache_key,
            'cache_allowed' => $this->cache_allowed
        ];
    }
}