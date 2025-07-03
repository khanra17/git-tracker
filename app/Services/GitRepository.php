<?php

namespace App\Services;

use App\Data\Commit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Provides an object-oriented interface to a physical Git repository on disk.
 * This class is the single source for executing Git commands.
 */
class GitRepository
{
    private ?array $commitListCache = null;

    /**
     * @throws RuntimeException if the path is invalid or not a Git repository.
     */
    public function __construct(public readonly string $path)
    {
        $this->validatePath();
    }

    /**
     * Gets the full name of the repository (e.g., "user/repo").
     * Falls back to the directory name if the remote URL is not configured.
     */
    public function getName(): string
    {
        try {
            $url = $this->run('git config --get remote.origin.url');
            $name = preg_replace('/\.git$/', '', $url); // Remove .git suffix
            $parts = preg_split('/[:\/]/', $name); // Split by : or /
            return implode('/', array_slice($parts, -2));
        } catch (RuntimeException) {
            return basename($this->path);
        }
    }

    /**
     * Determines the primary branch name (e.g., "main" or "master").
     * @throws RuntimeException If no suitable default branch can be found.
     */
    public function getDefaultBranch(): string
    {
        try {
            // First, try to get the symbolic ref of HEAD. This is the most reliable.
            return $this->run('git symbolic-ref --short HEAD');
        } catch (RuntimeException) {
            // This fails if HEAD is detached. We must find a sensible default.
            $branchesOutput = $this->run('git branch --format="%(refname:short)"');
            $localBranches = explode("\n", trim($branchesOutput));
            $defaultBranchCandidates = ['main', 'master', 'develop', 'trunk'];

            foreach ($defaultBranchCandidates as $branch) {
                if (in_array($branch, $localBranches, true)) {
                    return $branch;
                }
            }
            throw new RuntimeException('Repository HEAD is detached and no default branch (main, master, etc.) could be found.');
        }
    }

    /**
     * Retrieves all commit SHAs on the given branch, from oldest to newest.
     */
    public function getCommitSHAs(string $branch): array
    {
        // Cache the result to avoid re-running `git rev-list` multiple times per request.
        if ($this->commitListCache === null) {
            $shas = $this->run("git rev-list --reverse {$branch}");
            $this->commitListCache = $shas ? explode("\n", trim($shas)) : [];
        }
        return $this->commitListCache;
    }

    /**
     * Fetches a single commit by its SHA and returns a Commit DTO.
     */
    public function getCommit(string $sha): ?Commit
    {
        try {
            // We use an unlikely delimiter to safely split the formatted output.
            $delimiter = '||--GIT-TRACKER--||';
            $format = "%H{$delimiter}%s{$delimiter}%an{$delimiter}%aI{$delimiter}%b";
            $output = $this->run("git show --quiet --format=\"{$format}\" " . escapeshellarg($sha));

            $parts = explode($delimiter, $output, 5);
            if (count($parts) < 4) { // Body can be empty, so at least 4 parts
                return null;
            }

            [$hash, $subject, $author, $date] = $parts;
            // The full message is subject + body
            $fullMessage = isset($parts[4]) ? $subject . "\n\n" . $parts[4] : $subject;

            return new Commit($hash, trim($fullMessage), $author, CarbonImmutable::parse($date));
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * Calculates the average number of commits per day on a branch over a given period.
     */
    public function getUpstreamPace(string $branch, int $days = 30): float
    {
        if ($days <= 0) {
            return 0.0;
        }
        try {
            // Get the number of commits in the last N days on the specified branch
            $count = (int)$this->run("git rev-list --count --since=\"{$days} days ago\" " . escapeshellarg($branch));
            return $count / $days;
        } catch (RuntimeException) {
            // If the command fails for any reason, assume no pace.
            return 0.0;
        }
    }

    /**
     * Switches the working directory to the state of a specific commit or branch.
     */
    public function checkout(string $ref): void
    {
        $this->run("git checkout --force " . escapeshellarg($ref));
    }

    /**
     * Previews the changes from a subsequent commit by applying them without committing.
     * If the cherry-pick fails, it safely resets the working directory.
     */
    public function previewNextCommit(string $nextCommitSha): void
    {
        try {
            $this->run("git cherry-pick --no-commit " . escapeshellarg($nextCommitSha));
        } catch (RuntimeException $e) {
            // Cherry-pick can fail due to merge conflicts, which is expected.
            // We abort the cherry-pick to return to a clean state.
            $this->run("git cherry-pick --abort");
            // We can choose to ignore the error or re-throw it if needed.
            // For now, ignoring it provides a better UX (the user just can't preview).
        }
    }

    /**
     * Refreshes the local repository from its 'origin' remote.
     * This fetches changes, hard resets the local branch, and cleans untracked files.
     */
    public function refreshFromRemote(string $branch): void
    {
        $this->run('git fetch origin');
        $this->checkout($branch);
        $this->run("git reset --hard origin/" . escapeshellarg($branch));
        $this->run('git clean -dfx'); // Clean untracked files and directories forcefully
        $this->commitListCache = null; // Invalidate cache after refresh
    }

    /**
     * Ensures the provided path is a valid, non-bare Git repository.
     */
    private function validatePath(): void
    {
        if (!is_dir($this->path)) {
            throw new RuntimeException("Path does not exist: {$this->path}");
        }
        if (!is_dir($this->path . '/.git')) {
            throw new RuntimeException("Not a Git repository: {$this->path}");
        }
        if ($this->run('git rev-parse --is-bare-repository') === 'true') {
            throw new RuntimeException("Bare repositories are not supported.");
        }
    }

    /**
     * Executes a process in the repository's path and handles errors.
     */
    private function run(string $command): string
    {
        $result = Process::path($this->path)->run($command);

        if (!$result->successful()) {
            throw new RuntimeException(
                "Git command failed: `{$command}`\nError: " . $result->errorOutput()
            );
        }

        return trim($result->output());
    }
}