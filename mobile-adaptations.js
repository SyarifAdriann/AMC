// Mobile-specific adaptations and touch optimizations
class MobileAdaptations {
    constructor() {
        this.isMobile = window.innerWidth < 1024;
        this.isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
        this.init();
    }

    init() {
        this.setupResizeHandler();
        this.setupTouchOptimizations();
        this.setupApronScaling();
        this.setupModalAdaptations();
        this.setupTableResponsive();
    }

    setupResizeHandler() {
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.isMobile = window.innerWidth < 1024;
                this.isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
                this.updateAdaptations();
            }, 100);
        });
    }

    setupTouchOptimizations() {
        if (this.isMobile) {
            // Prevent iOS zoom on input focus
            document.querySelectorAll('input, select, textarea').forEach(input => {
                input.style.fontSize = '16px';
            });

            // Improve touch target sizes
            document.querySelectorAll('.stand').forEach(stand => {
                stand.style.minWidth = '44px';
                stand.style.minHeight = '44px';
                stand.style.display = 'flex';
                stand.style.alignItems = 'center';
                stand.style.justifyContent = 'center';
            });

            // Add touch feedback
            document.addEventListener('touchstart', this.handleTouchStart, { passive: true });
            document.addEventListener('touchend', this.handleTouchEnd, { passive: true });
        }
    }

    setupApronScaling() {
        const apronWrapper = document.getElementById('apron-wrapper');
        const apronContainer = document.getElementById('apron-container');
        
        if (!apronWrapper || !apronContainer) return;

        const updateApronScale = () => {
            const wrapperWidth = apronWrapper.clientWidth;
            const wrapperHeight = apronWrapper.clientHeight;
            const containerWidth = 1920;
            const containerHeight = 1080;

            if (this.isMobile) {
                // Mobile: fit to screen with scrolling
                const scale = Math.min(wrapperWidth / containerWidth, 0.5) * 0.95;
                apronContainer.style.transform = `scale(${scale})`;
                apronContainer.style.transformOrigin = 'top left';
                apronWrapper.style.height = `${containerHeight * scale}px`;
                apronWrapper.style.overflowX = 'auto';
                apronWrapper.style.overflowY = 'auto';
            } else if (this.isTablet) {
                // Tablet: better fit
                const scale = Math.min(wrapperWidth / containerWidth, wrapperHeight / containerHeight) * 0.9;
                apronContainer.style.transform = `scale(${scale})`;
                apronContainer.style.transformOrigin = 'top left';
                apronWrapper.style.height = `${containerHeight * scale}px`;
            } else {
                // Desktop: original scaling
                const scale = (wrapperWidth / containerWidth) * 0.95;
                apronContainer.style.transform = `scale(${scale})`;
                apronContainer.style.transformOrigin = 'top left';
                apronWrapper.style.height = `${containerHeight * scale}px`;
            }
        };

        updateApronScale();
        this.updateApronScale = updateApronScale;
    }

    setupModalAdaptations() {
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop && this.isMobile) {
                    // Prevent accidental closes on mobile
                    e.preventDefault();
                }
            });
        });

        // Improve modal positioning on mobile
        if (this.isMobile) {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.maxHeight = '90vh';
                modal.style.overflowY = 'auto';
                modal.style.margin = '10px';
            });
        }
    }

    setupTableResponsive() {
        // Handle card vs table view switching
        const updateTableView = () => {
            const mobileCards = document.querySelectorAll('.mobile-cards');
            const desktopTables = document.querySelectorAll('.desktop-table');

            mobileCards.forEach(card => {
                card.style.display = this.isMobile ? 'block' : 'none';
            });

            desktopTables.forEach(table => {
                table.style.display = this.isMobile ? 'none' : 'block';
            });
        };

        updateTableView();
        this.updateTableView = updateTableView;
    }

    handleTouchStart(e) {
        if (e.target.classList.contains('stand') || e.target.closest('.stand')) {
            e.target.style.transform += ' scale(0.95)';
        }
    }

    handleTouchEnd(e) {
        if (e.target.classList.contains('stand') || e.target.closest('.stand')) {
            setTimeout(() => {
                e.target.style.transform = e.target.style.transform.replace(' scale(0.95)', '');
            }, 150);
        }
    }

    updateAdaptations() {
        if (this.updateApronScale) this.updateApronScale();
        if (this.updateTableView) this.updateTableView();
        this.setupTouchOptimizations();
        this.setupModalAdaptations();
    }

    // Utility method for smooth scrolling to elements
    scrollToElement(element, offset = 80) {
        const elementPosition = element.offsetTop - offset;
        window.scrollTo({
            top: elementPosition,
            behavior: 'smooth'
        });
    }

    // Handle virtual keyboard on iOS
    handleVirtualKeyboard() {
        if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
            const originalViewportHeight = window.innerHeight;
            
            window.addEventListener('resize', () => {
                const currentViewportHeight = window.innerHeight;
                const keyboardHeight = originalViewportHeight - currentViewportHeight;
                
                if (keyboardHeight > 150) {
                    // Keyboard is open
                    document.body.style.paddingBottom = `${keyboardHeight}px`;
                } else {
                    // Keyboard is closed
                    document.body.style.paddingBottom = '0px';
                }
            });
        }
    }
}

// Initialize on DOM content loaded
document.addEventListener('DOMContentLoaded', () => {
    window.mobileAdaptations = new MobileAdaptations();
});