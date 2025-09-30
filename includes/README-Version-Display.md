# Webglobal Plugin Version Display

Classe indépendante pour afficher les informations de version et de mise à jour dans l'admin WordPress.

## Utilisation

### Inclusion des fichiers requis

```php
require_once 'includes/class-update-checker.php';
require_once 'includes/class-plugin-version-display.php';
require_once 'includes/update-config.php';
```

### Initialisation

```php
// Initialiser l'updater
$config = require 'includes/update-config.php';
$updater = new Webglobal_Update_Checker(
    __FILE__,
    $config['github']['username'],
    $config['github']['repository']
);

// Initialiser l'affichage des versions
$version_display = new Webglobal_Plugin_Version_Display(
    $updater,
    __FILE__,
    'votre-domaine-traduction'
);
```

### Affichage dans l'admin

```php
// Dans votre page d'administration
?>
<div class="wrap">
    <h1>Mon Plugin</h1>
    <!-- Vos paramètres -->

    <?php $version_display->render(); ?>
</div>
<?php
```

### Gestion des actions POST

```php
// Dans admin_init
add_action('admin_init', function() use ($version_display) {
    $version_display->handle_post_actions();
});
```

## Personnalisation

La classe détecte automatiquement :
- L'URL GitHub depuis la configuration de l'updater
- Le domaine de traduction fourni
- Les informations de version du plugin

## Exemple complet

```php
<?php
class MonPlugin {

    private $updater;
    private $version_display;

    public function __construct() {
        $this->init_updater();
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_post_actions']);
    }

    private function init_updater() {
        $config = require plugin_dir_path(__FILE__) . 'includes/update-config.php';
        $this->updater = new Webglobal_Update_Checker(__FILE__, $config['github']['username'], $config['github']['repository']);
        $this->version_display = new Webglobal_Plugin_Version_Display($this->updater, __FILE__, 'mon-plugin');
    }

    public function add_admin_menu() {
        add_menu_page('Mon Plugin', 'Mon Plugin', 'manage_options', 'mon-plugin', [$this, 'admin_page']);
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Mon Plugin</h1>
            <!-- Vos formulaires de configuration -->

            <?php $this->version_display->render(); ?>
        </div>
        <?php
    }

    public function handle_post_actions() {
        $this->version_display->handle_post_actions();
    }
}
```