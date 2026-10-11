<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The academy is Spanish-first: a missing es key renders as a raw "group.key" on screen
 * (it happened on the thank-you page), so every English line must have a Spanish one.
 */
class TranslationCoverageTest extends TestCase
{
    public static function langGroups(): array
    {
        return array_map(fn ($g) => [$g], [
            'thank_you', 'pages', 'pagination', 'subject', 'taxonomy', 'checkout', 'general', 'calendar',
            'identity', 'admin/general', 'courses::courses', 'meetfusion::meetfusion',
        ]);
    }

    /** @dataProvider langGroups */
    public function test_every_english_line_has_a_spanish_translation(string $group): void
    {
        $en = trans($group, [], 'en');
        $this->assertIsArray($en, "$group missing in en");

        $missing = [];
        foreach (array_keys(\Illuminate\Support\Arr::dot($en)) as $key) {
            if (!trans()->has("$group.$key", 'es', false)) {
                $missing[] = $key;
            }
        }

        $this->assertSame([], $missing, "Spanish lines missing in $group: " . implode(', ', $missing));
    }

    public function test_thank_you_page_strings_are_translated_and_use_academy_wording(): void
    {
        app()->setLocale('es');

        foreach (['thank_you', 'select_best_tutor', 'add_checkout_details', 'You_done', 'order_summary', 'grand_total'] as $key) {
            $this->assertNotSame("thank_you.$key", __("thank_you.$key"));
        }
        $this->assertStringContainsString('Horizontia', __('thank_you.thanks_detail'));
        $this->assertStringNotContainsStringIgnoringCase('tutor', __('thank_you.select_best_tutor'));
        $this->assertStringContainsString('#7', __('thank_you.order_reference', ['id' => 7]));
    }
}
