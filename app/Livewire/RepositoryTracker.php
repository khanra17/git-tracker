<?php

namespace App\Livewire;

use App\Data\Commit;
use App\Models\Repository;
use App\Services\GitRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('layouts.app')]
class RepositoryTracker extends Component
{
    #[Locked]
    public Repository $repository;

    #[Locked]
    public string $repositoryName;

    // --- State Properties ---
    public ?Commit $currentCommit = null;
    public ?Commit $targetCommit = null;
    public int $currentCommitIndex = 0;
    public int $targetCommitIndex = 0;
    public int $totalCommits = 0;

    // --- Settings Form Properties ---
    #[Rule('required|string')]
    public string $targetCommitReferenceInput = 'latest';

    #[Rule('required|integer|min:1')]
    public int $idealReviewPaceInput = 20;

    #[Rule('required|in:7-days,30-days,all-time')]
    public string $actualPacePeriodInput = '30-days';


    /**
     * The mount method is called only once when the component is first instantiated.
     * It's responsible for setting up the initial state.
     * @throws Exception
     */
    public function mount(Repository $repository): void
    {
        $this->repository = $repository;

        // The GitRepository service will now be lazy-loaded via the computed property.
        // This pre-flights that the path is valid on initial mount.
        $this->repositoryName = $this->git->getName();

        $this->syncStateFromRepository();
    }

    /**
     * Define the GitRepository service as a computed property.
     * This ensures it is instantiated on every request and cached for the duration
     * of that request, solving the uninitialized property issue.
     */
    #[Computed]
    public function git(): GitRepository
    {
        return new GitRepository($this->repository->path);
    }

    /**
     * Main render method.
     */
    public function render()
    {
        /// Dispatch chart data on every render if the component is in a valid state
        if ($this->repository) {
            $this->dispatch('updatePaceChart', data: $this->chartData);
        }

        return view('livewire.repository-tracker');
    }

    /**
     * Navigates the user back to the repository selection screen.
     */
    public function changeRepository()
    {
        return $this->redirect(route('home'), navigate: true);
    }

    /**
     * Advances the review to the next commit in the history.
     */
    public function stepForward(): void
    {
        if (!$this->canStepForward) {
            return;
        }

        $allShas = $this->git->getCommitSHAs($this->repository->default_branch);
        $nextCommitSha = $allShas[$this->currentCommitIndex + 1] ?? null;

        if (!$nextCommitSha) return;

        // Persist the new progress in the database within a transaction
        DB::transaction(function () use ($nextCommitSha) {
            $this->repository->last_reviewed_commit_sha = $nextCommitSha;
            $this->repository->save();

            $this->repository->reviewLogs()->create(['commit_sha' => $nextCommitSha]);
        });

        // Refresh the component's state from the newly saved data
        $this->syncStateFromRepository();
    }

    /**
     * Moves the review to the previous commit in the history.
     */
    public function stepBackward(): void
    {
        if (!$this->canStepBackward) {
            return;
        }

        $allShas = $this->git->getCommitSHAs($this->repository->default_branch);
        $previousCommitSha = $allShas[$this->currentCommitIndex - 1] ?? null;

        if (!$previousCommitSha) {
            return;
        }

        // Persist the new progress in the database within a transaction
        DB::transaction(function () use ($previousCommitSha) {
            $this->repository->last_reviewed_commit_sha = $previousCommitSha;
            $this->repository->save();

            // Delete the last review log to correct the pace calculation
            $this->repository->reviewLogs()->latest('created_at')->first()?->delete();
        });

        // Refresh the component's state from the newly saved data
        $this->syncStateFromRepository();
    }

    /**
     * Fetches the latest changes from the remote Git repository and re-syncs the state.
     */
    public function refreshRepository(): void
    {
        $lastReviewedSha = $this->repository->last_reviewed_commit_sha;

        try {
            // Now $this->git is available here thanks to the computed property
            $this->git->refreshFromRemote($this->repository->default_branch);

            // After refresh, update the model if the default branch has changed.
            $newBranchName = $this->git->getDefaultBranch();
            if ($newBranchName !== $this->repository->default_branch) {
                $this->repository->default_branch = $newBranchName;
                $this->repository->save();
            }

            // Restore the last reviewed SHA, which might have been wiped by the hard reset
            $this->repository->last_reviewed_commit_sha = $lastReviewedSha;

        } catch (Exception $e) {
            $this->addError('state', "Refresh failed: {$e->getMessage()}");
            return;
        }

        // Fully re-initialize the component state after the refresh
        $this->syncStateFromRepository();
    }

    /**
     * Updates the repository settings based on form input.
     */
    public function updateSettings(): void
    {
        if (!$this->repository) return;

        try {
            $validated = $this->validate([
                'targetCommitReferenceInput' => 'required|string',
                'idealReviewPaceInput' => 'required|integer|min:1',
                'actualPacePeriodInput' => 'required|in:7-days,30-days,all-time',
            ]);
        } catch (ValidationException $e) {
            $this->addError('settings', array_values($e->errors())[0][0]);
            return;
        }

        // Validate that the commit reference is valid
        if (!$this->resolveCommitFromReference($validated['targetCommitReferenceInput'])) {
            $this->addError('settings', "Commit or reference '{$validated['targetCommitReferenceInput']}' not found.");
            return;
        }

        // Update the repository model and save
        $this->repository->target_commit_reference = $validated['targetCommitReferenceInput'];
        $this->repository->ideal_review_pace = $validated['idealReviewPaceInput'];
        $this->repository->actual_pace_period = $validated['actualPacePeriodInput'];
        $this->repository->save();

        // Sync state to reflect the new settings immediately
        $this->syncStateFromRepository();
    }

    // --- COMPUTED PROPERTIES FOR THE VIEW ---

    #[Computed]
    public function progressPercentage(): float
    {
        if ($this->targetCommitIndex <= 0) {
            return $this->currentCommitIndex > 0 ? 100.0 : 0.0;
        }
        $percentage = (($this->currentCommitIndex + 1) / ($this->targetCommitIndex + 1)) * 100;
        return min($percentage, 100.0);
    }

    #[Computed]
    public function canStepForward(): bool
    {
        return $this->currentCommitIndex < ($this->totalCommits - 1);
    }

    #[Computed]
    public function canStepBackward(): bool
    {
        return $this->currentCommitIndex > 0;
    }

    #[Computed]
    public function nextCommit(): ?Commit
    {
        if (!$this->canStepForward) {
            return null;
        }
        $allShas = $this->git->getCommitSHAs($this->repository->default_branch);
        $nextSha = $allShas[$this->currentCommitIndex + 1] ?? null;

        return $nextSha ? $this->git->getCommit($nextSha) : null;
    }

    /**
     * Calculates all pace and projection data for the view.
     */
    #[Computed]
    public function paceData(): array
    {
        if (!$this->currentCommit || !$this->targetCommit || $this->targetCommitIndex < $this->currentCommitIndex) {
            return [];
        }

        $commitsRemaining = $this->targetCommitIndex - $this->currentCommitIndex;
        if ($commitsRemaining <= 0) {
            return [];
        }

        // --- Paces ---
        $idealPace = (float)$this->idealReviewPaceInput;
        $actualPace = $this->repository->getActualPace($this->actualPacePeriodInput);

        $isTargetLatest = strtolower(trim($this->repository->target_commit_reference)) === 'latest';
        $upstreamPace = $isTargetLatest ? $this->git->getUpstreamPace($this->repository->default_branch, 30) : 0.0;

        // --- Projections ---
        $idealFinishDate = null;
        if ($idealPace > 0) {
            $daysToFinish = $commitsRemaining / $idealPace;
            $idealFinishDate = Carbon::now()->addDays((int)ceil($daysToFinish));
        }

        $actualFinishDate = null;
        if ($actualPace > 0) {
            $daysToFinish = $commitsRemaining / $actualPace;
            $actualFinishDate = Carbon::now()->addDays((int)ceil($daysToFinish));
        }

        // --- Catch-up Projections ---
        $idealCatchUpDate = null;
        $actualCatchUpDate = null;
        if ($isTargetLatest) {
            $netIdealPace = $idealPace - $upstreamPace;
            if ($netIdealPace > 0) {
                $daysToCatchUp = $commitsRemaining / $netIdealPace;
                $idealCatchUpDate = Carbon::now()->addDays((int)ceil($daysToCatchUp));
            }

            $netActualPace = $actualPace - $upstreamPace;
            if ($netActualPace > 0) {
                $daysToCatchUp = $commitsRemaining / $netActualPace;
                $actualCatchUpDate = Carbon::now()->addDays((int)ceil($daysToCatchUp));
            }
        }

        return [
            'commitsRemaining' => $commitsRemaining,
            'idealPace' => $idealPace,
            'actualPace' => $actualPace,
            'upstreamPace' => $upstreamPace,
            'idealFinishDate' => $idealFinishDate,
            'actualFinishDate' => $actualFinishDate,
            'idealCatchUpDate' => $idealCatchUpDate,
            'actualCatchUpDate' => $actualCatchUpDate,
            'isTargetLatest' => $isTargetLatest,
        ];
    }

    /**
     * Generates data series for the pace projection chart.
     */
    #[Computed]
    public function chartData(): array
    {
        $paceData = $this->paceData;

        if (empty($paceData) || $paceData['commitsRemaining'] <= 0) {
            return ['datasets' => []];
        }

        $datasets = [];
        $initialCommits = $paceData['commitsRemaining'];
        $isTargetLatest = $paceData['isTargetLatest'];
        $upstreamPace = $paceData['upstreamPace'];

        $generateSeries = function (float $pace, int $initialCommits, Carbon $startDate, int $limitInYears = 5) {
            if ($pace <= 0) return [];
            $series = [['x' => $startDate->toIso8601String(), 'y' => $initialCommits]];
            $remaining = $initialCommits;
            $currentDate = $startDate->copy();

            while ($remaining > 0) {
                $currentDate->addDay();
                $remaining -= $pace;
                $series[] = ['x' => $currentDate->toIso8601String(), 'y' => max(0, $remaining)];
                if ($currentDate->diffInYears($startDate) >= $limitInYears) break;
            }
            return $series;
        };

        // Ideal Pace Series
        $idealPace = $paceData['idealPace'];
        $netIdealPace = $isTargetLatest ? $idealPace - $upstreamPace : $idealPace;
        if ($netIdealPace > 0) {
            $datasets[] = [
                'label' => 'Ideal Pace',
                'data' => $generateSeries($netIdealPace, $initialCommits, Carbon::now()),
                'borderColor' => 'rgb(56, 248, 189)',
                'tension' => 0.1, 'borderWidth' => 2, 'fill' => true,
            ];
        }

        // Actual Pace Series
        $actualPace = $paceData['actualPace'];
        $netActualPace = $isTargetLatest ? $actualPace - $upstreamPace : $actualPace;
        if ($netActualPace > 0) {
            $datasets[] = [
                'label' => 'Actual Pace',
                'data' => $generateSeries($netActualPace, $initialCommits, Carbon::now()),
                'borderColor' => 'rgb(14, 165, 233)',
                'tension' => 0.1, 'borderWidth' => 3, 'fill' => false, 'borderDash' => [5, 5],
            ];
        }

        return ['datasets' => $datasets];
    }


    // --- PRIVATE HELPER METHODS ---

    /**
     * Initializes or refreshes the component's state from the database and Git repository.
     * This is the single source of truth for the component's state.
     */
    private function syncStateFromRepository(): void
    {
        if (!$this->repository->default_branch) {
            $this->addError('state', 'Repository is not configured with a default branch.');
            return;
        }

        try {
            // Invalidate the cache of the Git service to ensure we get fresh data
            unset($this->git);

            // Load all commits from the repository
            $allShas = $this->git->getCommitSHAs($this->repository->default_branch);
            $this->totalCommits = count($allShas);

            // Determine the current commit based on the last one reviewed
            $lastReviewedSha = $this->repository->last_reviewed_commit_sha;
            $currentSha = ($lastReviewedSha && in_array($lastReviewedSha, $allShas)) ? $lastReviewedSha : ($allShas[0] ?? null);

            if ($currentSha) {
                $this->currentCommit = $this->git->getCommit($currentSha);
                $this->currentCommitIndex = array_search($currentSha, $allShas) ?: 0;
            } else {
                $this->currentCommit = null;
                $this->currentCommitIndex = 0;
            }

            // Determine the target commit
            $this->targetCommit = $this->resolveCommitFromReference($this->repository->target_commit_reference);
            $this->targetCommitIndex = $this->targetCommit ? (array_search($this->targetCommit->sha, $allShas) ?: 0) : 0;

            // Sync settings from the model to the form inputs
            $this->targetCommitReferenceInput = $this->repository->target_commit_reference;
            $this->idealReviewPaceInput = $this->repository->ideal_review_pace;
            $this->actualPacePeriodInput = $this->repository->actual_pace_period;

            // Finally, update the physical files on disk to match the current state.
            $this->performCheckout();
            $this->resetErrorBag();

        } catch (Exception $e) {
            $this->addError('state', $e->getMessage());
        }
    }

    /**
     * Updates the physical file state of the repository based on the component's current state.
     */
    private function performCheckout(): void
    {
        if (!$this->currentCommit) return;

        $this->git->checkout($this->currentCommit->sha);

        if ($this->nextCommit) {
            $this->git->previewNextCommit($this->nextCommit->sha);
        }
    }

    /**
     * Finds a commit DTO from a given reference (e.g., 'latest', a tag, or a SHA).
     */
    private function resolveCommitFromReference(string $reference): ?Commit
    {
        $ref = strtolower(trim($reference));
        if ($ref === 'latest' || $ref === '') {
            $allShas = $this->git->getCommitSHAs($this->repository->default_branch);
            $lastSha = end($allShas);
            return $lastSha ? $this->git->getCommit($lastSha) : null;
        }

        // Otherwise, assume it's a SHA or tag that Git can resolve
        return $this->git->getCommit($reference);
    }
}