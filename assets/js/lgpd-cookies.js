jQuery(document).ready(function($) {
    const $modal = $('#lgpd-cookies-modal');
    const $modalBody = $('.lgpd-modal-body');
    const $rejectBtn = $('#lgpd-reject');
    const $prefBtn = $('#lgpd-preferences');
    const $acceptBtn = $('#lgpd-accept');
    const $icon = $('#lgpd-cookies-icon');

    const cookieCategories = {
        marketing: ['_fbp', '_gads'],
        analytics: ['_ga', '_gid', '_gat','pys_'],
        functional: ['wordpress_', 'wp-settings-','PHPSESSID'],
        uncategorized: []
    };

    // Aplicar estilos personalizados
    function applyCustomStyles() {
        // Modal
        $('.lgpd-modal-content').css({
            'background-color': lgpdSettings.bg_color
        });

        // Cabeçalho
        $('.lgpd-modal-header h2').css({
            'color': lgpdSettings.title_color
        });
        $('.lgpd-modal-header p').css({
            'color': lgpdSettings.text_color
        });

        // Corpo
        $('.lgpd-modal-body').css({
            'color': lgpdSettings.text_color
        });
        $('.cookie-list').css({
            'color': lgpdSettings.text_color // Pode ajustar para uma variação mais clara/escura se quiser
        });

        // Botões
        $('.lgpd-button').css({
            'background-color': lgpdSettings.btn_bg_color,
            'color': lgpdSettings.btn_text_color
        }).hover(
            function() { 
                $(this).css({
                    'background-color': lgpdSettings.btn_hover_bg,
                    'color': lgpdSettings.btn_hover_text
                });
            },
            function() { 
                $(this).css({
                    'background-color': lgpdSettings.btn_bg_color,
                    'color': lgpdSettings.btn_text_color
                });
            }
        );

        // Ícone flutuante
        $('.lgpd-icon').css({
            'background-color': lgpdSettings.bg_color,
            'color': lgpdSettings.text_color
        });
    }

    // Detectar e listar cookies
    function listCookies() {
        const allCookies = document.cookie.split(';').map(cookie => cookie.trim().split('=')[0]);
        $('.cookie-list').empty();
        
        Object.keys(cookieCategories).forEach(category => {
            const $list = $(`.cookie-list[data-category="${category}"]`);
            let foundCookies = [];
            
            allCookies.forEach(cookie => {
                if (cookieCategories[category].some(known => cookie.startsWith(known))) {
                    foundCookies.push(cookie);
                } else if (category === 'uncategorized' && !Object.values(cookieCategories).flat().some(known => cookie.startsWith(known))) {
                    foundCookies.push(cookie);
                }
            });
            
            foundCookies.forEach(cookie => {
                $list.append(`<li>${cookie}</li>`);
            });
        });
    }

    // Gerenciar cookies e scripts
    function manageCookies(preferences) {
        localStorage.setItem('lgpd_cookies', JSON.stringify(preferences));
        const allCookies = document.cookie.split(';');
        allCookies.forEach(cookie => {
            const name = cookie.trim().split('=')[0];
            if (!preferences.marketing && cookieCategories.marketing.some(c => name.startsWith(c))) {
                deleteCookie(name);
            }
            if (!preferences.analytics && cookieCategories.analytics.some(c => name.startsWith(c))) {
                deleteCookie(name);
            }
            if (!preferences.uncategorized && cookieCategories.uncategorized.some(c => name.startsWith(c))) {
                deleteCookie(name);
            }
        });

        $('script[type="text/plain"]').each(function() {
            const category = $(this).data('cookie-category');
            if (preferences[category]) {
                const script = document.createElement('script');
                script.text = $(this).text();
                if ($(this).attr('src')) script.src = $(this).attr('src');
                document.head.appendChild(script);
                $(this).remove();
            }
        });

        // Salvar no histórico via AJAX
        $.ajax({
            url: lgpdSettings.ajax_url,
            method: 'POST',
            data: {
                action: 'lgpd_save_history',
                nonce: lgpdSettings.nonce,
                cookies_accepted: JSON.stringify(preferences)
            },
            success: function(response) {
                console.log('Histórico salvo com sucesso');
            },
            error: function() {
                console.log('Erro ao salvar histórico');
            }
        });        
    }



    function deleteCookie(name) {
        document.cookie = `${name}=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;`;
    }

    // Carregar preferências iniciais
    const savedPreferences = localStorage.getItem('lgpd_cookies');
    if (savedPreferences) {
        manageCookies(JSON.parse(savedPreferences));
    } else {
        $modal.css({display:'flex'}).addClass('visible');
    }

    applyCustomStyles();
    listCookies();

    $rejectBtn.click(function() {
        const preferences = { marketing: false, uncategorized: false, analytics: false, functional: true };
        manageCookies(preferences);
        $modal.removeClass('visible');
        setTimeout(() => $modal.hide(), 300);
    });

    $acceptBtn.click(function() {
        const preferences = { marketing: true, uncategorized: true, analytics: true, functional: true };
        if ($acceptBtn.text() === 'Salvar a configuração') {
            preferences.marketing = $('#marketing-cookies').is(':checked');
            preferences.uncategorized = $('#uncategorized-cookies').is(':checked');
            preferences.analytics = $('#analytics-cookies').is(':checked');
        }
        manageCookies(preferences);
        $modal.removeClass('visible');
        setTimeout(() => $modal.hide(), 300);
    });
    $prefBtn.click(function() {
        $modalBody.slideDown();
        $prefBtn.hide();
        $acceptBtn.text('Salvar a configuração');
        $rejectBtn.show();
        const preferences = JSON.parse(localStorage.getItem('lgpd_cookies')) || {};
        $('#marketing-cookies').prop('checked', preferences.marketing || false);
        $('#uncategorized-cookies').prop('checked', preferences.uncategorized || false);
        $('#analytics-cookies').prop('checked', preferences.analytics || false);
        listCookies();
    });

    $icon.click(function() {
        $modal.css({display:'flex'}).addClass('visible');
        $modalBody.hide();
        $prefBtn.show();
        $acceptBtn.text('Aceitar todos os Cookies');
        $rejectBtn.show();
    });


    $('#lgpd-clear-history').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('Tem certeza que deseja limpar todo o histórico de aceites? Esta ação não pode ser desfeita.')) {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'lgpd_clear_history',
                    nonce: '<?php echo wp_create_nonce('lgpd_clear_history_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Erro ao processar a solicitação.');
                }
            });
        }
    });
    
    
});