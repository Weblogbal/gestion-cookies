<?php
/**
 * Classe pour afficher les informations de version et de mise à jour dans l'admin WordPress
 * @author Fabrice Simonet | Webglobal
 * @version 1.0.0
 */

if ( ! defined('ABSPATH') ) exit;

class Webglobal_Plugin_Version_Display {

    private $updater;
    private $plugin_file;
    private $text_domain;

    /**
     * Constructeur
     * @param Webglobal_Update_Checker $updater Instance de l'updater
     * @param string $plugin_file Chemin vers le fichier principal du plugin
     * @param string $text_domain Domaine de traduction
     */
    public function __construct($updater, $plugin_file, $text_domain = 'default') {
        $this->updater = $updater;
        $this->plugin_file = $plugin_file;
        $this->text_domain = $text_domain;
    }

    /**
     * Affiche les informations de version et de mise à jour
     */
    public function render() {
        $current_version = $this->get_plugin_version();
        $update_available = false;
        $remote_version = '';
        $update_url = '';

        // Vérifier s'il y a une mise à jour disponible
        if ($this->updater) {
            $remote_data = $this->updater->get_remote_version();
            if ($remote_data && version_compare($current_version, $remote_data->version, '<')) {
                $update_available = true;
                $remote_version = $remote_data->version;
                $update_url = admin_url('plugins.php');
            }
        }

        $this->render_html($current_version, $update_available, $remote_version, $update_url);
    }

    /**
     * Génère le HTML des informations de version
     */
    private function render_html($current_version, $update_available, $remote_version, $update_url) {
        ?>
        <div style="margin-top: 30px; padding: 20px; background: #f1f1f1; border-radius: 5px; border-left: 4px solid #0073aa;">
            <h3 style="margin-top: 0;"><span class="dashicons dashicons-info"></span> <?php echo esc_html($this->get_title()); ?></h3>

            <p>
                <strong><?php esc_html_e('Version actuelle :', $this->text_domain); ?></strong>
                <code><?php echo esc_html($current_version); ?></code>
            </p>

            <?php if ($update_available) : ?>
                <div class="notice notice-warning inline" style="margin: 15px 0; padding: 10px;">
                    <p>
                        <span class="dashicons dashicons-update"></span>
                        <strong><?php esc_html_e('Mise à jour disponible !', $this->text_domain); ?></strong><br>
                        <?php printf(esc_html__('Version %s disponible.', $this->text_domain), '<code>' . esc_html($remote_version) . '</code>'); ?>
                        <a href="<?php echo esc_url($update_url); ?>" class="button button-secondary" style="margin-left: 10px;">
                            <?php esc_html_e('Aller aux extensions', $this->text_domain); ?>
                        </a>
                    </p>
                </div>
            <?php else : ?>
                <p style="color: #46b450;">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e('Votre plugin est à jour !', $this->text_domain); ?>
                </p>
            <?php endif; ?>

            <p>
                <?php $this->render_links(); ?>
            </p>

            <form method="post" style="margin-top: 15px;">
                <button type="submit" name="check_update" class="button button-secondary">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Vérifier les mises à jour', $this->text_domain); ?>
                </button>
            </form>
        </div>
        <?php
    }

    /**
     * Génère les liens GitHub et changelog
     */
    private function render_links() {
        $github_url = $this->get_github_url();
        $changelog_url = $this->get_changelog_url();

        if ($github_url) {
            echo '<a href="' . esc_url($github_url) . '" target="_blank" class="button button-link">';
            echo '<span class="dashicons dashicons-external"></span> ';
            esc_html_e('Voir sur GitHub', $this->text_domain);
            echo '</a> ';
        }

        if ($changelog_url) {
            echo '<a href="' . esc_url($changelog_url) . '" target="_blank" class="button button-link">';
            echo '<span class="dashicons dashicons-media-document"></span> ';
            esc_html_e('Changelog', $this->text_domain);
            echo '</a>';
        }
    }

    /**
     * Récupère le titre à afficher
     */
    private function get_title() {
        return __('Informations sur le plugin', $this->text_domain);
    }

    /**
     * Récupère l'URL GitHub
     */
    private function get_github_url() {
        // Essayer de récupérer depuis la config de l'updater
        if ($this->updater && isset($this->updater->github_username) && isset($this->updater->github_repository)) {
            return "https://github.com/{$this->updater->github_username}/{$this->updater->github_repository}";
        }
        return '';
    }

    /**
     * Récupère l'URL du changelog
     */
    private function get_changelog_url() {
        $github_url = $this->get_github_url();
        return $github_url ? $github_url . '/blob/main/CHANGELOG.md' : '';
    }

    /**
     * Récupère la version actuelle du plugin
     */
    private function get_plugin_version() {
        $plugin_data = get_file_data($this->plugin_file, ['Version' => 'Version'], 'plugin');
        return $plugin_data['Version'];
    }

    /**
     * Gère les actions POST (vérification des mises à jour)
     */
    public function handle_post_actions() {
        if (isset($_POST['check_update'])) {
            // Vider le cache des mises à jour
            $cache_key = md5(plugin_basename($this->plugin_file) . '_update_checker');
            delete_transient($cache_key);

            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Cache des mises à jour vidé. La vérification sera effectuée lors de la prochaine visite.', $this->text_domain) . '</p></div>';
            });
        }
    }
}