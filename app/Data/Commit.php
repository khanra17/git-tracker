<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Livewire\Wireable;

/**
 * A Data Transfer Object representing a single Git commit.
 *
 * Implements Livewire's Wireable interface so it can be used as a
 * public property in Livewire components.
 */
readonly class Commit implements Wireable
{
    public string $shortSha;
    public string $subject;
    public string $body;

    public function __construct(
        public string          $sha,
        public string          $message,
        public string          $authorName,
        public CarbonImmutable $authoredDate
    )
    {
        $this->shortSha = substr($this->sha, 0, 8);

        // Split message into subject and body for easier display
        $parts = explode("\n\n", $this->message, 2);
        $this->subject = $parts[0];
        $this->body = $parts[1] ?? '';
    }

    /**
     * Converts the object into a simple, serializable array for Livewire.
     * The Carbon instance is converted to an ISO 8601 string.
     */
    public function toLivewire(): array
    {
        return [
            'sha' => $this->sha,
            'message' => $this->message,
            'authorName' => $this->authorName,
            'authoredDate' => $this->authoredDate->toIso8601String(),
        ];
    }

    /**
     * Reconstructs the object from the simple array provided by Livewire.
     * The date string is parsed back into a CarbonImmutable instance.
     *
     * @param array $value
     * @return static
     */
    public static function fromLivewire($value): static
    {
        return new static(
            $value['sha'],
            $value['message'],
            $value['authorName'],
            CarbonImmutable::parse($value['authoredDate'])
        );
    }
}