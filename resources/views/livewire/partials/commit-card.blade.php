@php
    use App\Data\Commit;use Carbon\Carbon;
    /** @var Commit|null $commit */
    $commit = $this->currentCommit;
@endphp
<div class="glass-premium shadow-2xl rounded-2xl overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent"></div>
    <div class="relative p-6 border-b border-emerald-400/20">
        <div class="flex items-center justify-between">
            <h2 class="text-emerald-50 text-xl font-semibold flex items-center gap-2">
                <i data-lucide="git-commit" class="w-5 h-5 text-emerald-400"></i> Current Commit
            </h2>
            @if($commit)
                <span class="px-3 py-1 bg-emerald-400/10 border border-emerald-400/30 text-emerald-200 rounded-lg text-sm font-medium">
                #{{ number_format($this->currentCommitIndex + 1) }} of {{ number_format($this->totalCommits) }}
            </span>
            @endif
        </div>
    </div>
    @if($commit)
        <div class="relative p-6 space-y-4">
            <div class="flex flex-wrap items-center gap-4 text-emerald-100/80 text-sm">
                <div class="flex items-center gap-2" title="Author"><i data-lucide="user"
                                                                       class="w-4 h-4 text-amber-400"></i><span>{{ $commit->authorName }}</span>
                </div>
                <div class="flex items-center gap-2" title="Date"><i data-lucide="calendar"
                                                                     class="w-4 h-4 text-amber-400"></i><span>{{ $commit->authoredDate->format('M d, Y') }}</span>
                </div>
                <div class="flex items-center gap-2" title="Commit SHA"><i data-lucide="hash"
                                                                           class="w-4 h-4 text-amber-400"></i><code
                            class="bg-zinc-900/50 px-3 py-1 rounded-lg text-sm font-mono text-amber-200">{{ $commit->shortSha }}</code>
                </div>
            </div>
            <div class="text-emerald-50">
                <h3 class="font-semibold text-lg">{!! nl2br(e($commit->subject)) !!}</h3>
                @if($commit->body)
                    <div class="text-emerald-100/70 text-sm leading-relaxed mt-2 prose prose-invert prose-sm">{!! nl2br(e($commit->body)) !!}</div>
                @endif
            </div>
        </div>
    @else
        <div class="p-6 text-center text-amber-200/80">Could not load commit details.</div>
    @endif
</div>