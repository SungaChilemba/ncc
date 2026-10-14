import Alpine from 'alpinejs';

// Expose Alpine for the inline x-data expressions used across Blade views.
window.Alpine = Alpine;

Alpine.start();

// Subtle scroll-reveal for elements marked with .reveal.
// Guarded so content is fully visible when JS or IntersectionObserver is unavailable.
if ('IntersectionObserver' in window) {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const revealElements = document.querySelectorAll('.reveal');

    if (prefersReducedMotion) {
        revealElements.forEach((element) => element.classList.add('is-visible'));
    } else {
        document.documentElement.classList.add('reveal-ready');

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            },
            { threshold: 0.1 }
        );

        revealElements.forEach((element) => observer.observe(element));
    }
}
