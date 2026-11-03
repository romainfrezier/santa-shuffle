<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use Random\Engine\Secure;
use Random\Randomizer;

class DrawNames
{
    /** Randomized bipartite matching: finds a valid assignment or proves impossibility. */
    public function assign(array $ids, array $exclusions): array
    {
        if (count($ids) < 3 || count($ids) > 100 || count(array_unique($ids)) !== count($ids)) {
            throw ValidationException::withMessages(['draw' => 'Le tirage nécessite entre 3 et 100 participants distincts.']);
        }
        $random = new Randomizer(new Secure);
        $choices = [];
        foreach ($ids as $giver) {
            $choices[$giver] = $random->shuffleArray(array_values(array_filter($ids, fn ($recipient) => $recipient !== $giver && ! in_array($recipient, $exclusions[$giver] ?? [], true))));
        }
        $owners = [];
        $visit = function (int $giver, array &$seen) use (&$visit, &$owners, $choices): bool {
            foreach ($choices[$giver] as $recipient) {
                if (isset($seen[$recipient])) {
                    continue;
                }
                $seen[$recipient] = true;
                if (! isset($owners[$recipient]) || $visit($owners[$recipient], $seen)) {
                    $owners[$recipient] = $giver;

                    return true;
                }
            }

            return false;
        };
        foreach ($random->shuffleArray($ids) as $giver) {
            $seen = [];
            if (! $visit($giver, $seen)) {
                throw ValidationException::withMessages(['draw' => 'Ces exclusions rendent le tirage impossible. Retirez une exclusion puis réessayez.']);
            }
        }

        return array_flip($owners);
    }
}
