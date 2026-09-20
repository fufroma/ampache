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

use PHPUnit\Framework\TestCase;

class PreferenceItemFactoryTest extends TestCase
{
    private PreferenceItemFactory $subject;

    public function testAnEmptySubcategoryBecomesNull(): void
    {
        $row                = $this->row();
        $row['subcategory'] = '';

        $this->assertNull($this->subject->create($row, null, null, true)->subcategory);
    }

    public function testANullValueBecomesAnEmptyString(): void
    {
        $this->assertSame('', $this->subject->create($this->row(value: null), null, null, true)->value);
    }

    public function testAnUnsetSecretIsReportedAsUnset(): void
    {
        $item = $this->subject->create($this->row(name: 'daap_pass', type: 'string', value: ''), null, null, true);

        $this->assertTrue($item->isSecret);
        $this->assertFalse($item->secretIsSet);
    }

    public function testASecretNeverCarriesItsValue(): void
    {
        $item = $this->subject->create($this->row(name: 'daap_pass', type: 'string', value: 'hunter2'), 'hunter2', null, true);

        $this->assertTrue($item->isSecret);
        $this->assertSame('', $item->value);
        $this->assertNull($item->systemValue);
        $this->assertTrue($item->secretIsSet);
    }

    public function testItAttachesHelpWhenTheCatalogueHasSome(): void
    {
        $item = $this->subject->create($this->row(), null, null, true);

        $this->assertNotNull($item->help);
        $this->assertNotSame('', $item->help->text);
    }

    public function testItAttachesNoHelpWhenTheCatalogueHasNone(): void
    {
        $this->assertNull($this->subject->create($this->row(name: 'lastfm_challenge'), null, null, true)->help);
    }

    public function testItCarriesTheRowThrough(): void
    {
        $item = $this->subject->create($this->row(), '10', null, true);

        $this->assertSame('popular_threshold', $item->name);
        $this->assertSame('Popular Threshold', $item->description);
        $this->assertSame('query', $item->subcategory);
        $this->assertSame(25, $item->level);
        $this->assertSame('25', $item->value);
        $this->assertSame('10', $item->systemValue);
        $this->assertTrue($item->editable);
        $this->assertSame(PreferenceType::INTEGER, $item->type);
    }

    public function testItKeepsTheChoicesItIsGiven(): void
    {
        $item = $this->subject->create($this->row(name: 'transcode', type: 'string'), null, ['never', 'default', 'always'], true);

        $this->assertSame(['never', 'default', 'always'], $item->choices);
    }

    public function testItLeavesTheShippedDefaultUnsetForAnUnknownPreference(): void
    {
        $item = $this->subject->create($this->row(name: 'some_plugin_option'), null, null, true);

        $this->assertNull($item->shippedDefault);
        $this->assertFalse($item->isAtShippedDefault());
    }

    public function testItReadsTheShippedDefaultFromTheDefaultsCatalogue(): void
    {
        $this->assertSame('10', $this->subject->create($this->row(), null, null, true)->shippedDefault);
    }

    protected function setUp(): void
    {
        $this->subject = new PreferenceItemFactory(new PreferenceHelpCatalog(), new PluginPreferenceHelp());
    }

    /** @return array<string, mixed> */
    private function row(string $name = 'popular_threshold', string $type = 'integer', mixed $value = '25'): array
    {
        return [
            'name' => $name,
            'description' => 'Popular Threshold',
            'category' => 'interface',
            'subcategory' => 'query',
            'type' => $type,
            'level' => 25,
            'value' => $value,
        ];
    }
}
