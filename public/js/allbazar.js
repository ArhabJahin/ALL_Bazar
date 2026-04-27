const siteHeader = document.querySelector('[data-site-header]');
const sidebar = document.querySelector('#site-sidebar');
const sidebarToggles = document.querySelectorAll('[data-sidebar-toggle]');
const sidebarClosers = document.querySelectorAll('[data-sidebar-close]');

const setSidebar = (isOpen) => {
    document.body.classList.toggle('sidebar-open', isOpen);
    sidebar?.setAttribute('aria-hidden', String(!isOpen));
    sidebarToggles.forEach((button) => button.setAttribute('aria-expanded', String(isOpen)));
};

sidebarToggles.forEach((button) => {
    button.addEventListener('click', () => setSidebar(!document.body.classList.contains('sidebar-open')));
});

sidebarClosers.forEach((button) => {
    button.addEventListener('click', () => setSidebar(false));
});

sidebar?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => setSidebar(false));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setSidebar(false);
});

const updateHeaderState = () => {
    siteHeader?.classList.toggle('is-scrolled', window.scrollY > 8);
};

updateHeaderState();
window.addEventListener('scroll', updateHeaderState, { passive: true });

document.querySelectorAll('[data-suggestions]').forEach((input) => {
    const box = document.createElement('datalist');
    box.id = `suggestions-${Math.random().toString(16).slice(2)}`;
    input.setAttribute('list', box.id);
    input.after(box);

    let timer;
    const loadSuggestions = () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            try {
                const response = await fetch(`/search-suggestions?q=${encodeURIComponent(input.value)}`);
                const names = await response.json();
                box.innerHTML = '';
                names.forEach((name) => {
                    const option = document.createElement('option');
                    option.value = name;
                    box.appendChild(option);
                });
            } catch (error) {
                box.innerHTML = '';
            }
        }, 180);
    };

    input.addEventListener('focus', loadSuggestions);
    input.addEventListener('input', loadSuggestions);
});

document.querySelectorAll('.zoom-gallery').forEach((gallery) => {
    const mainArea = gallery.querySelector('[data-product-gallery-main]');
    const mainImage = gallery.querySelector('[data-product-gallery-main] img');
    const zoomPane = gallery.querySelector('[data-product-zoom-pane]');
    const zoomImage = zoomPane?.querySelector('img');
    const thumbs = gallery.querySelectorAll('[data-gallery-thumb]');

    if (!mainArea || !mainImage) return;

    const setImage = (src) => {
        mainImage.src = src;
        if (zoomImage) zoomImage.src = src;
    };

    thumbs.forEach((thumb) => {
        thumb.addEventListener('click', () => {
            setImage(thumb.dataset.imageSrc);
            thumbs.forEach((item) => item.classList.toggle('is-active', item === thumb));
        });
    });

    if (!zoomImage) return;

    mainArea.addEventListener('mouseenter', () => {
        gallery.classList.add('is-zooming');
        zoomPane.setAttribute('aria-hidden', 'false');
    });

    mainArea.addEventListener('mouseleave', () => {
        gallery.classList.remove('is-zooming');
        zoomPane.setAttribute('aria-hidden', 'true');
        zoomImage.style.transformOrigin = 'center center';
    });

    mainArea.addEventListener('mousemove', (event) => {
        const rect = mainArea.getBoundingClientRect();
        const x = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100));
        const y = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100));
        zoomImage.style.transformOrigin = `${x}% ${y}%`;
    });
});

document.querySelectorAll('.cart-item input[type="number"]').forEach((input) => {
    input.addEventListener('input', () => {
        if (Number(input.value) < 1) input.value = 1;
    });
});

document.querySelectorAll('[data-quantity-stepper]').forEach((stepper) => {
    const input = stepper.querySelector('input[type="number"]');
    stepper.querySelectorAll('[data-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const step = Number(button.dataset.step);
            const min = Number(input.min || 1);
            const max = Number(input.max || 9999);
            const next = Math.min(max, Math.max(min, Number(input.value || min) + step));
            input.value = next;
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
});

document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => {
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
            button.dataset.originalText = button.textContent;
            button.textContent = 'Working...';
            button.disabled = true;
        });
    });
});
