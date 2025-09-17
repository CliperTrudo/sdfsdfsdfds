(function($, window){
    'use strict';

    if (!window) {
        return;
    }

    var noticeContainerClass = 'tb-dynamic-notices';

    function getAdminWrapper() {
        var $wrapper = $('.tb-admin-wrapper').first();
        if (!$wrapper.length) {
            $wrapper = $('#wpbody-content').length ? $('#wpbody-content') : $('body');
        }
        return $wrapper;
    }

    function ensureNoticeContainer() {
        var $wrapper = getAdminWrapper();
        var selector = '.' + noticeContainerClass;
        var $container = $wrapper.children(selector).first();
        if (!$container.length) {
            $container = $('<div>').addClass(noticeContainerClass);
            var $existing = $wrapper.children('.tb-notice:last');
            if ($existing.length) {
                $container.insertAfter($existing);
            } else {
                $wrapper.prepend($container);
            }
        }
        return $container;
    }

    function appendMessage($target, message) {
        if (Array.isArray(message)) {
            message.forEach(function(line, index){
                if (index > 0) {
                    $target.append('<br>');
                }
                $target.append(document.createTextNode(line != null ? String(line) : ''));
            });
            return;
        }
        var text = message == null ? '' : String(message);
        var parts = text.split(/\r?\n/);
        parts.forEach(function(part, idx){
            if (idx > 0) {
                $target.append('<br>');
            }
            $target.append(document.createTextNode(part));
        });
    }

    function createNotice(type, message) {
        var classes = ['notice', 'is-dismissible', 'tb-notice'];
        classes.push(type === 'success' ? 'notice-success' : 'notice-error');
        var $notice = $('<div>').addClass(classes.join(' '));
        var $message = $('<p>');
        appendMessage($message, message);
        var $button = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>');
        $button.find('.screen-reader-text').text('Cerrar este aviso.');
        $notice.append($message).append($button);
        $notice.on('click', '.notice-dismiss', function(){
            $notice.fadeOut(200, function(){
                $notice.remove();
            });
        });
        return $notice;
    }

    function addNotice(type, message) {
        var text = message;
        if (text == null) {
            return;
        }
        if (Array.isArray(text) && !text.length) {
            return;
        }
        if (!Array.isArray(text)) {
            text = String(text);
            if (!text.trim()) {
                return;
            }
        }
        var $container = ensureNoticeContainer();
        var $notice = createNotice(type, message);
        $container.append($notice);
        return $notice;
    }

    function clearNotices() {
        var $container = $('.' + noticeContainerClass);
        if ($container.length) {
            $container.empty();
        }
    }

    window.tbAdminNotices = {
        showSuccess: function(message){
            return addNotice('success', message);
        },
        showError: function(message){
            return addNotice('error', message);
        },
        clear: function(){
            clearNotices();
        }
    };

})(jQuery, window);
