// temperature_air_chart.js
document.addEventListener('DOMContentLoaded', function () {
  if (!window.ApexCharts) {
    console.error('Brak ApexCharts. Upewnij się, że biblioteka jest załadowana.');
    return;
  }

  const el = document.getElementById('chart-temperature');
  if (!el) {
    console.error('Nie znaleziono elementu #chart-temperature');
    return;
  }

  let chart = null;

  const todayDateStr = () => {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  };

  const fetchData = async () => {
    const dateStr = todayDateStr();
    try {
      const res = await fetch(`/weather/get-temperature?date=${encodeURIComponent(dateStr)}`, { cache: 'no-store' });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } catch (e) {
      console.error('Błąd pobierania danych temperatury:', e);
      return null;
    }
  };

  const lastHours = (data, hours = 8) => {
    const now = Date.now();
    const start = now - hours * 60 * 60 * 1000;
    return data.filter(p => p.x >= start && p.x <= now);
  };

  const transform = (raw) => {
    // Oczekiwane elementy: { measuredAt: string|Date, value: number }
    if (!Array.isArray(raw)) return { series: [] };

    const data = raw
      .map(r => {
        const t = new Date(r.measuredAt).getTime();
        if (Number.isNaN(t)) return null;
        const val = typeof r.value === 'number' ? r.value : null;
        return val == null ? null : { x: t, y: val };
      })
      .filter(Boolean)
      .sort((a, b) => a.x - b.x);

    return {
      series: [
        { name: 'Temperatura', data: data }
      ]
    };
  };

  const render = ({ series }) => {
    const hasData = series.some(s => s.data.length > 0);
    if (!hasData) {
      if (chart) { chart.destroy(); chart = null; }
      el.innerHTML = '<div class="text-center p-4">Brak danych.</div>';
      return;
    }

    const options = {
      chart: {
        type: 'area',
        height: 300,
        toolbar: { show: false },
        animations: { enabled: true }
      },
      series,
      stroke: {
        curve: 'smooth',
        width: 2
      },
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 0.3,
          opacityFrom: 0.35,
          opacityTo: 0.05,
          stops: [0, 50, 100]
        }
      },
      colors: ['#ffa94d'],
      dataLabels: { enabled: false },
      markers: { size: 0, hover: { sizeOffset: 2 } },
      tooltip: {
        shared: false,
        intersect: true,
        theme: 'dark',
        x: { format: 'dd MMM, HH:mm' },
        y: { formatter: (v) => (v == null ? '' : `${v.toFixed(1)} °C`) }
      },
      xaxis: {
        type: 'datetime',
        labels: { format: 'HH:mm', datetimeUTC: false }
      },
      yaxis: {
        forceNiceScale: true,
        labels: {
          formatter: (v) => (v == null ? '' : `${v.toFixed(1)}°C`)
        },
        title: { text: '°C' }
      },
      grid: { strokeDashArray: 4 },
      legend: { show: false }
    };

    el.innerHTML = '';
    if (!chart) {
      chart = new ApexCharts(el, options);
      chart.render();
    } else {
      chart.updateOptions(options, true, true);
    }
  };

  const load = async () => {
    el.innerHTML = '<div class="text-center p-4">Ładowanie…</div>';
    const raw = await fetchData();
    const t = transform(raw || []);
    render(t);
  };

  load();
  // setInterval(load, 60000);
});
