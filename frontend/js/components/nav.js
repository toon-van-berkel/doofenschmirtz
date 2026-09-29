const desktopBreakpoint = window.matchMedia('(min-width: 900px)');
const sidebar = document.querySelector('nav');

if (sidebar) {
    const storageKey = 'doofenschmirtz-sidebar';
    const savedState = JSON.parse(localStorage.getItem(storageKey) || '{}');
    const minWidth = 220;
    const maxWidth = 280;
    const collapsedWidth = 76;
    const defaultWidth = 248;

    sidebar.setAttribute('aria-label', 'Main navigation');
    sidebar.insertAdjacentHTML('afterbegin', `
        <button 
            class="sidebar-toggle" 
            type="button" 
            aria-label="Collapse navigation" 
            aria-expanded="true"
        ><span aria-hidden="true">‹</span></button>
        <button 
            class="sidebar-resize" 
            type="button" 
            aria-label="Resize navigation" 
            title="Drag to resize navigation"
        ></button>
    `);

    const toggle = sidebar.querySelector('.sidebar-toggle');
    const resizeHandle = sidebar.querySelector('.sidebar-resize');

    function setWidth(width) {
        const nextWidth = Math.max(minWidth, Math.min(maxWidth, width));
        document.documentElement.style.setProperty('--sidebar-width', `${nextWidth}px`);
        savedState.width = nextWidth;
    }

    function setCollapsed(collapsed) {
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        document.documentElement.style.setProperty('--sidebar-width', 
            `${collapsed ? collapsedWidth : (savedState.width || defaultWidth)}px`);
        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('aria-label', collapsed ? 'Expand navigation' : 'Collapse navigation');
        toggle.classList.toggle('is-collapsed', collapsed);
        toggle.querySelector('span').textContent = '‹';
        savedState.collapsed = collapsed;
        localStorage.setItem(storageKey, JSON.stringify(savedState));
    }

    setWidth(Number(savedState.width) || defaultWidth);
    setCollapsed(Boolean(savedState.collapsed));
    toggle.addEventListener('click', () => setCollapsed(!document.body.classList.contains('sidebar-collapsed')));

    resizeHandle.addEventListener('pointerdown', (event) => {
        if (!desktopBreakpoint.matches || document.body.classList.contains('sidebar-collapsed')) return;
        event.preventDefault();
        resizeHandle.setPointerCapture(event.pointerId);
        document.body.classList.add('sidebar-resizing');

        const move = (moveEvent) => setWidth(moveEvent.clientX);
        const stop = () => {
            document.body.classList.remove('sidebar-resizing');
            localStorage.setItem(storageKey, JSON.stringify(savedState));
            resizeHandle.removeEventListener('pointermove', move);
            resizeHandle.removeEventListener('pointerup', stop);
            resizeHandle.removeEventListener('pointercancel', stop);
        };

        resizeHandle.addEventListener('pointermove', move);
        resizeHandle.addEventListener('pointerup', stop);
        resizeHandle.addEventListener('pointercancel', stop);
    });

    resizeHandle.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        const currentWidth = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width'), 10) || defaultWidth;
        setWidth(currentWidth + (event.key === 'ArrowRight' ? 16 : -16));
        localStorage.setItem(storageKey, JSON.stringify(savedState));
    });
}
