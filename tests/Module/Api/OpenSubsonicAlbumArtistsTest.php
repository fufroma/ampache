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

namespace Ampache\Module\Api;

use Ampache\MockeryTestCase;
use Ampache\Module\Database\database_object;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\SongRepositoryInterface;
use Override;

/**
 * Every song of an album maps to the same album artists, and a various-artists album maps every track
 * artist. Building that list once per song is what makes a large folder cost N x A instead of A.
 */
class OpenSubsonicAlbumArtistsTest extends MockeryTestCase
{
    private const int ALBUM_ID = 4093;

    public function testTheDisplayStringIsJoinedOncePerAlbum(): void
    {
        $subject = $this->subject();

        $this->assertSame(
            'Alpha, Beta',
            $subject->songDisplayAlbumArtist($this->song(callsExpected: 1))
        );
        $this->assertSame(
            'Alpha, Beta',
            $subject->songDisplayAlbumArtist($this->song(callsExpected: 0))
        );
    }

    public function testTheEntriesShareOneCopyOfTheList(): void
    {
        $subject = $this->subject();
        $entries = [];
        foreach ([$this->song(callsExpected: 1), $this->song(callsExpected: 0), $this->song(callsExpected: 0)] as $song) {
            $entries[] = ['albumArtists' => $subject->songAlbumArtists($song)];
        }

        // a copy would cost per entry; sharing is what keeps a folder of N songs at the size of one list
        $this->assertSame($entries[0]['albumArtists'], $entries[2]['albumArtists']);
    }

    public function testTheListIsBuiltOncePerAlbum(): void
    {
        $subject = $this->subject();
        $first   = $this->song(callsExpected: 1);
        $second  = $this->song(callsExpected: 0);

        $this->assertSame(
            $subject->songAlbumArtists($first),
            $subject->songAlbumArtists($second),
            'the second song of the same album must reuse the first list'
        );
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // `Artist::get_name_array_by_id()` reads this cache first, which keeps the lookup off the database
        foreach ([11 => 'Alpha', 22 => 'Beta'] as $artistId => $name) {
            database_object::add_to_cache('artist_name_array', $artistId, [
                'id' => (string) $artistId,
                'name' => $name,
                'prefix' => '',
                'basename' => $name,
            ]);
        }
    }

    private function song(int $callsExpected): Song
    {
        $song        = $this->mock(Song::class);
        $song->album = self::ALBUM_ID;
        $song->shouldReceive('get_album_artists')->times($callsExpected)->andReturn([11, 22]);

        return $song;
    }

    private function subject(): OpenSubsonic_Fields
    {
        return new OpenSubsonic_Fields(
            $this->mock(BookmarkRepositoryInterface::class),
            $this->mock(LabelRepositoryInterface::class),
            $this->mock(SongRepositoryInterface::class),
        );
    }
}
