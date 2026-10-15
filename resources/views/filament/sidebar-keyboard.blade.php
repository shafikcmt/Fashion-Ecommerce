<div x-data x-on:keydown.escape.window="
    if (window.matchMedia('(max-width: 1023px)').matches && $store.sidebar.isOpen && !document.querySelector('.fi-modal-open')) {
        $store.sidebar.close();
        $nextTick(() => document.querySelector('.fi-topbar-open-sidebar-btn')?.focus());
        $event.preventDefault();
    }
"></div>
