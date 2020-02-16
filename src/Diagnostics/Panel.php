<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator\Diagnostics;

use Tracy;
use Tracy\Helpers;

class Panel implements Tracy\IBarPanel
{
	/** @var array<string, array<string>> */
	private $untranslated = [];

	/** @var int */
	private $untranslatedCount = 0;

	/** @var array<string, string> */
	private $localeSources = [];


	/**
	 * Renders HTML code for custom tab.
	 */
	public function getTab(): string
	{
		return '<span title="SimpleTranslator"><img width="16" height="16" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAB9ElEQVR42qWTv2pUQRTGfzP37t3EJTHZpDBIIIIgYhVQQdKJEXwECxsbG8FXsPcBxMaUEsQHSCmi2AVfQAvBGHYxf3Tv7r0zcz6Lm2QTE0FwimHm8M2P8505B/5zucPD3dXVh5+NR08KGz4uYvgSBDLMDMyQhJm1JJuU2YurbzdfAuSHAMF9xE0dXJqgaHuPAVVMR0rBPnASgAQ4ZAamI8j3UUWOmMo8SWpkGlvw4/dq4hKyRO7EMCW+lkO2hjVmasQCd4zgT5ZESIYsITN2qppunlM4x16IZAcGjL9loMaJA8qYKENkcaKgW2TshECUNYKzLIxBhpNRxkCdjH4dGIRIHROjmMj+0Ptjv9DsEpaM/TqQORjESGUJAb9Cwjt3NqApL2TAXh3ojQLzRcbSZMGlyYJO5vlW11TJyMftc+wbD/rKMCRjoZ3TcY46JryDbp7hgSjhnZ0GmMHIPFWCGQUWnaMcViRBBNrARWAUI6HVOisDw6UalycGrQ6D4hxFq0UVAkJEoJZDyXCjcucU4MI8PL3Xp7e1y3rxAHWXuXZlgfm5OYajITKR2hNYv4dfe77Bu82TgNpYW1meurN92fP62Zv1n+9ffdRSMXNj9jxtIMjIzHlnmlaKP05N4/bGrevTHVYmpn292y8/zN7e/PQv4/wbPRMfZ4n8tOQAAAAASUVORK5CYII="><span class="tracy-label">'
			. implode(', ', array_keys($this->localeSources)) . ($this->getUntranslatedCount() > 0 ? ' <strong>(' . $this->getUntranslatedCount() . ' errors)</strong>' : '')
			. '</span></span>';
	}


	/**
	 * Renders HTML code for custom panel.
	 */
	public function getPanel(): string
	{
		$panel = [];
		if ($this->getUntranslatedCount() > 0) {
			foreach ($this->untranslated as $locale => $untranslated) {
				$panel[] = $this->renderUntranslated($locale, $untranslated);
			}
		}

		if (count($this->localeSources) > 0) {
			if (count($panel) > 0) {
				$panel[] = '<br><br>';
			}
			$panel[] = '<h2>Loaded locale sources</h2>';
			$panel[] = $this->renderLocaleSources();
		}

		return count($panel) > 0 ?
			'<h1>Missing translations: ' . $this->getUntranslatedCount() . '</h1>' .
			'<div class="nette-inner tracy-inner translator-panel" style="min-width:500px">' . implode($panel) . '</div>' .
			'<style>
				#tracy-debug .translator-panel h2 {font-size: 23px;}
			</style>' : '';
	}


	private function getUntranslatedCount(): int
	{
		if ($this->untranslatedCount === 0) {
			$count = 0;
			foreach ($this->untranslated as $untranslated) {
				$count += count(array_unique($untranslated, SORT_REGULAR));
			}
			$this->untranslatedCount = $count;
		}
		return $this->untranslatedCount;
	}


	/**
	 * @param array<string> $untranslated
	 */
	private function renderUntranslated(string $locale, array $untranslated): string
	{
		$s = '';

		foreach (array_unique($untranslated, SORT_REGULAR) as $message) {
			$s .= '<tr><td>' . htmlspecialchars($message) . '</td></tr>';
		}

		return sprintf('<table style="width:100%%"><tr><th>Untranslated messages (%s)</th></tr>%s</table>', $locale, $s);
	}


	private function renderLocaleSources(): string
	{
		$s = '';

		foreach ($this->localeSources as $locale => $source) {
			$s .= '<tr>';
			$s .= '<td>' . htmlspecialchars($locale) . '</td>';
			$s .= '<td>' . (file_exists($source) ? Helpers::editorLink($source) : $source) . '</td>';
			$s .= '</tr>';
		}

		return sprintf('<table style="width:100%%"><tr><th>Locale</th><th>Data source</th></tr>%s</table>', $s);
	}


	public function addUntranslated(string $locale, string $message): void
	{
		if (!isset($this->untranslated[$locale])) {
			$this->untranslated[$locale] = [$message];
		} else {
			$this->untranslated[$locale][] = $message;
		}
	}


	public function addLocaleFile(string $locale, string $source): void
	{
		$this->localeSources[$locale] = $source;
	}


	public static function register(): self
	{
		$panel = new self();

		Tracy\Debugger::getBar()->addPanel($panel, 'translator');

		return $panel;
	}

}
