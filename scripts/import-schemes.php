<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_CLI')) {
    return;
}

$csv = ABSPATH . 'data/schemes-3000.csv';
if (!file_exists($csv)) {
    $csv = ABSPATH . 'wp-content/uploads/schemes-3000.csv';
}
if (!file_exists($csv)) {
    WP_CLI::error('CSV not found: ' . $csv);
}

$fp = fopen($csv, 'r');
$header = fgetcsv($fp);
$count = 0;

while (($row = fgetcsv($fp)) !== false) {
    $item = array_combine($header, $row);
    $post_id = wp_insert_post([
        'post_type' => 'gov_scheme',
        'post_status' => 'publish',
        'post_title' => $item['scheme_name'],
        'post_content' => $item['benefits'],
    ]);

    $meta = [
        '_seva_setu_state' => $item['state'],
        '_seva_setu_department' => $item['department'],
        '_seva_setu_eligibility' => $item['eligibility'],
        '_seva_setu_benefits' => $item['benefits'],
        '_seva_setu_documents_required' => $item['documents_required'],
        '_seva_setu_application_process' => $item['application_process'],
        '_seva_setu_official_website' => $item['official_website'],
        '_seva_setu_min_income' => $item['min_income'],
        '_seva_setu_max_income' => $item['max_income'],
        '_seva_setu_min_age' => $item['min_age'],
        '_seva_setu_max_age' => $item['max_age'],
        '_seva_setu_gender' => $item['gender'],
        '_seva_setu_target_category' => $item['target_category'],
    ];

    foreach ($meta as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }

    $count++;
}
fclose($fp);

WP_CLI::success('Imported schemes: ' . $count);
