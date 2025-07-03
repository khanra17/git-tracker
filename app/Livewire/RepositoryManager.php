<?php

namespace App\Livewire;

use App\Models\Repository;
use App\Services\GitRepository;
use Exception;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('layouts.app')]
class RepositoryManager extends Component
{
    public bool $isAddFormVisible = false;

    #[Rule('required|string', message: 'The repository path is required.')]
    public string $newRepositoryPath = '';

    /**
     * Renders the repository selection view.
     */
    public function render()
    {
        return view('livewire.repository-manager');
    }

    /**
     * Computed property to get all repositories, ordered by name.
     * Caches the result for the duration of the request.
     */
    #[Computed(persist: true)]
    public function repositories()
    {
        return Repository::query()->orderBy('name')->get();
    }

    /**
     * Shows the modal form to add a new repository.
     */
    public function showAddForm(): void
    {
        $this->resetErrorBag();
        $this->newRepositoryPath = '';
        $this->isAddFormVisible = true;
    }

    /**
     * Hides the "Add Repository" modal.
     */
    #[On('cancel-add-repository')]
    public function hideAddForm(): void
    {
        $this->isAddFormVisible = false;
        $this->resetErrorBag();
        $this->newRepositoryPath = '';
    }

    /**
     * Validates and adds a new repository to the database.
     */
    public function addRepository(): void
    {
        $this->validate();

        try {
            $path = realpath(trim($this->newRepositoryPath));
            if (!$path) {
                throw new Exception("Path does not exist.");
            }

            // This will fail if path is not a valid git repo.
            $git = new GitRepository($path);

            // This will create or update the repo entry
            $repo = Repository::query()->updateOrCreate(
                ['path' => $path],
                [
                    'name' => $git->getName(),
                    'default_branch' => $git->getDefaultBranch()
                ]
            );

            // Redirect to the tracker page for the new repo
            $this->selectRepository($repo->id);

        } catch (Exception $e) {
            $this->addError('newRepositoryPath', "Error: {$e->getMessage()}");
        }
    }

    /**
     * Navigates to the tracker page for a given repository.
     */
    public function selectRepository(int $repositoryId)
    {
        $repo = Repository::find($repositoryId);
        if ($repo) {
            try {
                // Pre-flight check to ensure the repo is still valid before redirecting
                new GitRepository($repo->path);
                return $this->redirect(route('git-tracker', ['repository' => $repo->id]), navigate: true);
            } catch (Exception $e) {
                session()->flash('error', "Could not load '{$repo->name}': {$e->getMessage()}");
                $repo->delete(); // Clean up invalid entry
                unset($this->repositories); // Refresh the list
            }
        }
    }

    /**
     * Deletes a repository record from the database. Does not touch files on disk.
     */
    public function deleteRepository(int $id): void
    {
        Repository::destroy($id);
        unset($this->repositories); // Clear computed property cache to force a re-fetch.
    }
}