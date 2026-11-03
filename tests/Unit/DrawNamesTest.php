<?php

namespace Tests\Unit;

use App\Services\DrawNames;
use PHPUnit\Framework\TestCase;

class DrawNamesTest extends TestCase
{
    public function test_every_person_gives_and_receives_once_across_sizes(): void
    {
        $draw = new DrawNames;
        foreach ([3, 4, 5, 20, 100] as $size) {
            for ($round = 0; $round < 20; $round++) {
                $ids = range(1, $size);
                $pairs = $draw->assign($ids, [1 => [2]]);
                $this->assertCount($size, $pairs);
                $recipients = array_values($pairs);
                sort($recipients);
                $this->assertSame($ids, $recipients);
                foreach ($pairs as $giver => $recipient) {
                    $this->assertNotSame($giver, $recipient);
                }
                $this->assertNotSame(2, $pairs[1]);
            }
        }
    }

    public function test_constrained_matching_finds_the_only_solution(): void
    {
        $pairs = (new DrawNames)->assign([1, 2, 3], [1 => [3], 2 => [1], 3 => [2]]);
        ksort($pairs);
        $this->assertSame([1 => 2, 2 => 3, 3 => 1], $pairs);
    }
}
