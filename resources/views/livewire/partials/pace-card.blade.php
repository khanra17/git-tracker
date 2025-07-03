@php
    // This computed property is evaluated on every render.
    $paceData = $this->paceData;
@endphp

{{-- Only render the card if there's data to show --}}
@if (!empty($paceData))
    <div class="glass-premium shadow-2xl rounded-2xl overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-sky-500/5 to-transparent"></div>
        <div class="relative p-6 border-b border-sky-400/20">
            <h2 class="text-sky-50 text-xl font-semibold flex items-center gap-2">
                <i data-lucide="bar-chart-3" class="w-5 h-5 text-sky-400"></i>
                Pace Projections
            </h2>
        </div>

        <div class="relative p-6 h-64">
            <canvas id="paceProjectionChart"></canvas>
        </div>

        <div class="relative px-6 pb-6 pt-0 space-y-5 text-sm">
            {{-- Ideal Pace --}}
            <div class="">
                <h3 class="font-semibold text-sky-100 mb-2">Ideal Pace <span class="text-xs font-mono text-sky-300/70">({{ number_format($paceData['idealPace'], 1) }}c/d)</span>
                </h3>
                @if ($paceData['idealFinishDate'])
                    <p class="text-sky-200/80">
                        Reach target around
                        <strong class="text-sky-200">{{ $paceData['idealFinishDate']->format('M d, Y') }}</strong>.
                    </p>
                    @if ($paceData['isTargetLatest'] && $paceData['idealCatchUpDate'])
                        <p class="text-sky-200/60 mt-1">
                            Catch up with branch around
                            <strong class="text-sky-200/80">{{ $paceData['idealCatchUpDate']->format('M d, Y') }}</strong>.
                        </p>
                    @elseif($paceData['isTargetLatest'] && !$paceData['idealCatchUpDate'] && $paceData['idealPace'] > 0)
                        <p class="text-rose-300/70 mt-1 text-xs">Pace is too slow to catch up with upstream
                            ({{ number_format($paceData['upstreamPace'], 1) }}c/d).</p>
                    @endif
                @else
                    <p class="text-sky-200/50">Your ideal pace is zero.</p>
                @endif
            </div>

            {{-- Actual Pace --}}
            <div class="border-t border-sky-400/10 pt-4">
                <h3 class="font-semibold text-sky-100 mb-2">Actual Pace <span class="text-xs font-mono text-sky-300/70">({{ number_format($paceData['actualPace'], 1) }}c/d)</span>
                </h3>
                @if ($paceData['actualFinishDate'])
                    <p class="text-sky-200/80">
                        At current pace, finish around
                        <strong class="text-sky-200">{{ $paceData['actualFinishDate']->format('M d, Y') }}</strong>.
                    </p>
                    @if ($paceData['isTargetLatest'] && $paceData['actualCatchUpDate'])
                        <p class="text-sky-200/60 mt-1">
                            Catch up with branch around
                            <strong class="text-sky-200/80">{{ $paceData['actualCatchUpDate']->format('M d, Y') }}</strong>.
                        </p>
                    @elseif($paceData['isTargetLatest'] && !$paceData['actualCatchUpDate'] && $paceData['actualPace'] > 0)
                        <p class="text-rose-300/70 mt-1 text-xs">Pace is too slow to catch up with upstream
                            ({{ number_format($paceData['upstreamPace'], 1) }}c/d).</p>
                    @endif
                @else
                    <p class="text-sky-200/50">Not enough review history to calculate.</p>
                @endif
            </div>

        </div>
    </div>
@endif
<script>
    // --- Pace Chart Logic ---
    let paceChart = null;

    function renderPaceChart(data) {
        const canvas = document.getElementById('paceProjectionChart');
        if (!canvas) return;

        if (paceChart) {
            paceChart.destroy();
        }

        if (!data || !data.datasets || data.datasets.length === 0) {
            canvas.style.display = 'none';
            return;
        }
        canvas.style.display = 'block';

        paceChart = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {datasets: data.datasets},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'time',
                        time: {unit: 'month', tooltipFormat: 'MMM d, yyyy', displayFormats: {month: 'MMM yyyy'}},
                        grid: {color: 'rgba(255, 255, 255, 0.1)'},
                        ticks: {color: 'rgba(255, 255, 255, 0.5)'}
                    },
                    y: {
                        beginAtZero: true,
                        title: {display: true, text: 'Commits Remaining', color: 'rgba(255, 255, 255, 0.7)'},
                        grid: {color: 'rgba(255, 255, 255, 0.1)'},
                        ticks: {color: 'rgba(255, 255, 255, 0.5)'}
                    }
                },
                plugins: {
                    legend: {position: 'top', labels: {color: 'rgba(255, 255, 255, 0.7)', usePointStyle: true}},
                    tooltip: {mode: 'index', intersect: false, backgroundColor: 'rgba(0, 0, 0, 0.8)'}
                },
                interaction: {mode: 'index', intersect: false}
            }
        });
    }

    document.addEventListener('livewire:initialized', () => {
        Livewire.on('updatePaceChart', event => {
            renderPaceChart(event.data);
        });
    });
</script>