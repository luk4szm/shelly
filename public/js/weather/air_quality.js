// language: javascript
/**
 * Kontroler wykresów jakości powietrza i pogody z obsługą daty:
 * - start z data-role-date (YYYY-MM-DD),
 * - aktualizacja ?date= w URL przy każdej zmianie,
 * - pobieranie danych dla wybranej daty,
 * - odświeżanie kart z tej samej strony pod wybraną datę.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.ApexCharts) {
        console.error('Brak ApexCharts.');
        return;
    }

    const dateInput = document.getElementById('wheater_date');
    const prevBtn = document.getElementById('prev-day-btn');
    const nextBtn = document.getElementById('next-day-btn');
    const elAir = document.getElementById('chart-air-quality');
    const elWeather = document.getElementById('chart-weather');

    if (!dateInput || !prevBtn || !nextBtn || (!elAir && !elWeather)) {
        return;
    }

    let airChart = null;
    let weatherChart = null;
    let cleanupPreviewIds = [];

    const cleanupModal = document.getElementById('weather-invalid-readings-modal');
    const cleanupDateLabel = document.getElementById('weather-invalid-date-label');
    const cleanupFieldInput = document.getElementById('weather-invalid-field');
    const cleanupFromInput = document.getElementById('weather-invalid-from-time');
    const cleanupToInput = document.getElementById('weather-invalid-to-time');
    const cleanupPreviewBtn = document.getElementById('weather-invalid-preview-btn');
    const cleanupApplyBtn = document.getElementById('weather-invalid-apply-btn');
    const cleanupStatus = document.getElementById('weather-invalid-status');
    const cleanupError = document.getElementById('weather-invalid-error');
    const cleanupEmpty = document.getElementById('weather-invalid-empty');
    const cleanupSummary = document.getElementById('weather-invalid-summary');
    const cleanupPreview = document.getElementById('weather-invalid-preview');
    const cleanupPreviewBody = document.getElementById('weather-invalid-preview-body');

    const formatDate = (date) => date.toISOString().split('T')[0];
    const isValidDateStr = (value) => /^\d{4}-\d{2}-\d{2}$/.test(value);

    const syncCleanupDate = () => {
        if (cleanupDateLabel) {
            cleanupDateLabel.textContent = dateInput.value || '-';
        }
    };

    const setCleanupStatus = (message = '') => {
        if (cleanupStatus) {
            cleanupStatus.textContent = message;
        }
    };

    const hideCleanupFeedback = () => {
        if (cleanupError) {
            cleanupError.classList.add('d-none');
            cleanupError.textContent = '';
        }

        if (cleanupEmpty) {
            cleanupEmpty.classList.add('d-none');
            cleanupEmpty.textContent = '';
        }

        if (cleanupSummary) {
            cleanupSummary.classList.add('d-none');
            cleanupSummary.innerHTML = '';
        }
    };

    const resetCleanupPreview = () => {
        cleanupPreviewIds = [];
        hideCleanupFeedback();
        setCleanupStatus('');

        if (cleanupPreviewBody) {
            cleanupPreviewBody.innerHTML = '';
        }

        if (cleanupPreview) {
            cleanupPreview.classList.add('d-none');
        }

        if (cleanupApplyBtn) {
            cleanupApplyBtn.classList.add('d-none');
            cleanupApplyBtn.disabled = true;
            cleanupApplyBtn.textContent = 'Zmień na null';
        }
    };

    const setCleanupBusy = (isBusy, phase = 'preview') => {
        if (cleanupPreviewBtn) {
            cleanupPreviewBtn.disabled = isBusy;
            cleanupPreviewBtn.textContent = isBusy && phase === 'preview' ? 'Szukam…' : 'Pokaż podejrzane odczyty';
        }

        if (cleanupApplyBtn) {
            cleanupApplyBtn.disabled = isBusy || cleanupPreviewIds.length === 0;
            cleanupApplyBtn.textContent = isBusy && phase === 'apply' ? 'Zmieniam…' : 'Zmień na null';
        }
    };

    const validateCleanupTimeRange = () => {
        if (cleanupFromInput && cleanupToInput && cleanupFromInput.value && cleanupToInput.value && cleanupFromInput.value > cleanupToInput.value) {
            throw new Error('Godzina początkowa nie może być późniejsza niż końcowa.');
        }
    };

    const buildCleanupPayload = () => ({
        date: dateInput.value,
        field: cleanupFieldInput ? cleanupFieldInput.value : null,
        fromTime: cleanupFromInput && cleanupFromInput.value ? cleanupFromInput.value : null,
        toTime: cleanupToInput && cleanupToInput.value ? cleanupToInput.value : null,
    });

    const fetchCleanupJson = async (url, payload) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.error || `HTTP ${response.status}`);
        }

        return data;
    };

    const renderCleanupPreview = (data) => {
        resetCleanupPreview();

        cleanupPreviewIds = Array.isArray(data.candidates)
            ? data.candidates.map((candidate) => candidate.id).filter((id) => Number.isInteger(id))
            : [];

        if (!cleanupPreviewIds.length) {
            if (cleanupEmpty) {
                cleanupEmpty.classList.remove('d-none');
                cleanupEmpty.textContent = 'Nie znaleziono podejrzanych odczytów dla wybranego zakresu.';
            }

            return;
        }

        if (cleanupSummary) {
            cleanupSummary.classList.remove('d-none');
            cleanupSummary.innerHTML = `<div class="alert alert-warning mb-0">Znaleziono <strong>${data.count}</strong> podejrzanych odczytów dla parametru <strong>${data.fieldLabel}</strong> w zakresie ${data.fromTime} - ${data.toTime}.</div>`;
        }

        if (cleanupPreviewBody) {
            cleanupPreviewBody.innerHTML = data.candidates.map((candidate) => {
                const reasons = Array.isArray(candidate.reasons) ? candidate.reasons.join('<br>') : '';

                return `
                    <tr>
                        <td>${candidate.measuredAt}</td>
                        <td>${candidate.value} ${data.unit}</td>
                        <td class="text-muted">${reasons}</td>
                    </tr>
                `;
            }).join('');
        }

        if (cleanupPreview) {
            cleanupPreview.classList.remove('d-none');
        }

        if (cleanupApplyBtn) {
            cleanupApplyBtn.classList.remove('d-none');
            cleanupApplyBtn.disabled = false;
        }
    };

    const updateNextButtonState = () => {
        const today = new Date();
        const currentDate = new Date(dateInput.value);
        today.setHours(0, 0, 0, 0);
        currentDate.setHours(0, 0, 0, 0);
        // nextBtn.disabled = currentDate >= today;
    };

    const checkForecastTransition = (newDateStr, isPopstate = false) => {
        const todayStr = formatDate(new Date());
        const isFutureNow = newDateStr > todayStr;
        const wasFuture = document.getElementById('air_quality_data_cards') === null;

        if (isFutureNow !== wasFuture) {
            if (isPopstate) {
                window.location.reload();
            } else {
                const url = new URL(window.location.href);
                url.searchParams.set('date', newDateStr);
                window.location.assign(url.toString());
            }
            return true;
        }

        return false;
    };

    const setUrlDateParam = (dateStr, replace = false) => {
        const url = new URL(window.location.href);
        if (dateStr) {
            url.searchParams.set('date', dateStr);
        } else {
            url.searchParams.delete('date');
        }
        if (replace) {
            window.history.replaceState({}, '', url.toString());
        } else {
            window.history.pushState({}, '', url.toString());
        }
    };

    const changeDate = (days) => {
        if (!dateInput.value) return;
        const currentDate = new Date(dateInput.value);
        currentDate.setDate(currentDate.getDate() + days);
        const newVal = formatDate(currentDate);

        if (checkForecastTransition(newVal)) {
            return;
        }

        dateInput.value = newVal;
        syncCleanupDate();
        resetCleanupPreview();
        setUrlDateParam(newVal);
        updateNextButtonState();
        loadAll(newVal);
        reloadAirQualityCards();
    };

    const dateHolder = document.querySelector('[data-role-date]');
    const dateFromDataAttr = dateHolder ? (dateHolder.getAttribute('data-role-date') || '').trim() : '';

    const fetchAirQuality = async (dateStr) => {
        try {
            if (elAir) elAir.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            const res = await fetch(`/weather/get-air-quality?date=${encodeURIComponent(dateStr)}`, { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            console.error('Błąd pobierania jakości powietrza:', e);
            return null;
        }
    };

    const fetchWeather = async (dateStr) => {
        try {
            if (elWeather) elWeather.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
            const res = await fetch(`/weather/get-weather-data?date=${encodeURIComponent(dateStr)}`, { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            console.error('Błąd pobierania danych pogodowych:', e);
            return null;
        }
    };

    const transformAir = (raw) => {
        if (!Array.isArray(raw)) return { series: [] };
        const points = raw
            .map((r) => {
                const t = new Date(r.measuredAt).getTime();
                if (Number.isNaN(t)) return null;
                return {
                    x: t,
                    pm25: typeof r.pm25 === 'number' ? r.pm25 : null,
                    pm10: typeof r.pm10 === 'number' ? r.pm10 : null,
                };
            })
            .filter(Boolean)
            .sort((a, b) => a.x - b.x);

        const pm25 = points.filter((p) => p.pm25 != null).map((p) => ({ x: p.x, y: p.pm25 }));
        const pm10 = points.filter((p) => p.pm10 != null).map((p) => ({ x: p.x, y: p.pm10 }));

        return {
            series: [
                { name: 'PM2.5', data: pm25 },
                { name: 'PM10', data: pm10 },
            ],
        };
    };

    const transformWeather = (raw) => {
        if (!Array.isArray(raw)) return { series: [] };
        const pts = raw
            .map((r) => {
                const t = new Date(r.measuredAt).getTime();
                if (Number.isNaN(t)) return null;
                return {
                    x: t,
                    pressure: typeof r.pressure === 'number' ? r.pressure : null,
                    temperature: typeof r.temperature === 'number' ? r.temperature : null,
                    perceivedTemperature: typeof r.perceivedTemperature === 'number' ? r.perceivedTemperature : null,
                    humidity: typeof r.humidity === 'number' ? r.humidity : null,
                };
            })
            .filter(Boolean)
            .sort((a, b) => a.x - b.x);

        const pressureAll = pts.filter((p) => p.pressure != null).map((p) => ({ x: p.x, y: p.pressure }));
        const temperatureAll = pts.filter((p) => p.temperature != null).map((p) => ({ x: p.x, y: p.temperature }));
        const perceivedTemperature = pts.filter((p) => p.perceivedTemperature != null).map((p) => ({ x: p.x, y: p.perceivedTemperature }));
        const humidity = pts.filter((p) => p.humidity != null).map((p) => ({ x: p.x, y: p.humidity }));

        const cutoffTime = new Date().getTime();
        const tempSolid = [];
        const tempDashed = [];
        const pressureSolid = [];
        const pressureDashed = [];

        temperatureAll.forEach((pt) => {
            if (pt.x <= cutoffTime) tempSolid.push(pt);
            if (pt.x >= cutoffTime) tempDashed.push(pt);
        });

        pressureAll.forEach((pt) => {
            if (pt.x <= cutoffTime) pressureSolid.push(pt);
            if (pt.x >= cutoffTime) pressureDashed.push(pt);
        });

        const ensureContinuity = (solid, dashed) => {
            if (solid.length > 0 && dashed.length > 0) {
                const lastSolid = solid[solid.length - 1];
                const firstDashed = dashed[0];
                if (firstDashed.x > lastSolid.x) dashed.unshift(lastSolid);
            }
        };

        ensureContinuity(tempSolid, tempDashed);
        ensureContinuity(pressureSolid, pressureDashed);

        return {
            series: [
                { name: 'Ciśnienie', data: pressureSolid, type: 'line' },
                { name: 'Prognoza ciśnienia', data: pressureDashed, type: 'line' },
                { name: 'Temperatura', data: tempSolid, type: 'area' },
                { name: 'Prognoza temp.', data: tempDashed, type: 'area' },
                { name: 'Temp. odczuwalna', data: perceivedTemperature, type: 'line' },
                { name: 'Wilgotność', data: humidity, type: 'area' },
            ],
        };
    };

    const renderAir = ({ series }) => {
        if (!elAir) return;

        const selectedDateStr = dateInput.value;
        const todayStr = formatDate(new Date());
        const isFuture = selectedDateStr > todayStr;
        const containerChart = elAir.closest('.row');
        const containerCards = document.getElementById('air_quality_data_cards');
        const hasData = series.some((s) => s.data.length > 0);

        if (isFuture && !hasData) {
            if (containerChart) containerChart.style.display = 'none';
            if (containerCards) containerCards.style.display = 'none';
        } else {
            if (containerChart) containerChart.style.display = '';
            if (containerCards) containerCards.style.display = '';
        }

        if (!hasData) {
            if (airChart) {
                airChart.destroy();
                airChart = null;
            }
            elAir.innerHTML = '<div class="text-center p-4">Brak danych.</div>';
            return;
        }

        const selectedDate = new Date(dateInput.value);
        const minX = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate(), 0, 0, 0).getTime();
        const maxX = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate(), 23, 59, 59).getTime();

        const options = {
            chart: { type: 'area', height: 340, toolbar: { show: false }, animations: { enabled: true } },
            series,
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 0.3, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 50, 100] },
            },
            dataLabels: { enabled: false },
            markers: { size: 0, hover: { sizeOffset: 2 } },
            tooltip: {
                shared: true,
                intersect: false,
                theme: 'dark',
                x: { format: 'dd MMM, HH:mm' },
                y: { formatter: (v) => (v == null ? '' : `${v.toFixed(1)} µg/m³`) },
            },
            xaxis: {
                type: 'datetime',
                labels: { format: 'HH:mm', datetimeUTC: false },
                min: minX,
                max: maxX,
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: { formatter: (v) => (v == null ? '' : `${v.toFixed(0)}`) },
                title: { text: 'µg/m³' },
            },
            grid: { strokeDashArray: 4 },
            legend: { show: true, position: 'bottom' },
            colors: ['#ff6b6b', '#4dabf7'],
        };

        elAir.innerHTML = '';
        if (!airChart) {
            airChart = new ApexCharts(elAir, options);
            airChart.render();
        } else {
            airChart.updateOptions(options, true, true);
        }
    };

    const renderWeather = ({ series }) => {
        if (!elWeather) return;
        const hasData = series.some((s) => s.data.length > 0);
        if (!hasData) {
            if (weatherChart) {
                weatherChart.destroy();
                weatherChart = null;
            }
            elWeather.innerHTML = '<div class="text-center p-4">Brak danych.</div>';
            return;
        }

        const selectedDate = new Date(dateInput.value);
        const minX = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate(), 0, 0, 0).getTime();
        const maxX = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate(), 23, 59, 59).getTime();

        const pressureValues = []
            .concat((series[0].data || []).map((p) => p.y))
            .concat((series[1].data || []).map((p) => p.y))
            .filter((v) => typeof v === 'number' && !Number.isNaN(v));

        let pressureMin = null;
        let pressureMax = null;
        if (pressureValues.length > 0) {
            const rawPMin = Math.min(...pressureValues);
            const rawPMax = Math.max(...pressureValues);
            const pPadding = Math.max(1, (rawPMax - rawPMin) * 0.1);
            pressureMin = Math.floor(rawPMin - pPadding);
            pressureMax = Math.ceil(rawPMax + pPadding);
        }

        const tempValues = []
            .concat((series[2].data || []).map((p) => p.y))
            .concat((series[3].data || []).map((p) => p.y))
            .concat((series[4].data || []).map((p) => p.y))
            .filter((v) => typeof v === 'number' && !Number.isNaN(v));

        let tempMin = null;
        let tempMax = null;
        if (tempValues.length > 0) {
            const rawMin = Math.min(...tempValues);
            const rawMax = Math.max(...tempValues);
            const padding = Math.max(0.5, (rawMax - rawMin) * 0.05);
            const valForMin = rawMin - padding;
            const valForMax = rawMax + padding;
            tempMin = Math.floor(valForMin / 2) * 2;
            tempMax = Math.ceil(valForMax / 2) * 2;
        }

        const humiditySeries = series[5] || { data: [] };
        const humValues = (humiditySeries.data || [])
            .map((p) => p.y)
            .filter((v) => typeof v === 'number' && !Number.isNaN(v));

        let humMin = 0;
        let humMax = 100;
        if (humValues.length > 0) {
            const rawHumMin = Math.min(...humValues);
            const rawHumMax = Math.max(...humValues);
            const humPadding = Math.max(2, (rawHumMax - rawHumMin) * 0.1);
            humMin = Math.max(0, rawHumMin - humPadding);
            humMax = Math.min(100, rawHumMax + humPadding);
        }

        const options = {
            chart: {
                height: 300,
                type: 'line',
                stacked: false,
                toolbar: { show: false },
                animations: { enabled: true },
            },
            series,
            colors: ['#7463f0', '#7463f0', '#d90f0f', '#d90f0f', '#d90f0f', '#4bc0c0'],
            stroke: {
                curve: 'smooth',
                width: [2, 1.5, 2, 1.5, 1.5, 2],
                dashArray: [0, 5, 0, 5, 6, 0],
            },
            fill: {
                type: ['solid', 'solid', 'gradient', 'gradient', 'solid', 'gradient'],
                gradient: {
                    shadeIntensity: 0.3,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 99, 100],
                },
            },
            dataLabels: { enabled: false },
            markers: { size: 0, hover: { sizeOffset: 2 } },
            xaxis: {
                type: 'datetime',
                labels: { format: 'HH:mm', datetimeUTC: false },
                min: minX,
                max: maxX,
            },
            yaxis: [
                {
                    seriesName: 'Ciśnienie',
                    opposite: true,
                    title: { text: 'hPa' },
                    labels: { formatter: (v) => (v == null ? '' : `${v.toFixed(0)}`) },
                    ...(pressureMin !== null && pressureMax !== null ? { min: pressureMin, max: pressureMax } : {}),
                },
                {
                    seriesName: 'Prognoza ciśnienia',
                    show: false,
                    opposite: true,
                    ...(pressureMin !== null && pressureMax !== null ? { min: pressureMin, max: pressureMax } : {}),
                },
                {
                    seriesName: 'Temperatura',
                    opposite: false,
                    title: { text: '°C' },
                    labels: { formatter: (v) => (v == null ? '' : `${v.toFixed(1)}°C`) },
                    ...(tempMin !== null && tempMax !== null ? { min: tempMin, max: tempMax, tickAmount: (tempMax - tempMin) / 5 } : {}),
                },
                {
                    seriesName: 'Prognoza temp.',
                    show: false,
                    ...(tempMin !== null && tempMax !== null ? { min: tempMin, max: tempMax, tickAmount: (tempMax - tempMin) / 5 } : {}),
                },
                {
                    seriesName: 'Temp. odczuwalna',
                    show: false,
                    ...(tempMin !== null && tempMax !== null ? { min: tempMin, max: tempMax, tickAmount: (tempMax - tempMin) / 5 } : {}),
                },
                {
                    seriesName: 'Wilgotność',
                    opposite: true,
                    title: { text: '%' },
                    labels: { formatter: (v) => (v == null ? '' : `${v.toFixed(0)}%`) },
                    min: humMin,
                    max: humMax,
                },
            ],
            grid: { strokeDashArray: 4 },
            legend: { show: true, position: 'bottom' },
            tooltip: {
                shared: true,
                intersect: false,
                theme: 'dark',
                x: { format: 'dd MMM, HH:mm' },
                y: [
                    { formatter: (v) => (v == null ? '' : `${v.toFixed(1)} hPa`) },
                    { formatter: (v) => (v == null ? '' : `${v.toFixed(1)} hPa`) },
                    { formatter: (v) => (v == null ? '' : `${v.toFixed(1)} °C`) },
                    { formatter: (v) => (v == null ? '' : `${v.toFixed(1)} °C`) },
                    { formatter: (v) => (v == null ? '' : `${v.toFixed(1)} °C`) },
                    { formatter: (v) => (v == null ? '' : `${v.toFixed(0)} %`) },
                ],
            },
        };

        elWeather.innerHTML = '';
        if (!weatherChart) {
            weatherChart = new ApexCharts(elWeather, options);
            weatherChart.render();
            weatherChart.hideSeries('Wilgotność');
        } else {
            weatherChart.updateOptions(options, true, true);
        }
    };

    const reloadAirQualityCards = async () => {
        const container = document.getElementById('air_quality_data_cards');
        if (!container) return;
        container.innerHTML = '<div class="text-center p-3">Ładowanie…</div>';
        try {
            const url = new URL(window.location.href);
            const currentDate = dateInput.value || '';
            if (currentDate) url.searchParams.set('date', currentDate);
            const res = await fetch(url.toString(), { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const html = await res.text();
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const fresh = tmp.querySelector('#air_quality_data_cards');
            container.innerHTML = fresh ? fresh.innerHTML : '<div class="text-center text-muted p-3">Brak danych.</div>';
        } catch (e) {
            console.error(e);
            container.innerHTML = '<div class="text-center text-muted p-3">Nie udało się załadować.</div>';
        }
    };

    const loadAll = async (dateStr) => {
        const tasks = [];
        if (elAir) tasks.push(fetchAirQuality(dateStr).then((raw) => renderAir(transformAir(raw || []))));
        if (elWeather) tasks.push(fetchWeather(dateStr).then((raw) => renderWeather(transformWeather(raw || []))));
        await Promise.allSettled(tasks);
    };

    if (cleanupModal) {
        cleanupModal.addEventListener('show.bs.modal', () => {
            syncCleanupDate();
            resetCleanupPreview();
        });

        [cleanupFieldInput, cleanupFromInput, cleanupToInput].forEach((element) => {
            if (!element) {
                return;
            }

            element.addEventListener('change', () => {
                resetCleanupPreview();
                syncCleanupDate();
            });
        });

        if (cleanupPreviewBtn) {
            cleanupPreviewBtn.addEventListener('click', async () => {
                try {
                    validateCleanupTimeRange();
                    hideCleanupFeedback();
                    setCleanupStatus('Analizuję odczyty…');
                    setCleanupBusy(true, 'preview');
                    const data = await fetchCleanupJson(cleanupModal.dataset.previewUrl, buildCleanupPayload());
                    renderCleanupPreview(data);
                    setCleanupStatus(cleanupPreviewIds.length ? 'Sprawdź listę i zatwierdź zmianę.' : 'Analiza zakończona.');
                } catch (error) {
                    resetCleanupPreview();
                    if (cleanupError) {
                        cleanupError.classList.remove('d-none');
                        cleanupError.textContent = error.message || 'Nie udało się pobrać podglądu.';
                    }
                    setCleanupStatus('');
                } finally {
                    setCleanupBusy(false);
                }
            });
        }

        if (cleanupApplyBtn) {
            cleanupApplyBtn.addEventListener('click', async () => {
                if (!cleanupPreviewIds.length) {
                    return;
                }

                try {
                    validateCleanupTimeRange();
                    hideCleanupFeedback();
                    setCleanupStatus('Zapisuję zmiany…');
                    setCleanupBusy(true, 'apply');
                    const data = await fetchCleanupJson(cleanupModal.dataset.applyUrl, {
                        ...buildCleanupPayload(),
                        ids: cleanupPreviewIds,
                        _token: cleanupModal.dataset.csrfToken,
                    });

                    resetCleanupPreview();
                    setCleanupStatus(`Zmieniono ${data.updated} odczytów na null.`);
                    await Promise.allSettled([loadAll(dateInput.value), reloadAirQualityCards()]);

                    if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                        const instance = window.bootstrap.Modal.getOrCreateInstance(cleanupModal);
                        instance.hide();
                    } else {
                        const dismiss = cleanupModal.querySelector('[data-bs-dismiss="modal"]');
                        if (dismiss) {
                            dismiss.click();
                        }
                    }
                } catch (error) {
                    if (cleanupError) {
                        cleanupError.classList.remove('d-none');
                        cleanupError.textContent = error.message || 'Nie udało się zapisać zmian.';
                    }
                    setCleanupStatus('');
                } finally {
                    setCleanupBusy(false);
                }
            });
        }
    }

    prevBtn.addEventListener('click', () => {
        changeDate(-1);
    });

    nextBtn.addEventListener('click', () => {
        changeDate(1);
    });

    dateInput.addEventListener('change', (e) => {
        const val = e.target.value;
        if (!isValidDateStr(val)) return;

        if (checkForecastTransition(val)) {
            return;
        }

        syncCleanupDate();
        resetCleanupPreview();
        setUrlDateParam(val);
        updateNextButtonState();
        loadAll(val);
        reloadAirQualityCards();
    });

    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        const d = url.searchParams.get('date');
        const dateStr = isValidDateStr(d) ? d : dateInput.value;
        if (isValidDateStr(dateStr)) {
            if (checkForecastTransition(dateStr, true)) {
                return;
            }
            dateInput.value = dateStr;
            syncCleanupDate();
            resetCleanupPreview();
            updateNextButtonState();
            loadAll(dateStr);
            reloadAirQualityCards();
        }
    });

    const urlDate = new URL(window.location.href).searchParams.get('date');
    const initial = isValidDateStr(urlDate)
        ? urlDate
        : (isValidDateStr(dateFromDataAttr) ? dateFromDataAttr : (dateInput.value || formatDate(new Date())));

    dateInput.value = initial;
    syncCleanupDate();
    setUrlDateParam(initial, true);
    updateNextButtonState();
    loadAll(initial);
});
