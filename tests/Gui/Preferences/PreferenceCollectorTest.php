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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreferenceCollectorTest extends TestCase
{
    private PreferenceChoiceProviderInterface&MockObject $choiceProvider;
    private ConfigContainerInterface&MockObject $configContainer;
    private PreferenceCollector $subject;
    private UserRepositoryInterface&MockObject $userRepository;

    public function testAPreferenceAboveTheOperatorsLevelIsNotEditable(): void
    {
        $operator = $this->user(42, 25);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository->method('getPreferenceRows')->willReturn([
            $this->row('show_lyrics', 'interface', 25),
            $this->row('download', 'options', 100),
        ]);

        $collected = $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator);

        $this->assertTrue($collected['interface'][0]->editable);
        $this->assertFalse($collected['options'][0]->editable);
    }

    public function testAUserSubjectExcludesTheSystemCategory(): void
    {
        $operator = $this->user(42);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository
            ->method('getPreferenceRows')
            ->willReturnCallback(function (int $userId, ?string $category, bool $excludeSystem): array {
                if ($userId !== User::INTERNAL_SYSTEM_USER_ID) {
                    $this->assertTrue($excludeSystem, 'an account never edits the system category');
                }

                return [];
            });

        $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator);
    }

    public function testDemoModeLocksEveryField(): void
    {
        $operator = $this->user(42, 100);
        $this->configContainer
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(true);
        $this->userRepository->method('getPreferenceRows')->willReturn([$this->row('show_lyrics', 'interface', 5)]);

        $collected = $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator);

        $this->assertFalse($collected['interface'][0]->editable);
    }

    public function testItAttachesTheSystemValueToEachItem(): void
    {
        $operator = $this->user(42);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository->method('getPreferenceRows')->willReturnCallback(
            fn(int $userId): array => ($userId === User::INTERNAL_SYSTEM_USER_ID)
                ? [$this->row('download', 'options', value: '0')]
                : [$this->row('download', 'options', value: '1')]
        );

        $item = $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator)['options'][0];

        $this->assertSame('1', $item->value);
        $this->assertSame('0', $item->systemValue);
    }

    public function testItGroupsByCategoryAndKeepsTheRepositoryOrder(): void
    {
        $operator = $this->user(42);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository->method('getPreferenceRows')->willReturnCallback(
            fn(int $userId): array => ($userId === User::INTERNAL_SYSTEM_USER_ID)
                ? [$this->row('download', 'options'), $this->row('show_lyrics', 'interface')]
                : [$this->row('download', 'options'), $this->row('show_lyrics', 'interface')]
        );

        $collected = $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator);

        $this->assertSame(['options', 'interface'], array_keys($collected));
        $this->assertSame('download', $collected['options'][0]->name);
        $this->assertSame('show_lyrics', $collected['interface'][0]->name);
    }

    public function testItResolvesChoiceListsForTheSubject(): void
    {
        $operator = $this->user(42);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository->method('getPreferenceRows')->willReturn([
            $this->row('transcode', 'streaming', 25, 'default', 'string'),
        ]);

        $this->choiceProvider
            ->method('find')
            ->with('transcode')
            ->willReturn(['never' => 'Never', 'default' => 'Default', 'always' => 'Always']);

        $item = $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator)['streaming'][0];

        $this->assertSame(['never' => 'Never', 'default' => 'Default', 'always' => 'Always'], $item->choices);
    }

    public function testTheServerSubjectReadsTheSharedRowAndKeepsTheSystemCategory(): void
    {
        $operator = $this->user(42);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository
            ->expects($this->once())
            ->method('getPreferenceRows')
            ->with(User::INTERNAL_SYSTEM_USER_ID, null, false)
            ->willReturn([$this->row('site_title', 'system', 100, 'Ampache', 'string')]);

        $collected = $this->subject->collect(PreferenceSubject::serverPreferences($operator), $operator);

        $this->assertArrayHasKey('system', $collected);
        $this->assertNull($collected['system'][0]->systemValue, 'the server is not compared against itself');
    }

    protected function setUp(): void
    {
        $this->userRepository  = $this->createMock(UserRepositoryInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->choiceProvider  = $this->createMock(PreferenceChoiceProviderInterface::class);
        $this->subject         = new PreferenceCollector(
            $this->userRepository,
            new PreferenceItemFactory(new PreferenceHelpCatalog(), new PluginPreferenceHelp()),
            $this->choiceProvider,
            new PreferencePrerequisiteCatalog(),
            $this->configContainer,
        );
    }

    /** @return array<string, mixed> */
    private function row(string $name, string $category, int $level = 25, ?string $value = '1', string $type = 'boolean'): array
    {
        return [
            'name' => $name,
            'description' => ucfirst($name),
            'category' => $category,
            'subcategory' => null,
            'type' => $type,
            'level' => $level,
            'value' => $value,
        ];
    }

    private function user(int $id, int $access = 100): User
    {
        $user           = $this->createMock(User::class);
        $user->access   = $access;
        $user->fullname = 'u' . $id;
        $user->method('getId')->willReturn($id);

        return $user;
    }
}
