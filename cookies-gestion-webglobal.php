<?php
/**
 * Plugin Name: Gestion Cookies Webglobal
 * Plugin URI: https://web-global.ch
 * Description: Plugin pour gérer les cookies avec tarteaucitron.js, personnalisation des couleurs et configuration.
 * Version: 1.0.5
 * Author: Fabrice Simonet / Webglobal
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Inclusion du système de mise à jour
if (file_exists(__DIR__ . '/includes/class-update-checker.php')) {
    require_once __DIR__ . '/includes/class-update-checker.php';
}
if (file_exists(__DIR__ . '/includes/class-plugin-version-display.php')) {
    require_once __DIR__ . '/includes/class-plugin-version-display.php';
}

// Classe principale du plugin
class CookiesGestionWebglobal {

    private $update_checker;
    private $version_display;

    public function __construct() {
        // Initialiser le système de mise à jour
        $this->init_updater();

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_post_actions'));
        add_action('wp_head', array($this, 'inject_script'), 10);
        add_action('wp_head', array($this, 'inject_custom_js'), 20);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    /**
     * Initialise le système de mise à jour automatique
     */
    private function init_updater() {
        global $update_config;

        if (class_exists('Webglobal_Update_Checker') && isset($update_config)) {
            $this->update_checker = new Webglobal_Update_Checker(
                __FILE__,
                $update_config['github']['username'] ?? 'Weblogbal',
                $update_config['github']['repository'] ?? 'gestion-cookies'
            );

            // Initialiser l'affichage des versions
            $this->version_display = new Webglobal_Plugin_Version_Display(
                $this->update_checker,
                __FILE__,
                $update_config['plugin']['text_domain'] ?? 'gestion-cookies'
            );
        }
    }

    // Ajouter le menu d'administration
    public function add_admin_menu() {
        add_menu_page(
            'Gestion Cookies',
            'Gestion Cookies',
            'manage_options',
            'cookies-gestion-webglobal',
            array($this, 'admin_page'),
            'dashicons-admin-generic',
            30
        );
    }

    // Ajouter le lien "Réglages" dans la liste des plugins
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=cookies-gestion-webglobal') . '">' . __('Réglages') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    // Enregistrer les paramètres
    public function register_settings() {
        register_setting('cookies_gestion_options', 'cg_button_bg_color');
        register_setting('cookies_gestion_options', 'cg_button_text_color');
        register_setting('cookies_gestion_options', 'cg_privacy_url');
        register_setting('cookies_gestion_options', 'cg_custom_js');
    }

    // Gérer les actions POST
    public function handle_post_actions() {
        if ($this->version_display) {
            $this->version_display->handle_post_actions();
        }
    }

    // Page d'administration
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Gestion des Cookies</h1>
            <form method="post" action="options.php">
                <?php settings_fields('cookies_gestion_options'); ?>
                <?php do_settings_sections('cookies_gestion_options'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Couleur de fond du bouton</th>
                        <td><input type="color" name="cg_button_bg_color" value="<?php echo esc_attr(get_option('cg_button_bg_color', '#000000')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Couleur du texte du bouton</th>
                        <td><input type="color" name="cg_button_text_color" value="<?php echo esc_attr(get_option('cg_button_text_color', '#ffa726')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">URL de la politique de confidentialité</th>
                        <td><input type="url" name="cg_privacy_url" value="<?php echo esc_attr(get_option('cg_privacy_url', '/privacy')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">JavaScript personnalisé ( sans les balises script ) </th>
                        <td><textarea name="cg_custom_js" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(get_option('cg_custom_js', '')); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if ($this->version_display) $this->version_display->render(); ?>
        </div>
        <?php
    }

    // Injecter le script tarteaucitron
    public function inject_script() {
        $privacy_url = get_option('cg_privacy_url', '/privacy');
        $bg_color = get_option('cg_button_bg_color', '#000000');
        $text_color = get_option('cg_button_text_color', '#ffa726');
        $plugin_dir = plugin_dir_url(__FILE__);
        $parent_dir = dirname($plugin_dir);
        ?>
        <link rel="stylesheet" href="<?php echo esc_url($parent_dir . '/gestion-cookies/cookies-gestion/css/tarteaucitron.min.css'); ?>" />
        <style>
            :root {
                --tarteaucitron-button-bg-color: <?php echo esc_attr($bg_color); ?> !important;
                --tarteaucitron-button-text-color: <?php echo esc_attr($text_color); ?> !important;
            }
        </style>
        <script src="<?php echo esc_url($parent_dir . '/gestion-cookies/cookies-gestion/tarteaucitron.js'); ?>"></script>
        <script type="text/javascript">
            tarteaucitron.init({
                "privacyUrl": "<?php echo esc_js($privacy_url); ?>",
                "bodyPosition": "top",
                "hashtag": "#tarteaucitron",
                "cookieName": "emulsioncookies",
                "orientation": "middle",
                "groupServices": true,
                "showDetailsOnClick": true,
                "serviceDefaultState": "wait",
                "showAlertSmall": true,
                "cookieslist": false,
                "cookieslistEmbed": false,
                "closePopup": true,
                "showIcon": false,
                "iconPosition": "BottomLeft",
                "adblocker": false,
                "DenyAllCta": true,
                "AcceptAllCta": true,
                "highPrivacy": true,
                "alwaysNeedConsent": false,
                "handleBrowserDNTRequest": false,
                "removeCredit": true,
                "moreInfoLink": true,
                "useExternalCss": true,
                "useExternalJs": false,
                "readmoreLink": "",
                "mandatory": true,
                "mandatoryCta": false,
                "googleConsentMode": true,
                "bingConsentMode": true,
                "softConsentMode": false,
                "dataLayer": false,
                "serverSide": false,
                "partnersList": false
            });
        </script>
        <?php
    }

    // Injecter le JS personnalisé
    public function inject_custom_js() {
        $custom_js = get_option('cg_custom_js', '');
        if (!empty($custom_js)) {
            echo '<script type="text/javascript">' . $custom_js . '</script>';
        }
    }
}

// Initialiser le plugin
new CookiesGestionWebglobal();