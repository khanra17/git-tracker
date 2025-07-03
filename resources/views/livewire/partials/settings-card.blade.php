@php use Illuminate\Support\Str; @endphp
<div class="glass-premium shadow-2xl rounded-2xl overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent"></div>
    <div class="relative p-6 border-b border-amber-400/20">
        <h2 class="text-amber-50 text-xl font-semibold flex items-center gap-2"><i data-lucide="settings"
                                                                                   class="w-5 h-5 text-amber-400"></i>
            Settings</h2>
    </div>
    <form wire:submit="updateSettings" class="relative p-6 space-y-4">
        <div>
            <label for="targetCommitInput" class="block text-sm font-medium text-amber-100/80 mb-2">Target
                Commit</label>
            <input wire:model.defer="targetCommitReferenceInput" id="targetCommitInput" type="text"
                   placeholder="SHA, tag, or 'latest'"
                   class="w-full px-4 py-3 bg-zinc-900/50 border border-amber-400/20 rounded-lg text-amber-50 placeholder:text-amber-200/30 focus:border-amber-400/50 focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition-all"/>
        </div>
        <div>
            <label for="idealPaceInput" class="block text-sm font-medium text-amber-100/80 mb-2">Ideal Pace
                (reviews/day)</label>
            <input wire:model.defer="idealReviewPaceInput" id="idealPaceInput" type="number" placeholder="20"
                   class="w-full px-4 py-3 bg-zinc-900/50 border border-amber-400/20 rounded-lg text-amber-50 placeholder:text-amber-200/30 focus:border-amber-400/50 focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition-all"/>
        </div>
        <div>
            <label class="block text-sm font-medium text-amber-100/80 mb-2">Actual Pace Calculation</label>
            <div class="grid grid-cols-3 gap-2">
                @foreach(['7-days', '30-days', 'all-time'] as $period)
                    <button type="button" wire:click="$set('actualPacePeriodInput', '{{ $period }}')"
                            class="{{ $actualPacePeriodInput == $period ? 'bg-amber-500 text-white font-semibold' : 'bg-zinc-900/50 border border-amber-400/20 text-amber-100/80 hover:bg-amber-500/20' }} px-3 py-2 text-sm rounded-lg transition-colors duration-200">
                        {{ Str::of($period)->replace('-', ' ')->title() }}
                    </button>
                @endforeach
            </div>
        </div>

        @error('settings') <p class="text-rose-400 text-sm">{{ $message }}</p> @enderror

        <button type="submit"
                class="w-full px-4 py-3 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white rounded-lg border-0 transition-all duration-200 flex items-center justify-center gap-2 font-medium shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:scale-[1.02]">
            <i data-lucide="zap" class="w-4 h-4"></i>
            <span wire:loading.remove wire:target="updateSettings">Update Settings</span>
            <span wire:loading wire:target="updateSettings">Updating...</span>
        </button>
    </form>
</div>