<div class="glass-premium shadow-2xl rounded-2xl overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent"></div>
    <div class="relative p-6 border-b border-emerald-400/20">
        <h2 class="text-emerald-50 text-xl font-semibold flex items-center gap-2"><i data-lucide="target"
                                                                                     class="w-5 h-5 text-emerald-400"></i>
            Progress</h2>
    </div>
    <div class="relative p-6 space-y-4">
        @if($this->targetCommit && $this->currentCommit)
            <div class="space-y-3">
                <div class="flex justify-between text-emerald-100/80 text-sm font-medium">
                    <span>Completion</span>
                    <span class="text-emerald-50 font-bold">{{ number_format($this->progressPercentage, 2) }}%</span>
                </div>
                <div class="w-full bg-zinc-900/50 rounded-full h-4 overflow-hidden">
                    <div class="progress-bar bg-gradient-to-r from-emerald-500 to-teal-500 h-4 rounded-full relative overflow-hidden"
                         style="width: {{ $this->progressPercentage }}%">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent shimmer"></div>
                    </div>
                </div>
                <div class="text-center text-emerald-200/70 text-sm font-medium">
                    {{ number_format($this->currentCommitIndex + 1) }}
                    / {{ number_format($this->targetCommitIndex + 1) }} commits
                </div>
            </div>
        @else
            <div class="text-center text-emerald-200/70 text-sm">Target commit not set or found.</div>
        @endif
    </div>
</div>