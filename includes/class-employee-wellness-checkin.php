<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

class Employee_Wellness_Checkin {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Employee_Wellness_Checkin_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->plugin_name = 'employee-wellness-checkin';
        $this->version = EWC_VERSION;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_shortcodes();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Employee_Wellness_Checkin_Loader. Orchestrates the hooks of the plugin.
     * - Employee_Wellness_Checkin_i18n. Defines internationalization functionality.
     * - Employee_Wellness_Checkin_Admin. Defines all hooks for the admin area.
     * - Employee_Wellness_Checkin_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once EWC_PLUGIN_DIR . 'includes/class-employee-wellness-checkin-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once EWC_PLUGIN_DIR . 'includes/class-employee-wellness-checkin-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once EWC_PLUGIN_DIR . 'admin/class-employee-wellness-checkin-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once EWC_PLUGIN_DIR . 'public/class-employee-wellness-checkin-public.php';

        /**
         * The class responsible for defining all database operations.
         */
        require_once EWC_PLUGIN_DIR . 'includes/class-employee-wellness-checkin-db.php';

        /**
         * The class responsible for handling notifications.
         */
        require_once EWC_PLUGIN_DIR . 'includes/class-employee-wellness-checkin-notifications.php';

        /**
         * The class responsible for activation tasks.
         */
        require_once EWC_PLUGIN_DIR . 'includes/class-employee-wellness-checkin-activator.php';

        /**
         * The class responsible for deactivation tasks.
         */
        require_once EWC_PLUGIN_DIR . 'includes/class-employee-wellness-checkin-deactivator.php';

        $this->loader = new Employee_Wellness_Checkin_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Employee_Wellness_Checkin_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Employee_Wellness_Checkin_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Employee_Wellness_Checkin_Admin($this->get_plugin_name(), $this->get_version());

        // Admin scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Admin menu and settings
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

        // Schedule daily check for stress/tired alerts
        $this->loader->add_action('admin_init', $plugin_admin, 'schedule_alert_check');
        $this->loader->add_action('ewc_check_alerts', $plugin_admin, 'check_for_alerts');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Employee_Wellness_Checkin_Public($this->get_plugin_name(), $this->get_version());

        // Public scripts and styles
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        // AJAX handlers for mood submission
        $this->loader->add_action('wp_ajax_ewc_submit_mood', $plugin_public, 'handle_mood_submission');
    }

    /**
     * Register all shortcodes.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_shortcodes() {
        $plugin_public = new Employee_Wellness_Checkin_Public($this->get_plugin_name(), $this->get_version());
        $plugin_admin = new Employee_Wellness_Checkin_Admin($this->get_plugin_name(), $this->get_version());

        // Register shortcodes
        add_shortcode('employee_wellness_checkin_form', array($plugin_public, 'render_checkin_form'));
        add_shortcode('employee_wellness_report_dashboard', array($plugin_admin, 'render_report_dashboard'));
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Employee_Wellness_Checkin_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}