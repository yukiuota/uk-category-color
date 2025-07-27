<?php
/**
 * Plugin Name: UK Category Color
 * Description: カテゴリーとタクソノミーのタームに個別の背景色を設定できるプラグインです。
 * Version: 1.0.0
 * Author: Y.U.
 * License: GPL v2
 * Text Domain: uk-category-color
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数定義
define('UK_CATEGORY_COLOR_VERSION', '1.0.0');
define('UK_CATEGORY_COLOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UK_CATEGORY_COLOR_PLUGIN_PATH', plugin_dir_path(__FILE__));

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
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // 管理画面の初期化
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            add_action('wp_ajax_save_term_colors', array($this, 'save_term_colors'));
            add_action('admin_notices', array($this, 'show_admin_notices'));
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
     * 対応タクソノミーの取得
     */
    public function get_supported_taxonomies() {
        $taxonomies = get_taxonomies(array('public' => true), 'names');
        // post_formatを除外
        unset($taxonomies['post_format']);
        return $taxonomies;
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
            
            <form id="uk-category-color-form" class="uk-color-form" method="post">
                <?php wp_nonce_field('uk_category_color_nonce'); ?>
                
                <?php foreach ($taxonomies as $taxonomy): ?>
                    <?php
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
     * 設定の保存
     */
    public function save_term_colors() {
        check_ajax_referer('uk_category_color_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません');
        }
        
        $term_colors = isset($_POST['term_colors']) ? $_POST['term_colors'] : array();
        $term_links = isset($_POST['term_links']) ? $_POST['term_links'] : array();
        
        // デバッグ情報をログに出力
        error_log('UK Category Color - Term Colors: ' . print_r($term_colors, true));
        error_log('UK Category Color - Term Links: ' . print_r($term_links, true));
        
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
        
        if (empty($taxonomies)) {
            return '';
        }
        
        $output = '<div class="uk-taxonomy-list">';
        
        foreach ($taxonomies as $taxonomy) {
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

// プラグインの初期化
new UK_Category_Color();
