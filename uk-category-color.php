<?php
/**
 * Plugin Name: UK Category Color
 * Plugin URI: https://github.com/yukiuota/uk-category-color
 * Description: カテゴリーとタクソノミーのタームに個別の背景色を設定できるプラグインです。
 * Version: 1.2.0
 * Author: Yuki Uota
 * Author URI: https://github.com/yukiuota
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: uk-category-color
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * UK Category Color is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * UK Category Color is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with UK Category Color. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

// セキュリティチェック：直接アクセスを防止
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// プラグインの定数定義
define('UK_CATEGORY_COLOR_VERSION', '1.2.0');
define('UK_CATEGORY_COLOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UK_CATEGORY_COLOR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('UK_CATEGORY_COLOR_PLUGIN_FILE', __FILE__);
define('UK_CATEGORY_COLOR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// プラグインのアクティベーション・デアクティベーションフック
register_activation_hook(__FILE__, 'uk_category_color_activate');
register_deactivation_hook(__FILE__, 'uk_category_color_deactivate');

/**
 * プラグインアクティベーション時の処理
 */
function uk_category_color_activate() {
    // 必要なデータベーステーブルの作成や初期設定
    // 現在は特に処理なし
}

/**
 * プラグインデアクティベーション時の処理
 */
function uk_category_color_deactivate() {
    // 一時的なデータのクリーンアップ
    // 設定データは残しておく（アンインストール時に削除）
}

/**
 * 色付きタームリンクを生成する関数
 */
function uk_get_colored_term_link($term_id, $text = null, $force_link = null) {
    $term = get_term($term_id);
    
    if (is_wp_error($term) || !$term) {
        return '';
    }
    
    $color = get_option('uk_term_color_' . $term_id);
    $display_text = $text ? $text : $term->name;
    
    // リンク設定を取得（force_linkが指定されていない場合）
    $use_link = $force_link !== null ? $force_link : get_option('uk_term_link_' . $term_id, '1');
    
    $style = '';
    if ($color) {
        $style = ' style="background-color: ' . esc_attr($color) . ';"';
    }
    
    // リンクありの場合
    if ($use_link) {
        $term_link = get_term_link($term);
        
        if (is_wp_error($term_link)) {
            return sprintf('<span%s>%s</span>', $style, esc_html($display_text));
        }
        
        return sprintf(
            '<a href="%s"%s>%s</a>',
            esc_url($term_link),
            $style,
            esc_html($display_text)
        );
    } else {
        // リンクなしの場合
        return sprintf('<span%s>%s</span>', $style, esc_html($display_text));
    }
}

/**
 * タームの背景色を取得する関数
 */
function uk_get_term_color($term_id) {
    return get_option('uk_term_color_' . $term_id, '');
}

/**
 * メインクラス
 */
class UK_Category_Color {
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * 国際化対応
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'uk-category-color',
            false,
            dirname(UK_CATEGORY_COLOR_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    public function init() {
        // 管理画面の初期化
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            add_action('wp_ajax_save_term_colors', array($this, 'save_term_colors'));
            add_action('admin_notices', array($this, 'show_admin_notices'));
            add_action('admin_init', array($this, 'handle_taxonomy_settings_save'));
        }
        
        // フロントエンドの初期化
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_head', array($this, 'output_custom_css'));
        
        // ショートコードの登録
        add_shortcode('uk_category_list', array($this, 'category_list_shortcode'));
        add_shortcode('uk_taxonomy_list', array($this, 'taxonomy_list_shortcode'));
    }
    
    /**
     * 管理画面メニューの追加
     */
    public function add_admin_menu() {
        add_options_page(
            'Category Color',
            'Category Color',
            'manage_options',
            'uk-category-color',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 管理画面スクリプトの読み込み
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_uk-category-color') {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        wp_enqueue_style(
            'uk-category-color-admin',
            UK_CATEGORY_COLOR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            UK_CATEGORY_COLOR_VERSION
        );
        
        wp_enqueue_script(
            'uk-category-color-admin',
            UK_CATEGORY_COLOR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            UK_CATEGORY_COLOR_VERSION,
            true
        );
        
        wp_localize_script('uk-category-color-admin', 'ukCategoryColor', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('uk_category_color_nonce')
        ));
    }
    
    /**
     * フロントエンドスクリプトの読み込み
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'uk-category-color-frontend',
            UK_CATEGORY_COLOR_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            UK_CATEGORY_COLOR_VERSION
        );
    }
    
    /**
     * カスタムCSSの出力
     */
    public function output_custom_css() {
        // 背景色はインラインスタイルで適用するため、カスタムCSSは出力しない
        // 必要に応じて共通のスタイルのみをここで出力可能
    }
    
    /**
     * タクソノミー設定保存のハンドラー
     */
    public function handle_taxonomy_settings_save() {
        if (isset($_POST['save_taxonomy_settings']) && 
            isset($_GET['page']) && $_GET['page'] === 'uk-category-color') {
            $this->save_taxonomy_settings();
        }
    }
    
    /**
     * 対応タクソノミーの取得
     */
    public function get_supported_taxonomies() {
        $taxonomies = get_taxonomies(array('public' => true), 'names');
        
        // post_formatとpost_tagを除外
        unset($taxonomies['post_format']);
        unset($taxonomies['post_tag']);
        
        // 階層的なタクソノミーのみを対象にする
        $hierarchical_taxonomies = array();
        foreach ($taxonomies as $taxonomy) {
            $taxonomy_obj = get_taxonomy($taxonomy);
            if ($taxonomy_obj && $taxonomy_obj->hierarchical) {
                $hierarchical_taxonomies[] = $taxonomy;
            }
        }
        
        return $hierarchical_taxonomies;
    }
    
    /**
     * 管理画面通知の表示
     */
    public function show_admin_notices() {
        // 自分のページでのみ表示
        if (!isset($_GET['page']) || $_GET['page'] !== 'uk-category-color') {
            return;
        }

        if (isset($_GET['saved']) && $_GET['saved'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>保存されました</p></div>';
        }

        if (isset($_GET['taxonomy_saved']) && $_GET['taxonomy_saved'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>タクソノミー設定を保存しました</p></div>';
        }

        if (isset($_GET['reset']) && $_GET['reset'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>すべての色設定をクリアしました</p></div>';
        }
    }

    /**
     * 管理画面ページの表示
     */
    public function admin_page() {
        $taxonomies = $this->get_supported_taxonomies();
        ?>
        <div class="wrap">
            <h1>Category Color 設定</h1>
            <div id="uk-color-message" class="notice" style="display:none;"></div>
            
            <!-- タクソノミー有効/無効設定 -->
            <form id="uk-taxonomy-settings-form" method="post" style="margin-bottom: 30px;">
                <?php wp_nonce_field('uk_category_color_taxonomy_nonce'); ?>
                <h2>タクソノミー機能設定</h2>
                <p>各タクソノミーでCategory Color機能を使用するかどうかを設定できます。</p>
                
                <div class="uk-taxonomy-settings">
                    <?php foreach ($taxonomies as $taxonomy): ?>
                        <?php
                        $taxonomy_obj = get_taxonomy($taxonomy);
                        $is_enabled = get_option('uk_taxonomy_enabled_' . $taxonomy, '1');
                        ?>
                        <div class="uk-taxonomy-setting-item">
                            <label>
                                <input 
                                    type="checkbox" 
                                    name="taxonomy_enabled[<?php echo esc_attr($taxonomy); ?>]" 
                                    value="1"
                                    <?php checked($is_enabled, '1'); ?>
                                />
                                <?php echo esc_html($taxonomy_obj->label); ?> (<?php echo esc_html($taxonomy); ?>)
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 15px;">
                    <input type="submit" class="button-primary" name="save_taxonomy_settings" value="タクソノミー設定を保存" />
                </div>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <!-- カラー設定フォーム -->
            <form id="uk-category-color-form" class="uk-color-form" method="post">
                <?php wp_nonce_field('uk_category_color_nonce'); ?>
                
                <?php foreach ($taxonomies as $taxonomy): ?>
                    <?php
                    // タクソノミーが有効かチェック
                    $is_taxonomy_enabled = get_option('uk_taxonomy_enabled_' . $taxonomy, '1');
                    if ($is_taxonomy_enabled !== '1') {
                        continue;
                    }
                    
                    $taxonomy_obj = get_taxonomy($taxonomy);
                    $terms = get_terms(array(
                        'taxonomy' => $taxonomy,
                        'hide_empty' => false
                    ));
                    
                    if (is_wp_error($terms) || empty($terms)) {
                        continue;
                    }
                    ?>
                    
                    <div class="uk-taxonomy-section">
                        <h2><?php echo esc_html($taxonomy_obj->label); ?> (<?php echo esc_html($taxonomy); ?>)</h2>
                        
                        <div class="uk-terms-grid">
                            <?php foreach ($terms as $term): ?>
                                <?php
                                $color = get_option('uk_term_color_' . $term->term_id, '');
                                $use_link = get_option('uk_term_link_' . $term->term_id, '1');
                                ?>
                                <div class="uk-term-item" data-term-name="<?php echo esc_attr(strtolower($term->name)); ?>">
                                    <div class="uk-term-info">
                                        <strong><?php echo esc_html($term->name); ?></strong>
                                        <span class="uk-term-meta">
                                            ID: <?php echo $term->term_id; ?> | 
                                            投稿数: <?php echo $term->count; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="uk-color-controls">
                                        <input 
                                            type="text" 
                                            name="term_colors[<?php echo $term->term_id; ?>]" 
                                            value="<?php echo esc_attr($color); ?>" 
                                            class="uk-color-picker"
                                            data-term-id="<?php echo $term->term_id; ?>"
                                        />
                                        <div class="color-preview" 
                                             style="background-color: <?php echo $color ? esc_attr($color) : 'transparent'; ?>">
                                            <?php if (!$color): ?>
                                                <span class="no-color">なし</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="uk-link-setting">
                                            <label>
                                                <input 
                                                    type="checkbox" 
                                                    name="term_links[<?php echo $term->term_id; ?>]" 
                                                    value="1"
                                                    <?php checked($use_link, '1'); ?>
                                                />
                                                リンクあり
                                            </label>
                                        </div>
                                        <button type="button" class="button uk-reset-color" data-term-id="<?php echo $term->term_id; ?>">
                                            リセット
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="uk-form-actions">
                    <input type="submit" class="button-primary uk-save-colors" value="設定を保存" />
                    <button type="button" class="button reset-colors" style="margin-left: 10px;">すべての色をクリア</button>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * タクソノミー設定の保存
     */
    public function save_taxonomy_settings() {
        // nonceチェック
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'uk_category_color_taxonomy_nonce')) {
            wp_die('セキュリティチェックに失敗しました。');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        
        $taxonomy_enabled = isset($_POST['taxonomy_enabled']) ? $_POST['taxonomy_enabled'] : array();
        $taxonomies = $this->get_supported_taxonomies();
        
        // 各タクソノミーの設定を保存
        foreach ($taxonomies as $taxonomy) {
            if (isset($taxonomy_enabled[$taxonomy])) {
                update_option('uk_taxonomy_enabled_' . $taxonomy, '1');
            } else {
                update_option('uk_taxonomy_enabled_' . $taxonomy, '0');
            }
        }
        
        // 保存完了のリダイレクト
        wp_redirect(add_query_arg(array(
            'page' => 'uk-category-color',
            'taxonomy_saved' => '1'
        ), admin_url('options-general.php')));
        exit;
    }
    
    /**
     * 設定の保存
     */
    public function save_term_colors() {
        check_ajax_referer('uk_category_color_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません');
        }
        
        $term_colors = isset($_POST['term_colors']) ? $_POST['term_colors'] : array();
        $term_links = isset($_POST['term_links']) ? $_POST['term_links'] : array();
        
        // デバッグ情報をログに出力（WP_DEBUGが有効な場合のみ）
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('UK Category Color - Term Colors: ' . print_r($term_colors, true));
            error_log('UK Category Color - Term Links: ' . print_r($term_links, true));
        }
        
        // すべてのタームのリンク設定を保存
        $taxonomies = $this->get_supported_taxonomies();
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ));
            
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    // リンク設定の保存（JavaScriptから '1' または '0' で送信される）
                    if (isset($term_links[$term->term_id])) {
                        $link_value = $term_links[$term->term_id] === '1' ? '1' : '0';
                        update_option('uk_term_link_' . $term->term_id, $link_value);
                    } else {
                        // データが送信されていない場合は '0' (リンクなし) にする
                        update_option('uk_term_link_' . $term->term_id, '0');
                    }
                }
            }
        }
        
        // 色設定の保存
        foreach ($term_colors as $term_id => $color) {
            $term_id = intval($term_id);
            $color = sanitize_hex_color($color);
            
            if ($color) {
                update_option('uk_term_color_' . $term_id, $color);
            } else {
                delete_option('uk_term_color_' . $term_id);
            }
        }
        
        wp_send_json_success('設定を保存しました');
    }
    
    /**
     * カテゴリーリスト表示ショートコード
     */
    public function category_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'taxonomy' => 'category',
            'hide_empty' => 'false',
            'orderby' => 'name',
            'order' => 'ASC',
            'force_link' => null // true, false, または null（設定に従う）
        ), $atts);
        
        // タクソノミーが有効かチェック
        $is_enabled = get_option('uk_taxonomy_enabled_' . $atts['taxonomy'], '1');
        if ($is_enabled !== '1') {
            return '';
        }
        
        $terms = get_terms(array(
            'taxonomy' => $atts['taxonomy'],
            'hide_empty' => $atts['hide_empty'] === 'true',
            'orderby' => $atts['orderby'],
            'order' => $atts['order']
        ));
        
        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }
        
        $output = '<div class="uk-category-list">';
        
        foreach ($terms as $term) {
            $color = get_option('uk_term_color_' . $term->term_id);
            $use_link = $atts['force_link'] !== null ? 
                        ($atts['force_link'] === 'true') : 
                        get_option('uk_term_link_' . $term->term_id, '1');
            
            $style = '';
            if ($color) {
                $style = ' style="background-color: ' . esc_attr($color) . ';"';
            }
            
            if ($use_link) {
                $term_link = get_term_link($term);
                
                if (is_wp_error($term_link)) {
                    $output .= sprintf('<span%s>%s</span>', $style, esc_html($term->name));
                } else {
                    $output .= sprintf(
                        '<a href="%s"%s>%s</a>',
                        esc_url($term_link),
                        $style,
                        esc_html($term->name)
                    );
                }
            } else {
                $output .= sprintf('<span%s>%s</span>', $style, esc_html($term->name));
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 複数タクソノミーリスト表示ショートコード
     */
    public function taxonomy_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'taxonomies' => 'category,post_tag',
            'hide_empty' => 'false',
            'show_taxonomy_name' => 'true',
            'force_link' => null // true, false, または null（設定に従う）
        ), $atts);
        
        $taxonomies = array_map('trim', explode(',', $atts['taxonomies']));
        $supported_taxonomies = $this->get_supported_taxonomies();
        $taxonomies = array_intersect($taxonomies, $supported_taxonomies);
        
        // 有効なタクソノミーのみをフィルタリング
        $enabled_taxonomies = array();
        foreach ($taxonomies as $taxonomy) {
            $is_enabled = get_option('uk_taxonomy_enabled_' . $taxonomy, '1');
            if ($is_enabled === '1') {
                $enabled_taxonomies[] = $taxonomy;
            }
        }
        
        if (empty($enabled_taxonomies)) {
            return '';
        }
        
        $output = '<div class="uk-taxonomy-list">';
        
        foreach ($enabled_taxonomies as $taxonomy) {
            $taxonomy_obj = get_taxonomy($taxonomy);
            if (!$taxonomy_obj) {
                continue;
            }
            
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => $atts['hide_empty'] === 'true'
            ));
            
            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }
            
            if ($atts['show_taxonomy_name'] === 'true') {
                $output .= '<h3>' . esc_html($taxonomy_obj->label) . '</h3>';
            }
            
            $output .= '<div class="uk-terms-group">';
            
            foreach ($terms as $term) {
                $color = get_option('uk_term_color_' . $term->term_id);
                $use_link = $atts['force_link'] !== null ? 
                            ($atts['force_link'] === 'true') : 
                            get_option('uk_term_link_' . $term->term_id, '1');
                
                $style = '';
                if ($color) {
                    $style = ' style="background-color: ' . esc_attr($color) . ';"';
                }
                
                if ($use_link) {
                    $term_link = get_term_link($term);
                    
                    if (is_wp_error($term_link)) {
                        $output .= sprintf('<span%s>%s</span>', $style, esc_html($term->name));
                    } else {
                        $output .= sprintf(
                            '<a href="%s"%s>%s</a>',
                            esc_url($term_link),
                            $style,
                            esc_html($term->name)
                        );
                    }
                } else {
                    $output .= sprintf('<span%s>%s</span>', $style, esc_html($term->name));
                }
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
}

/**
 * カテゴリー・ターム編集画面にカラーフィールドを追加するクラス
 */
class UK_Category_Color_Admin_Fields {
    
    public function __construct() {
        // init フックで実行することで、すべてのタクソノミーが登録されてから処理する
        add_action('init', array($this, 'init_taxonomy_hooks'), 20);
        
        // カラーピッカーの読み込み
        add_action('admin_enqueue_scripts', array($this, 'enqueue_color_picker'));
    }
    
    /**
     * init時にカスタムタクソノミーのフックを追加
     */
    public function init_taxonomy_hooks() {
        // 対応タクソノミーを取得
        $taxonomies = get_taxonomies(array('public' => true), 'names');
        
        // デバッグ情報をログに出力（WP_DEBUGが有効な場合のみ）
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('UK Category Color - Available Taxonomies: ' . print_r($taxonomies, true));
        }
        
        foreach ($taxonomies as $taxonomy) {
            // post_formatとpost_tagは除外
            if (in_array($taxonomy, array('post_format', 'post_tag'))) {
                continue;
            }
            
            // 非階層的なタクソノミーは除外（タグ的なものを除外）
            $taxonomy_obj = get_taxonomy($taxonomy);
            if (!$taxonomy_obj || !$taxonomy_obj->hierarchical) {
                continue;
            }
            
            // タクソノミーが有効かチェック
            $is_enabled = get_option('uk_taxonomy_enabled_' . $taxonomy, '1');
            if ($is_enabled !== '1') {
                continue;
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('UK Category Color - Adding hooks for taxonomy: ' . $taxonomy);
            }
            
            add_action($taxonomy . '_add_form_fields', array($this, 'add_color_field'));
            add_action($taxonomy . '_edit_form_fields', array($this, 'edit_color_field'));
            add_action('edited_' . $taxonomy, array($this, 'save_color_field'));
            add_action('create_' . $taxonomy, array($this, 'save_color_field'));
            
            // 一覧画面にも対応
            add_filter('manage_edit-' . $taxonomy . '_columns', array($this, 'add_color_column'));
            add_filter('manage_' . $taxonomy . '_custom_column', array($this, 'display_color_column'), 10, 3);
        }
        
        // より後のタイミングでも確認
        add_action('admin_init', array($this, 'late_taxonomy_hooks'));
    }
    
    /**
     * admin_init時にさらに遅いタイミングでタクソノミーフックを追加
     */
    public function late_taxonomy_hooks() {
        $taxonomies = get_taxonomies(array('public' => true), 'names');
        
        foreach ($taxonomies as $taxonomy) {
            // post_formatとpost_tagは除外
            if (in_array($taxonomy, array('post_format', 'post_tag'))) {
                continue;
            }
            
            // 非階層的なタクソノミーは除外（タグ的なものを除外）
            $taxonomy_obj = get_taxonomy($taxonomy);
            if (!$taxonomy_obj || !$taxonomy_obj->hierarchical) {
                continue;
            }
            
            // タクソノミーが有効かチェック
            $is_enabled = get_option('uk_taxonomy_enabled_' . $taxonomy, '1');
            if ($is_enabled !== '1') {
                continue;
            }
            
            // 既に追加されていない場合のみ追加
            if (!has_action($taxonomy . '_add_form_fields', array($this, 'add_color_field'))) {
                add_action($taxonomy . '_add_form_fields', array($this, 'add_color_field'));
                add_action($taxonomy . '_edit_form_fields', array($this, 'edit_color_field'));
                add_action('edited_' . $taxonomy, array($this, 'save_color_field'));
                add_action('create_' . $taxonomy, array($this, 'save_color_field'));
                
                add_filter('manage_edit-' . $taxonomy . '_columns', array($this, 'add_color_column'));
                add_filter('manage_' . $taxonomy . '_custom_column', array($this, 'display_color_column'), 10, 3);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('UK Category Color - Late adding hooks for taxonomy: ' . $taxonomy);
                }
            }
        }
    }
    
    /**
     * 新規追加画面のカラーフィールド
     */
    public function add_color_field($taxonomy) {
        ?>
        <div class="form-field">
            <label for="uk_category_color"><?php _e('カテゴリーカラー', 'uk-category-color'); ?></label>
            <input type="text" name="uk_category_color" id="uk_category_color" value="" class="uk-color-picker-field" />
            <p class="description"><?php _e('このカテゴリー/タームの表示色を選択してください。', 'uk-category-color'); ?></p>
            <div class="uk-color-preview" style="width: 50px; height: 20px; border: 1px solid #ccc; margin-top: 5px; background-color: transparent; text-align: center; line-height: 20px; font-size: 12px; color: #666;">なし</div>
            <button type="button" class="button uk-reset-color-field" style="margin-top: 5px;"><?php _e('リセット', 'uk-category-color'); ?></button>
        </div>
        <?php
    }
    
    /**
     * 編集画面のカラーフィールド
     */
    public function edit_color_field($term) {
        $color = get_option('uk_term_color_' . $term->term_id, '');
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="uk_category_color"><?php _e('カテゴリーカラー', 'uk-category-color'); ?></label>
            </th>
            <td>
                <input type="text" name="uk_category_color" id="uk_category_color" value="<?php echo esc_attr($color); ?>" class="uk-color-picker-field" />
                <p class="description"><?php _e('このカテゴリー/タームの表示色を選択してください。', 'uk-category-color'); ?></p>
                <?php if ($color): ?>
                    <div class="uk-color-preview" style="width: 50px; height: 20px; border: 1px solid #ccc; margin-top: 5px; background-color: <?php echo esc_attr($color); ?>;"></div>
                <?php else: ?>
                    <div class="uk-color-preview" style="width: 50px; height: 20px; border: 1px solid #ccc; margin-top: 5px; background-color: transparent; text-align: center; line-height: 20px; font-size: 12px; color: #666;">なし</div>
                <?php endif; ?>
                <br>
                <button type="button" class="button uk-reset-color-field" style="margin-top: 5px;"><?php _e('リセット', 'uk-category-color'); ?></button>
            </td>
        </tr>
        <?php
    }
    
    /**
     * カラーフィールドの保存
     */
    public function save_color_field($term_id) {
        // 権限チェック
        if (!current_user_can('manage_categories')) {
            return;
        }
        
        // nonceチェック（フォーム送信時のみ）
        if (isset($_POST['_wpnonce']) && !wp_verify_nonce($_POST['_wpnonce'], 'update-tag_' . $term_id)) {
            return;
        }
        
        if (isset($_POST['uk_category_color'])) {
            $color = sanitize_hex_color($_POST['uk_category_color']);
            if ($color) {
                update_option('uk_term_color_' . intval($term_id), $color);
            } else {
                delete_option('uk_term_color_' . intval($term_id));
            }
        }
    }
    
    /**
     * カラーピッカーのスクリプトを読み込み
     */
    public function enqueue_color_picker($hook_suffix) {
        // タクソノミー編集画面でのみ読み込み
        if (in_array($hook_suffix, array('edit-tags.php', 'term.php'))) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            // カスタムスタイル
            wp_add_inline_style('wp-color-picker', '
                .uk-color-preview {
                    display: inline-block;
                    vertical-align: middle;
                }
            ');
            
            // カラーピッカーの初期化
            wp_add_inline_script('wp-color-picker', '
                jQuery(document).ready(function($) {
                    $(".uk-color-picker-field").wpColorPicker({
                        change: function(event, ui) {
                            var preview = $(this).closest("td, .form-field").find(".uk-color-preview");
                            preview.css("background-color", ui.color.toString());
                            preview.text("");
                        },
                        clear: function() {
                            var preview = $(this).closest("td, .form-field").find(".uk-color-preview");
                            preview.css("background-color", "transparent");
                            preview.text("なし");
                        }
                    });
                    
                    // リセットボタンの処理
                    $(".uk-reset-color-field").on("click", function(e) {
                        e.preventDefault();
                        var container = $(this).closest("td, .form-field");
                        var colorInput = container.find(".uk-color-picker-field");
                        var preview = container.find(".uk-color-preview");
                        
                        // カラーピッカーをクリア
                        colorInput.val("");
                        colorInput.wpColorPicker("color", "");
                        
                        // プレビューを更新
                        preview.css("background-color", "transparent");
                        preview.text("なし");
                    });
                });
            ');
        }
    }
    
    /**
     * 一覧画面にカラー列を追加
     */
    public function add_color_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'name') {
                $new_columns['uk_color'] = __('カラー', 'uk-category-color');
            }
        }
        return $new_columns;
    }
    
    /**
     * カラー列の内容を表示
     */
    public function display_color_column($content, $column_name, $term_id) {
        if ($column_name === 'uk_color') {
            $color = get_option('uk_term_color_' . $term_id);
            
            if ($color) {
                $content = sprintf(
                    '<div style="width: 30px; height: 20px; background-color: %s; border: 1px solid #ccc; display: inline-block; margin-right: 5px;"></div>%s',
                    esc_attr($color),
                    esc_html($color)
                );
            } else {
                $content = '<span style="color: #666;">' . __('なし', 'uk-category-color') . '</span>';
            }
        }
        return $content;
    }
}

/**
 * プラグインの初期化
 */
function uk_category_color_init() {
    new UK_Category_Color();
    new UK_Category_Color_Admin_Fields();
}

// プラグインの初期化を実行
add_action('plugins_loaded', 'uk_category_color_init');
