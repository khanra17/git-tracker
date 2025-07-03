{{-- We change the wire:poll target to a new public method --}}
<div id="mainApp" class="relative p-6">
    {{-- Error state or invalid repo --}}
    @if ($errors->has('state') || !$repository)
        <div class="text-center text-xl text-rose-300 p-8 glass-premium rounded-xl">
            <p class="font-semibold mb-2">A problem occurred</p>
            <p class="text-base">{{ $errors->first('state') ?? 'Could not load the repository.' }}</p>
            <button wire:click="changeRepository"
                    class="mt-4 px-4 py-2 bg-zinc-900/50 border border-emerald-400/20 text-emerald-100 rounded-lg hover:bg-zinc-900/70 hover:border-emerald-400/30 transition-all duration-200">
                Go Back
            </button>
        </div>
        {{-- Main application view --}}
    @else
        <header class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg shadow-emerald-500/25">
                        <i data-lucide="git-branch" class="w-6 h-6 text-white"></i>
                    </div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-emerald-200 to-teal-200 bg-clip-text text-transparent">
                        Git Tracker</h1>
                </div>
                <span class="px-4 py-2 bg-zinc-900/50 text-emerald-100 border border-emerald-400/20 rounded-lg text-sm font-medium">{{ $repositoryName }}</span>
            </div>
            <button wire:click="changeRepository"
                    class="px-4 py-2 bg-zinc-900/50 border border-emerald-400/20 text-emerald-100 rounded-lg hover:bg-zinc-900/70 hover:border-emerald-400/30 transition-all duration-200">
                Change Repository
            </button>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            <main class="lg:col-span-2 space-y-6">
                @include('livewire.partials.commit-card')
                @include('livewire.partials.navigation-card')
                @include('livewire.partials.pace-card')
            </main>
            <aside class="space-y-6">
                @include('livewire.partials.progress-card')
                @include('livewire.partials.settings-card')
            </aside>
        </div>
    @endif
</div>