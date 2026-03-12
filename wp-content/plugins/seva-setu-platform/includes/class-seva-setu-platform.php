<?php

if (!defined('ABSPATH')) {
    exit;
}

class Seva_Setu_Platform {
    const COMMISSION_AGENT = 0.60;
    const COMMISSION_PLATFORM = 0.40;
    const DEFAULT_SERVICE_FEE = 100;

    public function init() {
        add_action('init', [$this, 'register_roles']);
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post', [$this, 'save_scheme_meta']);
        add_action('save_post', [$this, 'save_application_meta']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_post_nopriv_seva_setu_agent_register', [$this, 'handle_agent_registration']);
        add_action('admin_post_seva_setu_agent_register', [$this, 'handle_agent_registration']);
        add_action('admin_post_nopriv_seva_setu_citizen_register', [$this, 'handle_citizen_registration']);
        add_action('admin_post_seva_setu_citizen_register', [$this, 'handle_citizen_registration']);
        add_action('admin_post_seva_setu_apply_scheme', [$this, 'handle_scheme_application']);
        add_filter('manage_gov_scheme_posts_columns', [$this, 'scheme_columns']);
        add_action('manage_gov_scheme_posts_custom_column', [$this, 'render_scheme_columns'], 10, 2);

        add_shortcode('seva_setu_agent_registration', [$this, 'agent_registration_shortcode']);
        add_shortcode('seva_setu_citizen_portal', [$this, 'citizen_portal_shortcode']);
        add_shortcode('seva_setu_scheme_search', [$this, 'scheme_search_shortcode']);
        add_shortcode('seva_setu_apply_form', [$this, 'apply_form_shortcode']);
        add_shortcode('seva_setu_ai_finder', [$this, 'ai_finder_shortcode']);
        add_shortcode('seva_setu_application_tracker', [$this, 'application_tracker_shortcode']);

        add_action('show_user_profile', [$this, 'render_custom_user_fields']);
        add_action('edit_user_profile', [$this, 'render_custom_user_fields']);
        add_action('personal_options_update', [$this, 'save_custom_user_fields']);
        add_action('edit_user_profile_update', [$this, 'save_custom_user_fields']);
    }

    public static function activate() {
        $instance = new self();
        $instance->register_roles();
        $instance->register_post_types();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public function register_roles() {
        add_role('agent', 'Agent', [
            'read' => true,
            'upload_files' => true,
            'edit_posts' => false,
        ]);

        add_role('citizen', 'Citizen', [
            'read' => true,
        ]);
    }

    public function register_post_types() {
        register_post_type('gov_scheme', [
            'label' => 'Government Schemes',
            'public' => true,
            'menu_icon' => 'dashicons-archive',
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_rest' => true,
        ]);

        register_post_type('scheme_application', [
            'label' => 'Applications',
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-forms',
            'supports' => ['title'],
        ]);
    }

    public function register_taxonomies() {
        register_taxonomy('scheme_category', 'gov_scheme', [
            'label' => 'Scheme Categories',
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);
    }

    public function register_meta_boxes() {
        add_meta_box('seva_setu_scheme_details', 'Scheme Details', [$this, 'render_scheme_meta_box'], 'gov_scheme');
        add_meta_box('seva_setu_application_details', 'Application Details', [$this, 'render_application_meta_box'], 'scheme_application');
    }

    public function render_scheme_meta_box($post) {
        wp_nonce_field('seva_setu_scheme_nonce', 'seva_setu_scheme_nonce_field');
        $fields = [
            'state' => 'State',
            'department' => 'Department',
            'eligibility' => 'Eligibility',
            'benefits' => 'Benefits',
            'documents_required' => 'Documents Required',
            'application_process' => 'Application Process',
            'official_website' => 'Official Website',
            'min_income' => 'Minimum Income',
            'max_income' => 'Maximum Income',
            'min_age' => 'Minimum Age',
            'max_age' => 'Maximum Age',
            'gender' => 'Gender',
            'target_category' => 'Target Category',
        ];

        foreach ($fields as $key => $label) {
            $value = get_post_meta($post->ID, '_seva_setu_' . $key, true);
            echo '<p><label><strong>' . esc_html($label) . '</strong></label><br/>';
            if (in_array($key, ['eligibility', 'benefits', 'documents_required', 'application_process'], true)) {
                echo '<textarea style="width:100%" rows="3" name="seva_setu_' . esc_attr($key) . '">' . esc_textarea($value) . '</textarea></p>';
            } else {
                echo '<input style="width:100%" type="text" name="seva_setu_' . esc_attr($key) . '" value="' . esc_attr($value) . '"/></p>';
            }
        }
    }

    public function render_application_meta_box($post) {
        wp_nonce_field('seva_setu_application_nonce', 'seva_setu_application_nonce_field');
        $fields = [
            'citizen_name' => 'Citizen Name',
            'agent_name' => 'Agent Name',
            'scheme_name' => 'Scheme Name',
            'status' => 'Application Status',
            'documents' => 'Documents',
            'service_fee' => 'Service Fee',
            'agent_commission' => 'Agent Commission',
            'platform_fee' => 'Platform Fee',
        ];

        foreach ($fields as $key => $label) {
            $value = get_post_meta($post->ID, '_seva_setu_' . $key, true);
            echo '<p><label><strong>' . esc_html($label) . '</strong></label><br/>';
            echo '<input style="width:100%" type="text" name="seva_setu_' . esc_attr($key) . '" value="' . esc_attr($value) . '"/></p>';
        }
    }

    public function save_scheme_meta($post_id) {
        if (get_post_type($post_id) !== 'gov_scheme') {
            return;
        }
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        if (!isset($_POST['seva_setu_scheme_nonce_field']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['seva_setu_scheme_nonce_field'])), 'seva_setu_scheme_nonce')) {
            return;
        }
        $keys = ['state', 'department', 'eligibility', 'benefits', 'documents_required', 'application_process', 'official_website', 'min_income', 'max_income', 'min_age', 'max_age', 'gender', 'target_category'];
        foreach ($keys as $key) {
            if (isset($_POST['seva_setu_' . $key])) {
                update_post_meta($post_id, '_seva_setu_' . $key, sanitize_text_field(wp_unslash($_POST['seva_setu_' . $key])));
            }
        }
    }

    public function save_application_meta($post_id) {
        if (get_post_type($post_id) !== 'scheme_application') {
            return;
        }
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        if (!isset($_POST['seva_setu_application_nonce_field']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['seva_setu_application_nonce_field'])), 'seva_setu_application_nonce')) {
            return;
        }
        $keys = ['citizen_name', 'agent_name', 'scheme_name', 'status', 'documents', 'service_fee', 'agent_commission', 'platform_fee'];
        foreach ($keys as $key) {
            if (isset($_POST['seva_setu_' . $key])) {
                update_post_meta($post_id, '_seva_setu_' . $key, sanitize_text_field(wp_unslash($_POST['seva_setu_' . $key])));
            }
        }
    }

    public function register_admin_menu() {
        add_menu_page('Seva Setu Dashboard', 'Seva Setu Dashboard', 'manage_options', 'seva-setu-dashboard', [$this, 'render_admin_dashboard'], 'dashicons-chart-pie', 3);
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style('seva-setu-admin', SEVA_SETU_PLUGIN_URL . 'assets/css/admin.css', [], '1.0.0');
    }

    public function enqueue_public_assets() {
        wp_enqueue_style('seva-setu-public', SEVA_SETU_PLUGIN_URL . 'assets/css/public.css', [], '1.0.0');
    }

    public function render_admin_dashboard() {
        $agents = count(get_users(['role' => 'agent']));
        $citizens = count(get_users(['role' => 'citizen']));
        $schemes = wp_count_posts('gov_scheme')->publish ?? 0;
        $applications = wp_count_posts('scheme_application')->publish ?? 0;

        $app_posts = get_posts(['post_type' => 'scheme_application', 'numberposts' => -1]);
        $monthly_revenue = 0;
        $agent_commission = 0;
        $platform_earnings = 0;

        foreach ($app_posts as $app) {
            $fee = (float) get_post_meta($app->ID, '_seva_setu_service_fee', true);
            $monthly_revenue += $fee;
            $agent_commission += (float) get_post_meta($app->ID, '_seva_setu_agent_commission', true);
            $platform_earnings += (float) get_post_meta($app->ID, '_seva_setu_platform_fee', true);
        }

        echo '<div class="wrap"><h1>SEVA SETU KENDRA Dashboard</h1><div class="seva-grid">';
        $cards = [
            'Total Agents' => $agents,
            'Total Citizens' => $citizens,
            'Total Schemes' => $schemes,
            'Total Applications' => $applications,
            'Monthly Revenue' => '₹' . number_format_i18n($monthly_revenue, 2),
            'Agent Commissions' => '₹' . number_format_i18n($agent_commission, 2),
            'Platform Earnings' => '₹' . number_format_i18n($platform_earnings, 2),
        ];

        foreach ($cards as $title => $value) {
            echo '<div class="seva-card"><h3>' . esc_html($title) . '</h3><p>' . esc_html((string) $value) . '</p></div>';
        }

        echo '</div>';
        echo '<h2>Agent Approval Workflow</h2><p>Manage pending users from Users > All Users. Agent accounts use <code>agent_status</code> meta: pending / approved / rejected.</p>';
        echo '</div>';
    }

    public function agent_registration_shortcode() {
        ob_start();
        ?>
        <form class="seva-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="seva_setu_agent_register">
            <?php wp_nonce_field('seva_setu_agent_register', 'seva_setu_nonce'); ?>
            <h3>Become Seva Setu Agent</h3>
            <?php $this->form_field('full_name', 'Full Name'); ?>
            <?php $this->form_field('mobile', 'Mobile Number'); ?>
            <?php $this->form_field('email', 'Email', 'email'); ?>
            <?php $this->form_field('password', 'Password', 'password'); ?>
            <?php $this->form_field('aadhar', 'Aadhar Number'); ?>
            <?php $this->form_field('pan', 'PAN Number'); ?>
            <?php $this->form_field('state', 'State'); ?>
            <?php $this->form_field('district', 'District'); ?>
            <?php $this->form_field('address', 'Address'); ?>
            <?php $this->form_field('education', 'Education'); ?>
            <p><label>Upload Documents</label><input type="file" name="documents"></p>
            <button type="submit">Submit Registration</button>
        </form>
        <?php
        return ob_get_clean();
    }

    public function citizen_portal_shortcode() {
        ob_start();
        ?>
        <div class="seva-portal-grid">
            <form class="seva-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="seva_setu_citizen_register">
                <?php wp_nonce_field('seva_setu_citizen_register', 'seva_setu_nonce'); ?>
                <h3>Citizen Registration</h3>
                <?php $this->form_field('name', 'Name'); ?>
                <?php $this->form_field('mobile', 'Mobile'); ?>
                <?php $this->form_field('email', 'Email', 'email'); ?>
                <?php $this->form_field('state', 'State'); ?>
                <?php $this->form_field('district', 'District'); ?>
                <?php $this->form_field('income', 'Income'); ?>
                <?php $this->form_field('category', 'Category'); ?>
                <button type="submit">Create Citizen Account</button>
            </form>
            <div>
                <?php echo do_shortcode('[seva_setu_scheme_search]'); ?>
                <?php echo do_shortcode('[seva_setu_application_tracker]'); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function scheme_search_shortcode() {
        $state = isset($_GET['scheme_state']) ? sanitize_text_field(wp_unslash($_GET['scheme_state'])) : '';
        $query = new WP_Query([
            'post_type' => 'gov_scheme',
            'posts_per_page' => 10,
            's' => isset($_GET['scheme_q']) ? sanitize_text_field(wp_unslash($_GET['scheme_q'])) : '',
            'meta_query' => $state ? [[
                'key' => '_seva_setu_state',
                'value' => $state,
                'compare' => 'LIKE',
            ]] : [],
        ]);

        ob_start();
        ?>
        <form class="seva-form" method="get">
            <h3>Search Government Schemes</h3>
            <p><label>Keyword</label><input type="text" name="scheme_q" value="<?php echo isset($_GET['scheme_q']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['scheme_q']))) : ''; ?>"></p>
            <p><label>State</label><input type="text" name="scheme_state" value="<?php echo esc_attr($state); ?>"></p>
            <button type="submit">Search</button>
        </form>
        <ul class="seva-scheme-list">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <li>
                    <strong><?php the_title(); ?></strong>
                    <div><?php echo esc_html(get_post_meta(get_the_ID(), '_seva_setu_state', true)); ?> | <?php echo esc_html(get_post_meta(get_the_ID(), '_seva_setu_department', true)); ?></div>
                </li>
            <?php endwhile; wp_reset_postdata(); ?>
        </ul>
        <?php
        return ob_get_clean();
    }

    public function apply_form_shortcode() {
        if (!is_user_logged_in() || !in_array('agent', wp_get_current_user()->roles, true)) {
            return '<p>Only approved agents can submit applications.</p>';
        }

        $agent_status = get_user_meta(get_current_user_id(), 'agent_status', true);
        if ($agent_status !== 'approved') {
            return '<p>Your agent account is not approved yet. Current status: ' . esc_html($agent_status ?: 'pending') . '.</p>';
        }

        ob_start();
        ?>
        <form class="seva-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="seva_setu_apply_scheme">
            <?php wp_nonce_field('seva_setu_apply_scheme', 'seva_setu_nonce'); ?>
            <h3>Apply Scheme for Citizen</h3>
            <?php $this->form_field('citizen_name', 'Citizen Name'); ?>
            <?php $this->form_field('scheme_name', 'Scheme Name'); ?>
            <?php $this->form_field('documents', 'Documents Submitted'); ?>
            <button type="submit">Submit Application</button>
        </form>
        <?php
        return ob_get_clean();
    }

    public function ai_finder_shortcode() {
        $matches = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seva_setu_ai_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['seva_setu_ai_nonce'])), 'seva_setu_ai_finder')) {
            $state = sanitize_text_field(wp_unslash($_POST['state'] ?? ''));
            $income = (int) ($_POST['income'] ?? 0);
            $age = (int) ($_POST['age'] ?? 0);
            $gender = sanitize_text_field(wp_unslash($_POST['gender'] ?? ''));
            $category = sanitize_text_field(wp_unslash($_POST['category'] ?? ''));

            $candidates = get_posts([
                'post_type' => 'gov_scheme',
                'numberposts' => 15,
                'meta_query' => [[
                    'key' => '_seva_setu_state',
                    'value' => $state,
                    'compare' => 'LIKE',
                ]],
            ]);

            foreach ($candidates as $scheme) {
                $min_income = (int) get_post_meta($scheme->ID, '_seva_setu_min_income', true);
                $max_income = (int) get_post_meta($scheme->ID, '_seva_setu_max_income', true);
                $min_age = (int) get_post_meta($scheme->ID, '_seva_setu_min_age', true);
                $max_age = (int) get_post_meta($scheme->ID, '_seva_setu_max_age', true);
                $target_gender = strtolower((string) get_post_meta($scheme->ID, '_seva_setu_gender', true));
                $target_category = strtolower((string) get_post_meta($scheme->ID, '_seva_setu_target_category', true));

                $eligible = ($income >= $min_income && ($max_income === 0 || $income <= $max_income))
                    && ($age >= $min_age && ($max_age === 0 || $age <= $max_age))
                    && ($target_gender === '' || $target_gender === 'all' || $target_gender === strtolower($gender))
                    && ($target_category === '' || $target_category === 'all' || $target_category === strtolower($category));

                if ($eligible) {
                    $matches[] = $scheme;
                }
            }
        }

        ob_start();
        ?>
        <form class="seva-form" method="post">
            <h3>AI Scheme Finder</h3>
            <?php wp_nonce_field('seva_setu_ai_finder', 'seva_setu_ai_nonce'); ?>
            <?php $this->form_field('state', 'State'); ?>
            <?php $this->form_field('income', 'Income'); ?>
            <?php $this->form_field('age', 'Age'); ?>
            <?php $this->form_field('gender', 'Gender'); ?>
            <?php $this->form_field('category', 'Category'); ?>
            <button type="submit">Get Recommendations</button>
        </form>
        <?php if (!empty($matches)) : ?>
            <h4>Recommended Schemes</h4>
            <ul class="seva-scheme-list">
                <?php foreach ($matches as $match) : ?>
                    <li><?php echo esc_html($match->post_title); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    public function application_tracker_shortcode() {
        $apps = [];
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (in_array('citizen', $user->roles, true)) {
                $apps = get_posts([
                    'post_type' => 'scheme_application',
                    'numberposts' => 20,
                    'meta_key' => '_seva_setu_citizen_user_id',
                    'meta_value' => (string) $user->ID,
                ]);
            }
        }

        ob_start();
        echo '<h3>Application Tracker</h3><ul class="seva-scheme-list">';
        if (empty($apps)) {
            echo '<li>No applications available.</li>';
        } else {
            foreach ($apps as $app) {
                echo '<li>' . esc_html($app->post_title) . ' - ' . esc_html(get_post_meta($app->ID, '_seva_setu_status', true)) . '</li>';
            }
        }
        echo '</ul>';
        return ob_get_clean();
    }

    private function form_field($name, $label, $type = 'text') {
        echo '<p><label>' . esc_html($label) . '</label><input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" required></p>';
    }

    public function handle_agent_registration() {
        if (!isset($_POST['seva_setu_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['seva_setu_nonce'])), 'seva_setu_agent_register')) {
            wp_die('Invalid request');
        }

        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $password = sanitize_text_field(wp_unslash($_POST['password'] ?? ''));
        $full_name = sanitize_text_field(wp_unslash($_POST['full_name'] ?? ''));

        if (empty($email) || !is_email($email) || empty($password) || empty($full_name)) {
            wp_die('Please provide valid required fields.');
        }

        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) {
            wp_die('Unable to create account.');
        }

        wp_update_user(['ID' => $user_id, 'display_name' => $full_name, 'role' => 'agent']);
        update_user_meta($user_id, 'agent_status', 'pending');
        update_user_meta($user_id, 'mobile_verified', 'pending_otp');
        update_user_meta($user_id, 'email_verified', 'pending_email_verification');

        $fields = ['mobile', 'aadhar', 'pan', 'state', 'district', 'address', 'education'];
        foreach ($fields as $field) {
            update_user_meta($user_id, 'agent_' . $field, sanitize_text_field(wp_unslash($_POST[$field] ?? '')));
        }

        if (!empty($_FILES['documents']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $upload = wp_handle_upload($_FILES['documents'], ['test_form' => false]);
            if (empty($upload['error']) && !empty($upload['url'])) {
                update_user_meta($user_id, 'agent_documents_url', esc_url_raw($upload['url']));
            }
        }

        wp_mail($email, 'Verify Your Seva Setu Agent Account', 'Please verify your email. (Integrate OTP and verification providers in production).');
        wp_safe_redirect(home_url('/become-agent/?registered=1'));
        exit;
    }

    public function handle_citizen_registration() {
        if (!isset($_POST['seva_setu_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['seva_setu_nonce'])), 'seva_setu_citizen_register')) {
            wp_die('Invalid request');
        }

        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $password = wp_generate_password(12, true, true);
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));

        if (empty($email) || !is_email($email) || empty($name)) {
            wp_die('Please provide valid required fields.');
        }

        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) {
            wp_die('Unable to create citizen account.');
        }

        wp_update_user(['ID' => $user_id, 'display_name' => $name, 'role' => 'citizen']);
        foreach (['mobile', 'state', 'district', 'income', 'category'] as $field) {
            update_user_meta($user_id, 'citizen_' . $field, sanitize_text_field(wp_unslash($_POST[$field] ?? '')));
        }

        wp_mail($email, 'Your Citizen Portal Credentials', 'Temporary password: ' . $password);
        wp_safe_redirect(home_url('/citizen-portal/?registered=1'));
        exit;
    }

    public function handle_scheme_application() {
        if (!is_user_logged_in() || !in_array('agent', wp_get_current_user()->roles, true)) {
            wp_die('Unauthorized');
        }

        if (get_user_meta(get_current_user_id(), 'agent_status', true) !== 'approved') {
            wp_die('Only approved agents can submit applications.');
        }

        if (!isset($_POST['seva_setu_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['seva_setu_nonce'])), 'seva_setu_apply_scheme')) {
            wp_die('Invalid request');
        }

        $citizen = sanitize_text_field(wp_unslash($_POST['citizen_name'] ?? ''));
        $scheme = sanitize_text_field(wp_unslash($_POST['scheme_name'] ?? ''));
        $documents = sanitize_text_field(wp_unslash($_POST['documents'] ?? ''));
        $fee = self::DEFAULT_SERVICE_FEE;

        if (empty($citizen) || empty($scheme)) {
            wp_die('Citizen and scheme names are required.');
        }

        $citizen_user = get_user_by('login', $citizen);
        if (!$citizen_user) {
            $citizen_user = get_user_by('email', $citizen);
        }

        $post_id = wp_insert_post([
            'post_type' => 'scheme_application',
            'post_status' => 'publish',
            'post_title' => $citizen . ' - ' . $scheme,
        ]);

        update_post_meta($post_id, '_seva_setu_citizen_name', $citizen);
        if ($citizen_user instanceof WP_User) {
            update_post_meta($post_id, '_seva_setu_citizen_user_id', (string) $citizen_user->ID);
        }
        update_post_meta($post_id, '_seva_setu_agent_name', wp_get_current_user()->display_name);
        update_post_meta($post_id, '_seva_setu_scheme_name', $scheme);
        update_post_meta($post_id, '_seva_setu_status', 'Submitted');
        update_post_meta($post_id, '_seva_setu_documents', $documents);
        update_post_meta($post_id, '_seva_setu_service_fee', $fee);
        update_post_meta($post_id, '_seva_setu_agent_commission', $fee * self::COMMISSION_AGENT);
        update_post_meta($post_id, '_seva_setu_platform_fee', $fee * self::COMMISSION_PLATFORM);

        wp_safe_redirect(home_url('/citizen-portal/?applied=1'));
        exit;
    }

    public function scheme_columns($columns) {
        $columns['state'] = 'State';
        $columns['department'] = 'Department';
        return $columns;
    }

    public function render_scheme_columns($column, $post_id) {
        if ($column === 'state') {
            echo esc_html(get_post_meta($post_id, '_seva_setu_state', true));
        }
        if ($column === 'department') {
            echo esc_html(get_post_meta($post_id, '_seva_setu_department', true));
        }
    }

    public function render_custom_user_fields($user) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $status = get_user_meta($user->ID, 'agent_status', true);
        if (!in_array('agent', (array) $user->roles, true)) {
            return;
        }

        ?>
        <h3>Agent Approval</h3>
        <table class="form-table">
            <tr>
                <th><label for="agent_status">Status</label></th>
                <td>
                    <select name="agent_status" id="agent_status">
                        <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
                        <option value="approved" <?php selected($status, 'approved'); ?>>Approved</option>
                        <option value="rejected" <?php selected($status, 'rejected'); ?>>Rejected</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_custom_user_fields($user_id) {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (isset($_POST['agent_status'])) {
            update_user_meta($user_id, 'agent_status', sanitize_text_field(wp_unslash($_POST['agent_status'])));
        }
    }
}
