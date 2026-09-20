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

use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

/**
 * Answers whether a caller's catalog filter group reaches an object they named by id
 *
 * Listing endpoints filter by catalog already; anything reached by id has to ask for itself, because
 * `JellyfinId` is a plain encoding of a row id and is therefore forgeable.
 */
final class JellyfinCatalogAccess
{
    /**
     * Only an object carrying a catalog of its own can be answered here: an artist reports catalog 0,
     * which belongs to no filter group, so artists are filtered through the albums and songs beneath them.
     */
    public static function allows(Album|Song $object, User $user): bool
    {
        return Catalog::has_access($object->getCatalogId(), $user->getId());
    }
}
