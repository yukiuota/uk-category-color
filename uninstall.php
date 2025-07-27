<?php
/**
 * プラグインアンインストール時の処理
 * 
 * このファイルはプラグインがアンインストールされる際に実行されます。
 * 設定されたタームの色情報をデータベースから削除します。
 */

// セキュリティチェック
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * タームカラー設定の削除
 */
function uk_category_color_uninstall_cleanup() {
    global $wpdb;
    
    // uk_term_color_ で始まるすべてのオプションを削除
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            'uk_term_color_%'
        )
    );
    
    // オプションキャッシュのクリア
    wp_cache_flush();
}

// クリーンアップ実行
uk_category_color_uninstall_cleanup();
