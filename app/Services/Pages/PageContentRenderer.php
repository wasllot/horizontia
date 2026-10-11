<?php

namespace App\Services\Pages;

use App\Models\SitePage;
use App\Services\PageTokens;
use Modules\Courses\Models\Course;

/**
 * Turns a stored visual-editor page into what the public site prints:
 *  - [[tokens]] resolved from config/academy.php,
 *  - dynamic blocks (`data-hz-block="courses|course-info"`) rendered server-side with live data,
 *  - FAQPage JSON-LD derived from any Bootstrap accordion in the page.
 */
class PageContentRenderer
{
    /** @return array{html:string, css:string, jsonld:array<int,array>} */
    public function render(SitePage $page, ?Course $course = null): array
    {
        $html = $this->dynamicBlocks((string) $page->gjs_html, $course);
        $html = PageTokens::apply($html, $course);

        return [
            'html' => $html,
            'css' => PageHtmlSanitizer::css((string) $page->gjs_css),
            'jsonld' => array_filter([$this->faqSchema($html), $this->eventSchema($course)]),
        ];
    }

    private function dynamicBlocks(string $html, ?Course $course): string
    {
        if (!str_contains($html, 'data-hz-block')) {
            return $html;
        }
        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="hz-root">' . $html . '</div>', LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xp = new \DOMXPath($dom);
        foreach (iterator_to_array($xp->query('//*[@data-hz-block]')) as $el) {
            $attrs = [];
            foreach ($el->attributes as $a) {
                $attrs[$a->name] = $a->value;
            }
            $markup = $this->renderBlock((string) $el->getAttribute('data-hz-block'), $attrs, $course);
            $frag = $dom->createDocumentFragment();
            if ($markup !== '') {
                $frag->appendXML($this->xmlSafe($markup));
            }
            $el->parentNode->replaceChild($frag, $el);
        }

        $out = '';
        foreach (iterator_to_array($dom->getElementById('hz-root')->childNodes) as $c) {
            $out .= $dom->saveHTML($c);
        }

        return $out;
    }

    /**
     * Server-side markup of one dynamic block (what the public page prints and what the editor previews live).
     *
     * @param array<string,string> $attrs the block's `data-*` attributes as stored in the page
     */
    public function renderBlock(string $type, array $attrs, ?Course $course = null): string
    {
        return match ($type) {
            'courses' => view('pages.blocks.courses', [
                'courses' => $this->courses((int) ($attrs['data-count'] ?? 3) ?: 3, (string) ($attrs['data-category'] ?? '')),
                'heading' => (string) ($attrs['data-heading'] ?? ''),
            ])->render(),
            'course-info' => $course ? view('pages.blocks.course-info', ['course' => $course])->render() : '',
            'events' => view('pages.blocks.events', [
                'events' => app(EventsCatalog::class)->next((int) ($attrs['data-count'] ?? 3) ?: 3, trim((string) ($attrs['data-city'] ?? ''))),
                'heading' => (string) ($attrs['data-heading'] ?? ''),
            ])->render(),
            'course-dates' => $course && $course->isOnsite() ? view('pages.blocks.course-dates', [
                'course' => $course,
                'dates' => $course->sessions()->upcoming()->withCount(['enrollments as taken' => fn ($e) => $e->where('status', \Modules\Courses\Models\Enrollment::STATUSES['active'])])->get(),
                'heading' => (string) ($attrs['data-heading'] ?? ''),
            ])->render() : '',
            default => '',
        };
    }

    /** DOM fragments need well-formed XML: round-trip the rendered markup through the HTML parser. */
    private function xmlSafe(string $html): string
    {
        $d = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $d->loadHTML('<?xml encoding="UTF-8"><div id="x">' . $html . '</div>', LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $out = '';
        foreach (iterator_to_array($d->getElementById('x')->childNodes) as $c) {
            $out .= $d->saveXML($c);
        }

        return $out;
    }

    private function courses(int $count, string $category)
    {
        return Course::query()
            ->with(['thumbnail', 'pricing'])
            ->where('status', Course::STATUSES['active'])
            ->when($category !== '', fn ($q) => $q->where('category_id', (int) $category))
            ->latest('id')->limit(max(1, min(12, $count)))->get();
    }

    /** Event structured data for the dates of an in-person course (pages attached to it). */
    private function eventSchema(?Course $course): ?array
    {
        if (!$course || !$course->isOnsite()) {
            return null;
        }
        $items = app(EventsCatalog::class)->eventSchema($course, $course->sessions()->upcoming()->get());

        return $items ? ['@context' => 'https://schema.org', '@graph' => $items] : null;
    }

    /** FAQPage structured data from `.accordion-item` > `.accordion-button` + `.accordion-body`. */
    private function faqSchema(string $html): ?array
    {
        if (!str_contains($html, 'accordion-button')) {
            return null;
        }
        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xp = new \DOMXPath($dom);
        $items = [];
        foreach ($xp->query('//*[contains(concat(" ", normalize-space(@class), " "), " accordion-item ")]') as $item) {
            $q = $xp->query('.//*[contains(concat(" ", normalize-space(@class), " "), " accordion-button ")]', $item)->item(0);
            $a = $xp->query('.//*[contains(concat(" ", normalize-space(@class), " "), " accordion-body ")]', $item)->item(0);
            if ($q && $a && trim($q->textContent) !== '') {
                $items[] = ['@type' => 'Question', 'name' => trim($q->textContent), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(preg_replace('/\s+/', ' ', $a->textContent))]];
            }
        }

        return $items ? ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items] : null;
    }
}
