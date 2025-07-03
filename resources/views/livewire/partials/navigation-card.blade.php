@php
    /** @var Commit|null $nextCommit */
    use App\Data\Commit;$nextCommit = $this->nextCommit;
@endphp
<div class="glass-premium shadow-2xl rounded-2xl overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent"></div>
    <div class="relative p-6 space-y-4">
        <div class="text-amber-200/80 text-sm font-medium mb-1">Next Commit Preview:</div>
        <div class="p-4 bg-zinc-900/30 rounded-xl border border-amber-400/10 min-h-[80px]">
            @if($nextCommit)
                <div class="flex items-center gap-2 text-amber-100/60 text-xs mb-2">
                    <i data-lucide="user" class="w-3 h-3"></i><span>{{ $nextCommit->authorName }}</span>
                    <i data-lucide="hash" class="w-3 h-3 ml-2"></i><code>{{ $nextCommit->shortSha }}</code>
                </div>
                <p class="font-semibold text-amber-50 truncate">{{ $nextCommit->subject }}</p>
            @else
                <p class="text-center text-amber-100/50">You've reached the last commit!</p>
            @endif
        </div>
        <div class="flex gap-4">
            <button wire:click="stepBackward" @if(!$this->canStepBackward) disabled @endif
                    class="flex-1 px-6 py-3 bg-zinc-900/50 border border-amber-400/20 text-amber-100 rounded-lg hover:bg-zinc-900/70 hover:border-amber-400/30 transition-all duration-200 flex items-center justify-center gap-2 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
                <span wire:loading.remove wire:target="stepBackward">Previous Commit</span>
                <span wire:loading wire:target="stepBackward">Stepping...</span>
            </button>
            <button wire:click="stepForward" @if(!$this->canStepForward) disabled @endif
            class="flex-1 px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white rounded-xl border-0 transition-all duration-200 flex items-center justify-center gap-2 font-medium shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none disabled:scale-100">
                <span wire:loading.remove wire:target="stepForward">Next Commit</span>
                <span wire:loading wire:target="stepForward">Stepping...</span>
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>
            <button wire:click="refreshRepository" wire:loading.attr="disabled"
                    class="flex-1 px-6 py-3 bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-600 hover:to-indigo-700 text-white rounded-xl border-0 transition-all duration-200 flex items-center justify-center gap-2 font-medium shadow-lg shadow-sky-500/25 hover:shadow-sky-500/40 hover:scale-[1.02]">
                <i data-lucide="refresh-cw" class="w-5 h-5" wire:loading.class="animate-spin"
                   wire:target="refreshRepository"></i>
                <span wire:loading.remove wire:target="refreshRepository">Refresh</span>
                <span wire:loading wire:target="refreshRepository">Refreshing...</span>
            </button>
        </div>
    </div>
</div>