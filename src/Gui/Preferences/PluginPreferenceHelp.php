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

use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Preference;
use Ampache\Plugin\PluginPreferenceHelpInterface;

/**
 * Asks a plugin to explain its own preference, found through the subcategory that carries the plugin name
 */
final class PluginPreferenceHelp
{
    public const string PLUGIN_CATEGORY = 'plugins';

    /** @var array<string, ?PluginPreferenceHelpInterface> */
    private array $plugins = [];

    /**
     * @param array{name: string, category: string, subcategory: ?string, value: mixed} $row
     */
    public function find(array $row): ?PreferenceHelp
    {
        if ($row['category'] !== self::PLUGIN_CATEGORY || $row['subcategory'] === null) {
            return null;
        }

        $plugin = $this->load($row['subcategory']);
        if (!$plugin instanceof PluginPreferenceHelpInterface) {
            return null;
        }

        // the factory blanks a secret before it reaches a template; this seam must not undo that
        $value = (Preference::isSecretName($row['name']) || $row['value'] === null) ? null : (string) $row['value'];
        $text  = $plugin->getPreferenceHelp($row['name'], $value);

        return ($text === null || $text === '') ? null : new PreferenceHelp($text);
    }

    private function load(string $name): ?PluginPreferenceHelpInterface
    {
        if (!array_key_exists($name, $this->plugins)) {
            $loaded               = new Plugin($name)->_plugin;
            $this->plugins[$name] = ($loaded instanceof PluginPreferenceHelpInterface) ? $loaded : null;
        }

        return $this->plugins[$name];
    }
}
