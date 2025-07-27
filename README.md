# UK Category Color

WordPressのカテゴリーとカスタムタクソノミーのタームに個別の背景色を設定できるプラグインです。

## 機能

- すべてのパブリックタクソノミー（カテゴリー、タグ、カスタムタクソノミー）のタームに背景色を設定
- 直感的なカラーピッカーによる色選択
- リアルタイムプレビュー機能
- 検索機能でタームを素早く見つける
- ショートコードによる柔軟な表示
- レスポンシブデザイン対応
- アクセシビリティ配慮

## インストール

1. プラグインファイルを `/wp-content/plugins/uk-category-color/` ディレクトリにアップロード
2. WordPress管理画面の「プラグイン」メニューでプラグインを有効化
3. 「設定」→「Category Color」から色設定を行う

## 使用方法

### 基本設定

1. WordPress管理画面の「設定」→「Category Color」にアクセス
2. 各タクソノミーのタームに対して個別に背景色を設定
3. カラーピッカーで色を選択またはカラーコードを直接入力
4. 「リンクあり」チェックボックスでリンクの有無を設定（デフォルト：リンクあり）
5. 「設定を保存」ボタンで保存

### ショートコード

#### 単一タクソノミーの表示

```
[uk_category_list taxonomy="category"]
```

##### パラメータ
- `taxonomy`: 表示するタクソノミー（デフォルト: "category"）
- `hide_empty`: 投稿数0のタームを非表示（"true" または "false"、デフォルト: "false"）
- `orderby`: 並び順の基準（"name", "count", "term_id"など、デフォルト: "name"）
- `order`: 昇順/降順（"ASC" または "DESC"、デフォルト: "ASC"）
- `force_link`: リンク設定を強制（"true", "false", または設定に従う場合は省略）

##### 使用例
```
[uk_category_list taxonomy="product_category" hide_empty="true" orderby="count" order="DESC" force_link="false"]
```

#### 複数タクソノミーの表示

```
[uk_taxonomy_list taxonomies="category,post_tag"]
```

##### パラメータ
- `taxonomies`: 表示するタクソノミーをカンマ区切りで指定（デフォルト: "category,post_tag"）
- `hide_empty`: 投稿数0のタームを非表示（デフォルト: "false"）
- `show_taxonomy_name`: タクソノミー名を表示（デフォルト: "true"）
- `force_link`: リンク設定を強制（"true", "false", または設定に従う場合は省略）

##### 使用例
```
[uk_taxonomy_list taxonomies="category,product_category,post_tag" hide_empty="true" show_taxonomy_name="false" force_link="true"]
```

### PHP関数

#### タームの背景色を取得

```php
$color = uk_get_term_color($term_id);
```

#### 色付きタームリンクを生成

```php
echo uk_get_colored_term_link($term_id, 'カスタムテキスト', true);
```

**パラメータ:**
- `$term_id`: タームID（必須）
- `$text`: 表示テキスト（省略時はタームの名前）
- `$force_link`: リンクを強制（true/false、省略時は設定に従う）

### テーマでの使用例

#### 投稿のカテゴリーを色付きで表示

```php
$categories = get_the_category();
if ($categories) {
    echo '<div class="post-categories">';
    foreach ($categories as $category) {
        echo uk_get_colored_term_link($category->term_id);
    }
    echo '</div>';
}
```

#### カスタムタクソノミーのタームを表示

```php
$terms = get_the_terms(get_the_ID(), 'product_category');
if ($terms && !is_wp_error($terms)) {
    echo '<div class="product-categories">';
    foreach ($terms as $term) {
        echo uk_get_colored_term_link($term->term_id);
    }
    echo '</div>';
}
```

## CSSクラス

プラグインは以下の基本クラスを出力します：

背景色はインラインスタイルで直接適用されるため、CSSクラスは出力されません。
リンク設定により、`<a>`タグまたは`<span>`タグが出力されます。

### カスタムスタイリング例

```css
/* ショートコードで出力されるコンテナ */
.uk-category-list a,
.uk-category-list span,
.uk-taxonomy-list a,
.uk-taxonomy-list span,
.uk-terms-group a,
.uk-terms-group span {
    display: inline-block;
    padding: 4px 8px;
    margin: 2px;
    border-radius: 3px;
    text-decoration: none;
    color: #fff;
}

/* リンクのホバー効果 */
.uk-category-list a:hover,
.uk-taxonomy-list a:hover,
.uk-terms-group a:hover {
    opacity: 0.8;
}

/* リンクなしの要素（span）のスタイル */
.uk-category-list span,
.uk-taxonomy-list span,
.uk-terms-group span {
    cursor: default;
}
```

## 対応タクソノミー

- カテゴリー（category）
- タグ（post_tag）
- カスタムタクソノミー（public => true で登録されたもの）

**除外されるタクソノミー:**
- post_format（投稿フォーマット）- 投稿の形式を表すため、カテゴリー色設定には適さないため除外されます

## 要件

- WordPress 5.0以上
- PHP 7.4以上

## ライセンス

GPL v2

## サポート

このプラグインに関する質問や問題がある場合は、GitHubのIssueで報告してください。

## 更新履歴

### 1.0.0
- 初回リリース
- 基本的な色設定機能
- ショートコード機能