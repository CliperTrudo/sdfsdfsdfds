(function () {
    const initTabs = function () {
        const tabButtons = document.querySelectorAll('.tb-tabs-nav [data-tab]');
        const tabPanels = document.querySelectorAll('.tb-tab-panel');

        if (!tabButtons.length || !tabPanels.length) {
            return;
        }

        const updateHiddenFields = function (tab) {
            document.querySelectorAll('form input[name="active_tab"]').forEach(function (input) {
                input.value = tab;
            });
        };

        const setActiveTab = function (tab) {
            tabButtons.forEach(function (button) {
                const isActive = button.getAttribute('data-tab') === tab;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                button.setAttribute('tabindex', isActive ? '0' : '-1');
            });

            tabPanels.forEach(function (panel) {
                const isActive = panel.getAttribute('data-tab') === tab;
                panel.classList.toggle('is-active', isActive);
                panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            updateHiddenFields(tab);
        };

        const getCurrentTab = function () {
            const activeButton = Array.prototype.find.call(tabButtons, function (button) {
                return button.classList.contains('is-active');
            });

            if (activeButton) {
                return activeButton.getAttribute('data-tab');
            }

            return tabButtons[0] ? tabButtons[0].getAttribute('data-tab') : '';
        };

        tabButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                const tab = button.getAttribute('data-tab');
                if (tab) {
                    setActiveTab(tab);
                }
            });

            button.addEventListener('keydown', function (event) {
                if (event.key === ' ' || event.key === 'Spacebar' || event.key === 'Enter') {
                    event.preventDefault();
                    button.click();
                }
            });
        });

        const initialTab = getCurrentTab();
        if (initialTab) {
            setActiveTab(initialTab);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTabs);
    } else {
        initTabs();
    }
})();
