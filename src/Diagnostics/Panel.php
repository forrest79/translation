<?php declare(strict_types=1);

namespace Forrest79\SimpleTranslator\Diagnostics;

use Tracy;
use Tracy\Helpers;

class Panel implements Tracy\IBarPanel
{
	/** @var array */
	private $untranslated = [];

	/** @var int */
	private $untranslatedCount;

	/** @var array */
	private $localeSources = [];


	/**
	 * Renders HTML code for custom tab.
	 */
	public function getTab(): string
	{
		return '<span title="Translation"><img width="16px" height="16px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAAXNSR0IArs4c6QAAAAZiS0dEAP8A/wD/oL2nkwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB90DExAuL9uIsPUAAAQ2SURBVFjD7Ze9bxxVFMV/583skjgOIXEaKCNFAgmQ+ExS8qEUCEHH30BBScG/QM1fEmgoAAkoAigSKKFASElHESEFbGcdez7uoXhvZteW7ewmQjRMsTs7s/Pufeece+4d+P/4jw8ddvHq1avv2b5mm02Lr9Y7/gpQBCFDD5YhgsAoRLhHMtHnRYNAAb3yt92//9y3v3x+MFY6LIGIuGYbKedngzAGsPK5Kb8BGRAu9xiuL+zQ6NphsQ5NYB7YhA1jQCObyOHG/3sMlpM0hmGNcmXh7w9PYL5wfsphbCMPaOR7BipEJahV0FHZvZ2D+1i2qY+gYERCZTGZzLdFkLNIht/v72AHGM5Pa85MqowUc4p81PaPo2CgIa9RxOZ8jowDZm0WXo2oBdttN8ihIABC5dPLI7BIQQA5prEGIeYdbnVtSSpD3WCaCCqpMB8jXSshEBFEROZ9TCTvepC/be63HWA2ppNRoJttP1flvkp5BArmNBQl5zSIMJtNRxJEwHqVOF1XGLHVdSQp+0CpAi1UytJVYHteBQ7cm7AgIKnAH7CWhBDrVRqfud91UERLFNPyihQMwaOUIAI5I9D0PU2Xz9frCjs4WVVUMhJstR2SwaUWgvx7VQqyG2b4IoIoO9xsWipllJ6sK1JB7Exd4whmbUcYlCVcRKrVq0DSqCIVRlPZ4SCqO7MHC0Ey1Epiu+k5XafMvbRaFXCgdBxBOIgw202/TyNBtmuHibJRYba6LjuAsgjjCA3UxwWPCEwanRCJ7bYd/eHspFootdwhdvqeJsyegyaCSVK271US2E9BNp8otbTd9STlB89NJvRFmJREJ4K7e0El2Op6NiY1x7GQlpoawihgq+lIZF2s1xWdu9GAcO6Yp6rcE4OcwGDGR7nhUglYRjL32pbOQdMH61VCzgiFc8eUs+2cTIkuoI2MmH20D9TLzEzuTafgwtp0dNXO5FlhsOvSh23zzBPTBfDm4ny0BBBWAInWpSmVmSDzqrEko0TpBwct9W+xGgKL41hWoMeJB/LQsa/JlOYkVDrmAHlpwnF0M6of2gdIC7vUvDUNk1HMbwVRbmo+jA3tOlaYByTYbRJdiG1E3UFFg2IvLxqm67qR1yg71kLflDXvwhHhsxtbSycw2xWffXiPP+/NmFTiGyX21t9m99RbWDCZ1Fy5dAlJeXyz6aOfz48Mzli6aPTp8rMXz/585UVeun7z4WW418Kll0/w9Pk1/t5JTHdh2otpElMl1PX8eP06fdus9ObhQ2g4VBrVxpvvdl/Ovmgacev2Dnf+2GXnxi6zGw/KMJrNJUXPS6fXmCaNw+d8GNV8KHXgp87xyg83tVQCALPvLlvCVUK/3tnh9t3mkw8+/u3Tx3kNu/H6C7z6063lnLBU4dd98NHzF9Z47eKpE4/7Hngw+LHHzveX3zmAyBv/xsvpP+li/lm3bxkuAAAAAElFTkSuQmCC" />'
			. implode(', ', array_keys($this->localeSources)) . ($this->untranslated ? ' <strong>(' . $this->getUntranslatedCount() . ' errors)</strong>' : '')
			. '</span>';
	}


	/**
	 * Renders HTML code for custom panel.
	 */
	public function getPanel(): string
	{
		$panel = [];
		if ($this->untranslated) {
			foreach ($this->untranslated as $locale => $untranslated) {
				$panel[] = $this->renderUntranslated($locale, $untranslated);
			}
		}

		if ($this->localeSources) {
			if ($panel) {
				$panel[] = '<br><br>';
			}
			$panel[] = '<h2>Loaded locale sources</h2>';
			$panel[] = $this->renderLocaleSources();
		}

		return $panel ?
			'<h1>Missing translations: ' . $this->getUntranslatedCount() . '</h1>' .
			'<div class="nette-inner tracy-inner translator-panel" style="min-width:500px">' . implode($panel) . '</div>' .
			'<style>
				#tracy-debug .translator-panel h2 {font-size: 23px;}
			</style>' : '';
	}


	private function getUntranslatedCount(): int
	{
		if ($this->untranslatedCount === NULL) {
			$count = 0;
			foreach ($this->untranslated as $untranslated) {
				$count += count(array_unique($untranslated, SORT_REGULAR));
			}
			$this->untranslatedCount = $count;
		}
		return $this->untranslatedCount;
	}


	private function renderUntranslated(string $locale, array $untranslated): string
	{
		$s = '';

		foreach ($unique = array_unique($untranslated, SORT_REGULAR) as $message) {
			$s .= '<tr><td>' . htmlSpecialChars($message) . '</td></tr>';
		}

		return '<table style="width:100%"><tr><th>Untranslated messages (' . $locale . ')</th></tr>' . $s . '</table>';
	}


	private function renderLocaleSources(): string
	{
		$s = '';

		foreach ($this->localeSources as $locale => $source) {
			$s .= '<tr>';
			$s .= '<td>' . htmlSpecialChars($locale) . '</td>';
			$s .= '<td>' . (file_exists($source) ? Helpers::editorLink($source) : $source) . '</td>';
			$s .= '</tr>';
		}

		return '<table style="width:100%"><tr><th>Locale</th><th>Data source</th></tr>' . $s . '</table>';
	}


	public function addUntranslated(string $locale, string $message): void
	{
		$this->untranslated[$locale][] = $message;
	}


	public function addLocaleFile(string $locale, string $source): void
	{
		$this->localeSources[$locale] = $source;
	}


	public static function register(): self
	{
		$panel = new static;

		Tracy\Debugger::getBar()->addPanel($panel, 'translator');

		return $panel;
	}

}
