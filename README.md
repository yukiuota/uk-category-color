# UK Category Color

WordPressのカテゴリーとカスタムタクソノミーのタームに個別の背景色を設定できるプラグインです。

## 機能

### 基本機能
- 階層的なタクソノミー（カテゴリー、階層的なカスタムタクソノミー）のタームに背景色を設定
- タクソノミーごとに機能の有効/無効を個別設定
- 直感的なカラーピッカーによる色選択
- リアルタイムプレビュー機能
- 検索機能でタームを素早く見つける
- ショートコードによる柔軟な表示

### 管理画面での直接編集機能
- カテゴリー・ターム編集画面に直接カラーフィールドを表示
- 管理画面の一覧ページに色プレビュー列を追加
- リセットボタンで設定した色を簡単にクリア
- 色が設定されていない場合は「なし」と表示
- 編集画面でのリアルタイムプレビュー

## インストール

### GitHubからダウンロード
1. このリポジトリをダウンロードまたはクローン
2. `uk-category-color` フォルダを `/wp-content/plugins/` ディレクトリにアップロード
3. WordPress管理画面の「プラグイン」メニューでプラグインを有効化
4. 「設定」→「Category Color」から色設定を行う

## 使用方法

### 管理画面での直接編集

#### カテゴリー・ターム編集画面から設定
1. WordPress管理画面の「投稿」→「カテゴリー」または階層的なカスタムタクソノミーにアクセス
2. 新規追加画面または既存タームの編集画面でカラーフィールドを確認
3. カラーピッカーで色を選択またはカラーコードを直接入力
4. リアルタイムプレビューで設定した色を確認
5. 「リセット」ボタンで色設定をクリア（編集画面のみ）
6. 保存ボタンで変更を確定

#### 管理画面一覧での確認
- カテゴリー・階層的なカスタムタクソノミーの一覧画面に「色」列が追加
- 設定された色がプレビュー表示
- 色が設定されていない場合は「なし」と表示

### 統合設定画面

1. WordPress管理画面の「設定」→「Category Color」にアクセス
2. 「タクソノミー機能設定」で各タクソノミーの機能有効/無効を設定
3. 「タクソノミー設定を保存」ボタンで設定を保存
4. 有効にしたタクソノミーのタームに対して個別に背景色を設定
5. カラーピッカーで色を選択またはカラーコードを直接入力
6. 「リンクあり」チェックボックスでリンクの有無を設定（デフォルト：リンクあり）
7. 「設定を保存」ボタンで保存

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
[uk_taxonomy_list taxonomies="category,product_category"]
```

##### パラメータ
- `taxonomies`: 表示するタクソノミーをカンマ区切りで指定（デフォルト: "category,post_tag"）
- `hide_empty`: 投稿数0のタームを非表示（デフォルト: "false"）
- `show_taxonomy_name`: タクソノミー名を表示（デフォルト: "true"）
- `force_link`: リンク設定を強制（"true", "false", または設定に従う場合は省略）

##### 使用例
```
[uk_taxonomy_list taxonomies="category,product_category" hide_empty="true" show_taxonomy_name="false" force_link="true"]
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
- 階層的なカスタムタクソノミー（hierarchical => true で登録されたもの）

**除外されるタクソノミー:**
- post_format（投稿フォーマット）- 投稿の形式を表すため、カテゴリー色設定には適さないため除外されます
- post_tag（タグ）- 非階層的なタクソノミーのため除外されます
- 非階層的なカスタムタクソノミー（hierarchical => false）- タグ的な用途のため除外されます

## 要件

- WordPress 5.0以上
- PHP 7.4以上


## セキュリティ・パフォーマンス

- WordPress nonce による CSRF 攻撃対策
- データサニタイゼーション・エスケープ処理
- 権限チェックによるアクセス制御
- 必要な画面でのみスクリプト・スタイル読み込み


## ライセンス

GPL v2 or later


## サポート

このプラグインに関する質問や問題がある場合は、以下の方法でお知らせください：

- **GitHub Issues**: [GitHub リポジトリ](https://github.com/yukiuota/uk-category-color/issues)で問題を報告


## 更新履歴

### 1.2.0
- **新機能**: タクソノミーごとに機能の有効/無効を個別設定可能
- **改善**: 階層的なタクソノミー（カテゴリー系）のみを対象に変更
- **改善**: タグ（post_tag）および非階層的なタクソノミーを自動除外
- **改善**: 管理画面UI向上（タクソノミー設定セクション追加）
- **セキュリティ**: 設定保存時のセキュリティ強化

### 1.1.0
- **新機能**: カテゴリー・ターム編集画面に直接カラーフィールドを追加
- **新機能**: 管理画面の一覧ページに色プレビュー列を追加
- **新機能**: カテゴリー・ターム編集画面にリセットボタンを追加
- **改善**: カスタムタクソノミーへの完全対応
- **改善**: タクソノミーごとの色設定がより直感的に操作可能
- **改善**: 色未設定時の「なし」表示対応
- **セキュリティ**: WordPress Plugin Handbook準拠のセキュリティ強化
- **国際化**: 完全な多言語対応とPOTファイル追加
- **パフォーマンス**: コード最適化とWordPress標準準拠

### 1.0.0
- 初回リリース
- 基本的な色設定機能
- ショートコード機能