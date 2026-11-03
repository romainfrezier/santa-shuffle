<?php

declare(strict_types=1);

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
            throw ValidationException::withMessages(['draw' => __('messages.draw_size')]);
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
                throw ValidationException::withMessages(['draw' => __('messages.draw_impossible')]);
            }
        }

        return array_flip($owners);
    }
}
