document.addEventListener('DOMContentLoaded', function () {
    const progressBars = document.querySelectorAll('.progress-bar');
    if (progressBars.length === 0) return;

    const formatTime = (seconds) => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    const setFinishedState = (bar, text, durationSeconds) => {
        bar.style.width = '100%';
        bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
        bar.classList.add('bg-green');
        if (text) {
            text.innerHTML = `100%<br>${formatTime(durationSeconds)}`;
        }
    };

    const updateAllProgress = () => {
        const now = new Date().getTime();

        progressBars.forEach(progressBar => {
            const row = progressBar.closest('tr');
            const progressText = row ? row.querySelector('.progress-text') : null;

            const startTimeStr = progressBar.getAttribute('data-start-time');
            const durationSeconds = parseInt(progressBar.getAttribute('data-duration'), 10);
            const endTimeStr = progressBar.getAttribute('data-end-time');

            const startTime = new Date(startTimeStr).getTime();

            // Obsługa trybu ręcznego (brak duration)
            if (!durationSeconds || durationSeconds === 0) {
                if (now >= startTime) {
                    const elapsedSeconds = Math.floor((now - startTime) / 1000);
                    if (progressText) {
                        progressText.innerHTML = `${formatTime(elapsedSeconds)}`;
                    }
                }
                return;
            }

            const totalMs = durationSeconds * 1000;
            const endTime = endTimeStr ? new Date(endTimeStr).getTime() : startTime + totalMs;

            const formattedTotalTime = formatTime(durationSeconds);

            if (now >= endTime) {
                setFinishedState(progressBar, progressText, durationSeconds);
                return;
            }

            if (now < startTime) {
                progressBar.style.width = '0%';
                if (progressText) {
                    progressText.innerHTML = `0%<br>${formattedTotalTime}`;
                }
                return;
            }

            // W trakcie nawadniania
            const elapsedMs = now - startTime;
            let percentage = (elapsedMs / totalMs) * 100;

            if (percentage < 0) percentage = 0;
            if (percentage > 100) percentage = 100;

            progressBar.style.width = percentage.toFixed(2) + '%';
            progressBar.classList.add('progress-bar-striped', 'progress-bar-animated');
            progressBar.classList.remove('bg-green');

            if (progressText) {
                progressText.innerHTML = `${Math.floor(percentage)}%<br>${formattedTotalTime}`;
            }
        });
    };

    updateAllProgress();
    setInterval(updateAllProgress, 1000);
});
