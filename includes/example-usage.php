<?php
/**
 * Exemple d'utilisation de Webglobal_Plugin_Version_Display
 */

// Inclure les fichiers requis
require_once plugin_dir_path(__FILE__) . '../includes/class-update-checker.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-plugin-version-display.php';
require_once plugin_dir_path(__FILE__) . '../includes/update-config.php';

class ExemplePlugin {

    private $updater;
    private $version_display;

    public function __construct() {
        $this->init_updater();
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_post_actions']);
    }

    private function init_updater() {
        $config = require plugin_dir_path(__FILE__) . '../includes/update-config.php';

        // Adapter la config pour votre plugin
        $config['github']['repository'] = 'votre-repo-github';

        $this->updater = new Webglobal_Update_Checker(
            __FILE__,
            $config['github']['username'],
            $config['github']['repository']
        );

        $this->version_display = new Webglobal_Plugin_Version_Display(
            $this->updater,
            __FILE__,
            'exemple-plugin'
        );
    }

    public function add_admin_menu() {
        add_menu_page(
            'Exemple Plugin',
            'Exemple Plugin',
            'manage_options',
            'exemple-plugin',
            [$this, 'admin_page']
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Exemple Plugin</h1>

            <form method="post" action="options.php">
                <!-- Vos champs de configuration ici -->
                <p>Configuration de votre plugin...</p>
                <?php submit_button(); ?>
            </form>

            <?php $this->version_display->render(); ?>
        </div>
        <?php
    }

    public function handle_post_actions() {
        $this->version_display->handle_post_actions();
    }
}

// Initialiser le plugin
new ExemplePlugin();