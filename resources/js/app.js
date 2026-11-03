import Alpine from 'alpinejs';
import { Chart } from 'chart.js/auto';

// Expose Alpine and Chart.js for the inline x-data expressions used across Blade views.
window.Alpine = Alpine;
window.Chart = Chart;

Alpine.data('donationAllocationChart', {
    chart: null,
    init() {
        this.$nextTick(() => {
            const canvas = this.$refs?.donationChartCanvas;

            if (!canvas) {
                return;
            }

            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }

            this.chart = new Chart(canvas, {
                type: 'pie',
                data: {
                    labels: this.labels ?? [],
                    datasets: [
                        {
                            label: this.datasets?.[0]?.label ?? 'Donation allocation',
                            data: this.datasets?.[0]?.data ?? [],
                            backgroundColor: this.datasets?.[0]?.backgroundColor ?? [],
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true },
                    },
                },
            });
        });
    },
});

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
