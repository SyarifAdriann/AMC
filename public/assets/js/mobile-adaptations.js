(function () {
    function initMobileAdaptations() {
        var isMobile = window.innerWidth < 1024;
        if (!isMobile) {
            return;
        }

        var apronWrapper = document.getElementById('apron-wrapper');
        if (apronWrapper) {
            apronWrapper.style.height = '60vh';
            apronWrapper.style.overflowX = 'auto';
            apronWrapper.style.overflowY = 'auto';
        }

        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
            backdrop.classList.remove('items-center');
            backdrop.classList.add('items-start', 'pt-4');
        });

        var chartContainer = document.querySelector('#customPeakChart .relative');
        if (chartContainer) {
            chartContainer.style.overflowX = 'auto';
        }

        document.querySelectorAll('.bg-gray-600').forEach(function (button) {
            button.classList.add('text-center');
        });
    }

    window.addEventListener('load', initMobileAdaptations);
    window.addEventListener('resize', initMobileAdaptations);
})();
