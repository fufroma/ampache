<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Api\Jellyfin;

use PHPUnit\Framework\TestCase;
use ReflectionUnionType;

class JellyfinCatalogAccessTest extends TestCase
{
    /**
     * Every by-id path that reaches a song or an album, and therefore has to ask the filter itself.
     *
     * @var list<string>
     */
    private const array GUARDED = [
        'Method/Items/ItemMethod.php',
        'Method/Items/ItemsMethod.php',
        'Method/Items/InstantMixMethod.php',
        'Method/Playback/AudioStreamMethod.php',
        'Method/Playback/PlaybackInfoMethod.php',
        'Method/Session/PlaybackReportHelper.php',
        'Method/Similar/SimilarMethod.php',
        'Method/Song/LyricsMethod.php',
    ];

    /**
     * @return list<array{0: string}>
     */
    public static function guardedPathProvider(): array
    {
        return array_map(static fn(string $path): array => [$path], self::GUARDED);
    }

    /**
     * An artist reports catalog 0, which belongs to no filter group, so accepting one here would refuse
     * every artist on an install that turned the filter on.
     */
    public function testAnArtistCannotBeAskedAboutDirectly(): void
    {
        $parameter = (new \ReflectionMethod(JellyfinCatalogAccess::class, 'allows'))->getParameters()[0];
        $type      = $parameter->getType();

        $names = ($type instanceof ReflectionUnionType)
            ? array_map(static fn(\ReflectionType $part): string => (string) $part, $type->getTypes())
            : [(string) $type];

        self::assertNotContains('Ampache\Repository\Model\Artist', $names);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('guardedPathProvider')]
    public function testEveryByIdPathAsksTheCatalogFilter(string $path): void
    {
        self::assertStringContainsString(
            'JellyfinCatalogAccess::allows(',
            (string) file_get_contents(__DIR__ . '/../../../../src/Module/Api/Jellyfin/' . $path),
            $path . ' reaches a song or album by a forgeable id without asking the caller\'s catalog filter'
        );
    }
}
