/**
 * Layup Alpine.js Components
 *
 * Register all interactive widget components with Alpine.
 * Auto-included via @layupScripts unless disabled in config.
 *
 * To bundle manually instead:
 *   import '../../vendor/crumbls/layup/resources/js/layup.js'
 */

document.addEventListener("alpine:init", () => {
    Alpine.data("layupAccordion", (openFirst = true) => ({
        active: openFirst ? 0 : null,
        toggle(index) {
            this.active = this.active === index ? null : index;
        },
        isOpen(index) {
            return this.active === index;
        },
    }));

    Alpine.data("layupToggle", (open = false) => ({
        open,
        toggle() {
            this.open = !this.open;
        },
    }));

    Alpine.data("layupTabs", () => ({
        active: 0,
        select(index) {
            this.active = index;
        },
        isActive(index) {
            return this.active === index;
        },
    }));

    Alpine.data("layupCountdown", (targetDate) => ({
        target: new Date(targetDate).getTime(),
        days: 0,
        hours: 0,
        minutes: 0,
        seconds: 0,
        expired: false,
        timer: null,
        start() {
            if (!this.target || isNaN(this.target)) {
                this.expired = true;
                return;
            }
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },
        tick() {
            const diff = this.target - Date.now();
            if (diff <= 0) {
                this.expired = true;
                this.days = this.hours = this.minutes = this.seconds = 0;
                clearInterval(this.timer);
                return;
            }
            this.days = Math.floor(diff / 86400000);
            this.hours = Math.floor((diff % 86400000) / 3600000);
            this.minutes = Math.floor((diff % 3600000) / 60000);
            this.seconds = Math.floor((diff % 60000) / 1000);
        },
        destroy() {
            clearInterval(this.timer);
        },
    }));

    Alpine.data("layupSlider", (total, autoplay = true, speed = 5000) => ({
        active: 0,
        total,
        autoplay,
        speed,
        timer: null,
        init() {
            if (this.autoplay && this.total > 1) {
                this.timer = setInterval(() => this.next(), this.speed);
            }
        },
        next() {
            this.active = (this.active + 1) % this.total;
        },
        prev() {
            this.active = (this.active - 1 + this.total) % this.total;
        },
        goTo(index) {
            this.active = index;
        },
        isActive(index) {
            return this.active === index;
        },
        destroy() {
            clearInterval(this.timer);
        },
    }));

    Alpine.data("layupCounter", (target, animate = true) => ({
        count: 0,
        target,
        animate,
        init() {
            if (!this.animate) {
                this.count = this.target;
                return;
            }
        },
        start() {
            if (!this.animate) return;
            const step = Math.ceil(this.target / 40);
            const i = setInterval(() => {
                this.count += step;
                if (this.count >= this.target) {
                    this.count = this.target;
                    clearInterval(i);
                }
            }, 30);
        },
    }));

    Alpine.data("layupBarCounter", (percent, animate = true) => ({
        width: animate ? 0 : percent,
        percent,
        animate,
        start() {
            if (!this.animate) return;
            setTimeout(() => {
                this.width = this.percent;
            }, 100);
        },
    }));

    Alpine.data("layupLightbox", () => ({
        open: false,
        current: "",
        images: [],
        index: 0,
        init() {
            this.images = Array.from(
                this.$el.querySelectorAll("[data-lightbox-src]"),
            ).map((el) => el.dataset.lightboxSrc);
        },
        show(src) {
            this.current = src;
            this.index = this.images.indexOf(src);
            this.open = true;
            document.body.style.overflow = "hidden";
        },
        close() {
            this.open = false;
            document.body.style.overflow = "";
        },
        next() {
            this.index = (this.index + 1) % this.images.length;
            this.current = this.images[this.index];
        },
        prev() {
            this.index =
                (this.index - 1 + this.images.length) % this.images.length;
            this.current = this.images[this.index];
        },
    }));
});
