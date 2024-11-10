<?php
/**
 * Kognetiks Chatbot for WordPress - Markov Chain - Settings - Ver 2.1.6.1
 *
 * This file contains the code for the Markov Chain settings page.
 * It manages the settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Markov Chain Options Callback - Ver 2.1.6
function chatbot_markov_chain_model_settings_section_callback($args) {

    // See if the scanner needs to run
    $results = chatbot_markov_chain_build_results_callback(esc_attr(get_option('chatbot_markov_chain_build_schedule')));

    ?>
    <p>Configure the settings for the plugin when using Markov Chain models. Some example shortcodes include:</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot style="floating" model="markov-chain-2024-09-17"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="markov-chain-2024-09-17"&#93;</code> - Style is embedded, specific model</li>
    </ul>
    <p>Markov Chain models generate text using a local algorithm based on the <a href="https://en.wikipedia.org/wiki/Markov_chain" target="_blank" rel="noopener noreferrer">Markov Chain</a> concept. They are trained on your site's published content, including pages, posts, and comments. These models run locally on your server and are not available on the OpenAI platform. While they can produce useful text, they are less advanced than OpenAI models and may sometimes generate nonsensical output. However, they can still be effective when your site has a large amount of content.</p> 
    <?php
}

function chatbot_markov_chain_api_model_general_section_callback($args){
    ?>
    <p>Configure the settings for the plugin when using Markov Chain models.  Depending on the Markov Chain model you choose, the maximum tokens may be as high as 4000.  The default is 500.</p>
    <?php
}

// Markov Chain Advanced Settings Callback - Ver 2.1.9
function chatbot_markov_chain_advanced_settings_section_callback($args) {

    ?>
    <p>Configure the advanced settings for the plugin when using Markov Chain models.</p>
    <p>Schedule the Markov Chain model build process to run at different intervals. The build process trains the model on your site's published content, including pages, posts, and comments. The model is then used to generate text for the chatbot. The build process can be resource-intensive, so it is recommended to run it during off-peak hours or less frequently on high-traffic sites.</p>
    <p>The Markov Chain model length determines the number of tokens used to generate text. A higher length can produce more coherent text but may also be more prone to overfitting. The next phrase length determines the number of tokens used to generate the next phrase in a conversation. A higher length can produce more contextually relevant responses but may also be more prone to overfitting.</p>
    <?php

}

// Markov Chain Build Schedule Callback - Ver 2.1.6
function chatbot_markov_chain_build_schedule_callback($args) {

    // Get the saved chatbot_markov_chain_build_schedule value or default to "No"
    $chatbot_markov_chain_build_schedule = esc_attr(get_option('chatbot_markov_chain_build_schedule', 'No'));
    
    $options = [
        'No' => 'No',
        'Now' => 'Now',
        'Hourly' => 'Hourly',
        'Twice Daily' => 'Twice Daily',
        'Daily' => 'Daily',
        'Weekly' => 'Weekly',
        'Disable' => 'Disable',
        'Cancel' => 'Cancel'
    ];
    ?>
    <select id="chatbot_markov_chain_build_schedule" name="chatbot_markov_chain_build_schedule">
        <?php foreach ($options as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected($chatbot_markov_chain_build_schedule, $value); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
    
    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'chatbot_markov_chain_build_schedule: ' . $chatbot_markov_chain_build_schedule );
    
}

// Markov Chain Length Options Callback - Ver 2.1.6
function chatbot_markov_chain_length_callback($args) {

    // Get the saved chatbot_markov_chain_length_setting value or default to 10
    $markov_chain_length = esc_attr(get_option('chatbot_markov_chain_length', '3'));
    // Allow for a range of tokens between 1 and 10 in 1-step increments - Ver 2.1.6
    ?>
    <select id="chatbot_markov_chain_length" name="chatbot_markov_chain_length">
        <?php
        for ($i=1; $i<=10; $i+=1) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($markov_chain_length, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Markov Chain Next Phrase Length Settings Callback - Ver 2.1.6
function chatbot_markov_chain_next_phrase_length_callback($args) {

    // Get the saved chatbot_markov_chain_next_phrase_length_setting value or default to 10
    $markov_chain_next_phrase_length = esc_attr(get_option('chatbot_markov_chain_next_phrase_length', '1'));
    // Allow for a range of tokens between 10 and 1000 in 10-step increments - Ver 2.1.6
    ?>
    <select id="chatbot_markov_chain_next_phrase_length" name="chatbot_markov_chain_next_phrase_length">
        <?php
        for ($i=1; $i<=10; $i+=1) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($markov_chain_next_phrase_length, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
    
}

// Markov Chain Model Choice Callback - Ver 2.1.8
function chatbot_markov_chain_model_choice_callback($args) {

    global $chatbot_markov_chain_api_enabled;
    
    // Get the saved chatbot_markov_chain_model_choice value or default to "markov-chain-2024-09-17"
    $model_choice = esc_attr(get_option('chatbot_markov_chain_model_choice', 'markov-chain-2024-09-17'));

    ?>
    <select id="chatbot_markov_chain_model_choice" name="chatbot_markov_chain_model_choice">
        <option value="<?php echo esc_attr( 'markov-chain-2024-09-17' ); ?>" <?php selected( $model_choice, 'markov-chain-2024-09-17' ); ?>><?php echo esc_html( 'markov-chain-2024-09-17' ); ?></option>
        <option value="<?php echo esc_attr( 'markov-chain-2024-11-10' ); ?>" <?php selected( $model_choice, 'markov-chain-2024-11-10' ); ?>><?php echo esc_html( 'markov-chain-2024-11-10' ); ?></option>
    </select>
    <?php

}

// Max Tokens choice - Ver 2.1.9
function chatbot_markov_chain_max_tokens_setting_callback($args) {

    // Get the saved chatbot_markov_chain_max_tokens or default to 500
    $max_tokens = esc_attr(get_option('chatbot_markov_chain_max_tokens', '500'));

    // Allow for a range of tokens between 100 and 4096 in 100-step increments - Ver 2.0.4
    ?>
    <select id="chatbot_markov_chain_max_tokens" name="chatbot_markov_chain_max_tokens">
        <?php
        for ($i=100; $i<=4000; $i+=100) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($max_tokens, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Knowledge Navigator Status - Ver 2.0.0.
function chatbot_markov_chain_status_section_callback($args) {

    // See if the scanner is needs to run
    $chatbot_markov_chain_current_build_schedule = esc_attr(get_option('chatbot_markov_chain_build_schedule', 'No Schedule'));
    if ($chatbot_markov_chain_current_build_schedule == 'No') {
        $chatbot_markov_chain_current_build_schedule  = 'No Schedule';
    }

    // Force run the scanner
    // $results = chatbot_chatgpt_kn_acquire();

    // Get DB Stats
    $chatbot_markov_chain_db_stats = getDatabaseStats("chatbot_markov_chain");
    // Unpack the returned array
    $chatbot_markov_chain_row_count = $chatbot_markov_chain_db_stats['row_count'];
    $chatbot_markov_chain_table_size_mb = $chatbot_markov_chain_db_stats['table_size_mb'];
    
    ?>
        <div class="wrap">
            <div style="background-color: white; border: 1px solid #ccc; padding: 10px; margin: 10px; display: inline-block;">
                <p><b>Scheduled to Run: </b><?php echo $chatbot_markov_chain_current_build_schedule; ?></p>
                <p><b>Status of Last Run: </b><?php echo esc_attr(get_option('chatbot_markov_chain_last_updated', 'Please select a Build Schedule below.')); ?></p>
                <p><b>Row Count: </b><?php echo $chatbot_markov_chain_row_count; ?></p>
                <p><b>Table Size: </b><?php echo $chatbot_markov_chain_table_size_mb; ?> MB</p>
            </div>
            <p>Refresh this page to determine the progress and status of Knowledge Navigation!</p>
        </div>
    <?php
}

// Register API settings - Moved for Ver 2.1.8
function chatbot_markov_chain_api_settings_init() {

    add_settings_section(
        'chatbot_markov_chain_api_enabled_section',
        'API/Markov Chain Settings',
        'chatbot_markov_chain_model_settings_section_callback',
        'chatbot_markov_chain_model_settings_general'
    );

    // Markov Chain Options - Ver 2.1.6
    register_setting('chatbot_markov_chain_api_model', 'chatbot_markov_chain_api_enabled'); // Ver 2.1.6
    register_setting('chatbot_markov_chain_api_model', 'chatbot_markov_chain_model_choice'); // Ver 2.1.8
    register_setting('chatbot_markov_chain_api_model', 'chatbot_markov_chain_max_tokens'); // Ver 2.1.9
    register_setting('chatbot_markov_chain_api_model', 'chatbot_markov_chain_build_schedule'); // Ver 2.1.6
    register_setting('chatbot_markov_chain_api_model', 'chatbot_markov_chain_length'); // Ver 2.1.6
    register_setting('chatbot_markov_chain_api_model', 'chatbot_markov_chain_next_phrase_length'); // Ver 2.1.6

    add_settings_section(
        'chatbot_markov_chain_api_model_general_section',
        'Markov Chain Settings',
        'chatbot_markov_chain_api_model_general_section_callback',
        'chatbot_markov_chain_api_model_general'
    );

    add_settings_field(
        'chatbot_markov_chain_model_choice',
        'Markov Chain Model Choice',
        'chatbot_markov_chain_model_choice_callback',
        'chatbot_markov_chain_api_model_general',
        'chatbot_markov_chain_api_model_general_section'
    );

    add_settings_field(
        'chatbot_markov_chain_max_tokens',
        'Maximum Tokens Setting',
        'chatbot_markov_chain_max_tokens_setting_callback',
        'chatbot_markov_chain_api_model_general',
        'chatbot_markov_chain_api_model_general_section'
    );

    add_settings_section(
        'chatbot_markov_chain_status_section',
        'Markov Chain Build Status',
        'chatbot_markov_chain_status_section_callback',
        'chatbot_markov_chain_status'
    );

    add_settings_section(
        'chatbot_markov_chain_advanced_settings_section',
        'Markov Chain Advanced Settings',
        'chatbot_markov_chain_advanced_settings_section_callback',
        'chatbot_markov_chain_advanced_settings'
    );

    add_settings_field(
        'chatbot_markov_chain_build_schedule',
        'Markov Chain Build Schedule',
        'chatbot_markov_chain_build_schedule_callback',
        'chatbot_markov_chain_advanced_settings',
        'chatbot_markov_chain_advanced_settings_section'
    );

    add_settings_field(
        'chatbot_markov_chain_length',
        'Markov Chain Length',
        'chatbot_markov_chain_length_callback',
        'chatbot_markov_chain_advanced_settings',
        'chatbot_markov_chain_advanced_settings_section'
    );

    add_settings_field(
        'chatbot_markov_chain_next_phrase_length',
        'Markov Chain Length Next Phase Length',
        'chatbot_markov_chain_next_phrase_length_callback',
        'chatbot_markov_chain_advanced_settings',
        'chatbot_markov_chain_advanced_settings_section'
    );

}
add_action('admin_init', 'chatbot_markov_chain_api_settings_init');