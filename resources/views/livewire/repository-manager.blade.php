<div class="relative p-6 space-y-6">
    {{-- Repository Selection Screen --}}
    <div id="repoSelector" class="w-full max-w-6xl mx-auto py-12">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-emerald-200 to-teal-200 bg-clip-text text-transparent">
                Git Tracker</h1>
            <p class="text-emerald-100/70 mt-2">Select a repository to begin or add a new one.</p>
        </div>

        @if (session('error'))
            <div class="bg-rose-500/20 border border-rose-500/30 text-rose-300 p-4 rounded-lg mb-6 text-center">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($this->repositories as $repo)
                <div wire:key="repo-{{ $repo->id }}" wire:click="selectRepository({{ $repo->id }})"
                     class="glass-premium rounded-2xl group relative transition-all duration-300 hover:shadow-glow-emerald hover:-translate-y-1 cursor-pointer">
                    <div class="w-full h-full text-left p-6">
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg shadow-emerald-500/25 flex-shrink-0">
                                <i data-lucide="git-branch" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-lg text-emerald-50 truncate">{{ $repo->name }}</h3>
                                <p class="text-xs text-emerald-100/60 font-mono break-all">{{ $repo->path }}</p>
                            </div>
                        </div>
                    </div>
                    <button wire:click.stop="deleteRepository({{ $repo->id }})"
                            wire:confirm="Are you sure you want to remove this repository? This will not delete the files on your disk."
                            class="absolute top-3 right-3 p-2 rounded-full text-zinc-400 hover:bg-rose-500/20 hover:text-rose-400 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            @endforeach

            {{-- Add New Repo Card --}}
            <button type="button" wire:click="showAddForm"
                    class="border-2 border-dashed border-emerald-400/20 rounded-2xl text-emerald-100/60 hover:border-emerald-400/50 hover:text-emerald-100/90 transition-all duration-300 flex flex-col items-center justify-center p-6 min-h-[140px] hover:bg-emerald-500/5">
                <i data-lucide="plus-circle" class="w-8 h-8 mb-2"></i>
                <span class="font-semibold">Add New Repository</span>
            </button>
        </div>
    </div>

    {{-- Add Repo Modal --}}
    @if($isAddFormVisible)
        <div id="repoLoader" class="fixed inset-0 flex items-center justify-center p-4">
            <div wire:click="hideAddForm" class="absolute inset-0 glass-modal"></div>
            <div class="relative w-full max-w-md glass-premium shadow-2xl rounded-2xl overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-amber-500/5 pointer-events-none"></div>
                <div class="relative p-6 text-center border-b border-emerald-400/20">
                    <h2 class="text-emerald-50 text-xl font-semibold flex items-center justify-center gap-2">
                        <i data-lucide="folder-plus" class="w-5 h-5 text-emerald-400"></i> Add Repository
                    </h2>
                </div>
                <form wire:submit="addRepository" class="p-6 space-y-4">
                    <div>
                        <label for="newRepositoryPath" class="block text-sm font-medium text-emerald-100/80 mb-2">Absolute
                            Repository Path</label>
                        <input wire:model="newRepositoryPath" id="newRepositoryPath" type="text"
                               placeholder="/path/to/your/git/repository"
                               class="w-full px-4 py-3 bg-zinc-900/50 border border-emerald-400/20 rounded-lg text-emerald-50 placeholder:text-emerald-200/30 focus:border-emerald-400/50 focus:outline-none focus:ring-2 focus:ring-emerald-400/20 transition-all"/>
                        @error('newRepositoryPath') <p class="text-rose-400 text-sm mt-2">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-2">
                        <button type="button" wire:click="$dispatch('cancel-add-repository')"
                                class="flex-1 px-4 py-3 bg-zinc-900/50 border border-emerald-400/20 text-emerald-100 rounded-lg hover:bg-zinc-900/70 hover:border-emerald-400/30 transition-all duration-200">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white rounded-lg border-0 transition-all duration-200 shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40">
                            <span wire:loading.remove wire:target="addRepository">Add Repository</span>
                            <span wire:loading wire:target="addRepository">Validating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>