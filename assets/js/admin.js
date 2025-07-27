jQuery(document).ready(function ($) {
    'use strict';

    console.log('UK Category Color admin script loaded');
    console.log('ukCategoryColor object:', ukCategoryColor);

    // 状態管理用のヘルパー関数
    function showSavingState(message) {
        hideAllNotifications();

        const notification = $('<div class="uk-saving-notification saving">' +
            '<span class="spinner"></span>' + message + '</div>');
        $('body').append(notification);

        setTimeout(function () {
            notification.addClass('show');
        }, 10);

        // 保存ボタンを無効化
        $('.uk-save-colors').prop('disabled', true).addClass('saving');
    }

    function showSuccessState(message, duration) {
        hideAllNotifications();

        const notification = $('<div class="uk-saving-notification success">' +
            '<span class="checkmark">✓</span>' + message + '</div>');
        $('body').append(notification);

        setTimeout(function () {
            notification.addClass('show');
        }, 10);

        // ボタンを有効化
        $('.uk-save-colors').prop('disabled', false).removeClass('saving');

        if (duration) {
            setTimeout(function () {
                notification.removeClass('show');
                setTimeout(function () {
                    notification.remove();
                }, 300);
            }, duration);
        }
    }

    function showErrorState(message) {
        hideAllNotifications();

        const notification = $('<div class="uk-saving-notification error">' +
            '<span class="error-mark">✗</span>' + message + '</div>');
        $('body').append(notification);

        setTimeout(function () {
            notification.addClass('show');
        }, 10);

        // ボタンを有効化
        $('.uk-save-colors').prop('disabled', false).removeClass('saving');

        // 5秒後に自動で消す
        setTimeout(function () {
            notification.removeClass('show');
            setTimeout(function () {
                notification.remove();
            }, 300);
        }, 5000);
    }

    function hideAllNotifications() {
        $('.uk-saving-notification').removeClass('show');
        setTimeout(function () {
            $('.uk-saving-notification').remove();
        }, 300);
    }

    // Color Picker初期化
    $('.uk-color-picker').wpColorPicker({
        defaultColor: false,
        change: function (event, ui) {
            var $input = $(this);
            var color = ui.color.toString();
            updateColorPreview($input, color);
        },
        clear: function () {
            var $input = $(this);
            updateColorPreview($input, '');
        }
    });

    // プレビュー更新関数
    function updateColorPreview($input, color) {
        var termId = $input.data('term-id');
        var $preview = $('.uk-color-preview[data-term-id="' + termId + '"]');
        var $termName = $('.uk-term-name[data-term-id="' + termId + '"]');

        if ($preview.length && $termName.length) {
            if (color && color !== '') {
                $preview.css('background-color', color);
                $termName.css('background-color', color);
            } else {
                $preview.css('background-color', '');
                $termName.css('background-color', '');
            }
        }
    }

    // フォーム送信の処理
    $('form.uk-color-form').on('submit', function (e) {
        e.preventDefault();
        console.log('Form submission triggered');
        console.log('Form element:', this);
        console.log('Found forms:', $('form.uk-color-form').length);

        // Ajax URLの存在チェック
        if (typeof ukCategoryColor === 'undefined' || !ukCategoryColor.ajax_url) {
            console.error('ukCategoryColor.ajax_url is not defined');
            showErrorState('Ajax URLが定義されていません');
            return;
        }

        console.log('Ajax URL:', ukCategoryColor.ajax_url);

        // 保存中状態を表示
        showSavingState('設定を保存中...');

        var termColors = {};
        var termLinks = {};

        // 色の設定を収集
        $('.uk-color-picker').each(function () {
            var termId = $(this).data('term-id');
            var color = $(this).val();
            termColors[termId] = color || '';
        });

        // リンクの設定を収集
        $('input[name^="term_links"]').each(function () {
            var name = $(this).attr('name');
            var termId = name.match(/\[(\d+)\]/)[1];
            var isChecked = $(this).is(':checked');
            termLinks[termId] = isChecked ? '1' : '0';
            console.log('Term ID:', termId, 'Link checked:', isChecked, 'Value:', termLinks[termId]);
        });

        console.log('Term colors:', termColors);
        console.log('Term links:', termLinks);

        // Ajax送信
        $.ajax({
            url: ukCategoryColor.ajax_url,
            type: 'POST',
            data: {
                action: 'save_term_colors',
                _wpnonce: $('input[name="_wpnonce"]').val(),
                term_colors: termColors,
                term_links: termLinks
            },
            success: function (response) {
                console.log('Ajax success response:', response);
                if (response.success) {
                    showSuccessState('保存されました。ページを更新します...', 2000);

                    // 2.5秒後にページを更新（保存完了パラメータ付き）
                    setTimeout(function () {
                        $('body').fadeOut(300, function () {
                            window.location.href = window.location.href.split('?')[0] + '?page=uk-category-color&saved=1';
                        });
                    }, 2500);
                } else {
                    showErrorState('保存に失敗しました: ' + (response.data || 'Unknown error'));
                }
            },
            error: function (xhr, status, error) {
                console.error('Save Ajax error:', xhr, status, error);
                console.error('Response text:', xhr.responseText);
                showErrorState('通信エラーが発生しました: ' + error);
            }
        });
    });

    // 全色リセットボタンの処理
    $(document).on('click', '.reset-colors', function (e) {
        e.preventDefault();

        if (confirm('すべての色設定をクリア（色なし）にリセットしますか？\nこの操作は保存後にページが更新されます。')) {

            // リセット中状態を表示
            showSavingState('すべての色をリセット中...');

            // フォームデータの準備（すべて空にリセット）
            var termColors = {};
            var termLinks = {};
            $('.uk-color-picker').each(function () {
                var termId = $(this).data('term-id');
                termColors[termId] = '';

                // 即座にプレビューを更新
                var $input = $(this);
                $input.val('');
                updateColorPreview($input, '');
                if ($input.hasClass('wp-color-picker')) {
                    $input.wpColorPicker('color', '');
                }
            });

            // リンク設定も収集
            $('input[name^="term_links"]').each(function () {
                var name = $(this).attr('name');
                var termId = name.match(/\[(\d+)\]/)[1];
                var isChecked = $(this).is(':checked');
                termLinks[termId] = isChecked ? '1' : '0';
                console.log('Reset - Term ID:', termId, 'Link checked:', isChecked, 'Value:', termLinks[termId]);
            });

            // Ajax送信
            $.ajax({
                url: ukCategoryColor.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_term_colors',
                    _wpnonce: $('input[name="_wpnonce"]').val(),
                    term_colors: termColors,
                    term_links: termLinks
                },
                success: function (response) {
                    if (response.success) {
                        showSuccessState('すべての色設定をクリアしました。ページを更新します...', 2000);

                        // 2.5秒後にページを更新（リセット完了パラメータ付き）
                        setTimeout(function () {
                            $('body').fadeOut(300, function () {
                                window.location.href = window.location.href.split('?')[0] + '?page=uk-category-color&reset=1';
                            });
                        }, 2500);
                    } else {
                        showErrorState('リセットに失敗しました: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Reset Ajax error:', xhr, status, error);
                    showErrorState('通信エラーが発生しました');
                }
            });
        }
    });

    // 保存ボタンのクリックイベントも直接監視
    $(document).on('click', '.uk-save-colors', function (e) {
        console.log('Save button clicked directly');
        e.preventDefault();
        $('form.uk-color-form').trigger('submit');
    });

    // 個別リセットボタンの処理
    $(document).on('click', '.uk-reset-color', function (e) {
        e.preventDefault();

        var $button = $(this);
        var termId = $button.data('term-id');
        var $input = $('.uk-color-picker[data-term-id="' + termId + '"]');

        if (confirm('この色設定をリセットしますか？リセット後に自動保存されます。')) {
            // リセット中状態を表示
            showSavingState('色設定をリセット中...');

            // 即座にプレビューを更新
            $input.val('');
            updateColorPreview($input, '');
            if ($input.hasClass('wp-color-picker')) {
                $input.wpColorPicker('color', '');
            }

            // すべての設定を収集して保存
            var termColors = {};
            var termLinks = {};

            // 色の設定を収集
            $('.uk-color-picker').each(function () {
                var currentTermId = $(this).data('term-id');
                var color = $(this).val();
                termColors[currentTermId] = color || '';
            });

            // リンクの設定を収集
            $('input[name^="term_links"]').each(function () {
                var name = $(this).attr('name');
                var currentTermId = name.match(/\[(\d+)\]/)[1];
                var isChecked = $(this).is(':checked');
                termLinks[currentTermId] = isChecked ? '1' : '0';
            });

            // Ajax送信で保存
            $.ajax({
                url: ukCategoryColor.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_term_colors',
                    _wpnonce: $('input[name="_wpnonce"]').val(),
                    term_colors: termColors,
                    term_links: termLinks
                },
                success: function (response) {
                    if (response.success) {
                        showSuccessState('色設定をリセットしました。ページを更新します...', 2000);

                        // 2.5秒後にページを更新（保存完了パラメータ付き）
                        setTimeout(function () {
                            $('body').fadeOut(300, function () {
                                window.location.href = window.location.href.split('?')[0] + '?page=uk-category-color&saved=1';
                            });
                        }, 2500);
                    } else {
                        showErrorState('リセット保存に失敗しました: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Reset save Ajax error:', xhr, status, error);
                    showErrorState('通信エラーが発生しました: ' + error);
                }
            });
        }
    });

    // ページ読み込み時に全てのプレビューを初期化
    function initializeAllPreviews() {
        $('.uk-color-picker').each(function () {
            var $input = $(this);
            var color = $input.val() || '';
            updateColorPreview($input, color);
        });
    }

    // 初期化実行
    initializeAllPreviews();

    // DOM要素の存在確認
    console.log('Forms found:', $('form.uk-color-form').length);
    console.log('Save buttons found:', $('.uk-save-colors').length);
    console.log('Color pickers found:', $('.uk-color-picker').length);

    // カラーピッカーが作成された後にも実行
    setTimeout(initializeAllPreviews, 500);
});