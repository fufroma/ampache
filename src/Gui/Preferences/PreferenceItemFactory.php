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

namespace Ampache\Gui\Preferences;

use Ampache\Module\System\Preference;

/**
 * Turns a `UserRepository::getPreferenceRows()` row into a `PreferenceItem`.
 */
final class PreferenceItemFactory
{
    public function __construct(
        private PreferenceHelpCatalog $helpCatalog,
        private PluginPreferenceHelp $pluginHelp,
    ) {}

    /**
     * @param array{name: string, description: string, category: string, subcategory: ?string, type: string, level: int, value: mixed} $row
     * @param ?string $systemValue the `user = -1` value, null when the subject is the system itself
     * @param ?array<array-key, string> $choices from `PreferenceChoiceProvider`
     */
    public function create(array $row, ?string $systemValue, ?array $choices, bool $editable, ?string $warning = null): PreferenceItem
    {
        $name     = $row['name'];
        $value    = (string) ($row['value'] ?? '');
        $isSecret = Preference::isSecretName($name);

        return new PreferenceItem(
            name: $name,
            description: $row['description'],
            type: PreferenceType::fromDatabase($row['type']),
            subcategory: ($row['subcategory'] === null || $row['subcategory'] === '') ? null : $row['subcategory'],
            level: $row['level'],
            // a secret is write-only: it must not reach a template that could echo it
            value: $isSecret ? '' : $value,
            shippedDefault: Preference::DEFAULTS[$name][0] ?? null,
            systemValue: $isSecret ? null : $systemValue,
            choices: $choices,
            editable: $editable,
            isSecret: $isSecret,
            secretIsSet: $isSecret && $value !== '',
            // a plugin knows its own settings better than the shipped catalogue does
            help: $this->pluginHelp->find($row) ?? $this->helpCatalog->find($name),
            warning: $warning,
        );
    }
}
