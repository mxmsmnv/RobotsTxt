<?php

/**
 * RobotsTxt - ProcessWire Module
 *
 * Manage robots.txt file through the admin UI.
 * Includes a visual editor, preset rules library, and parsed rules viewer.
 *
 * @author  Maxim Alex <maxim@smnv.org>
 * @version 1.0.0
 */

if(!defined("PROCESSWIRE")) die();

class RobotsTxt extends Process implements Module {

	public static function getModuleInfo() {
		return [
			'title'      => 'RobotsTxt',
			'summary'    => 'Manage robots.txt file through the admin UI with presets and visual editor.',
			'version'    => 100,
			'author'     => 'Maxim Alex',
			'icon'       => 'cog',
			'requires'   => ['ProcessWire>=3.0.200', 'PHP>=8.2'],
			'permission' => 'robots-manager',
			'permissions' => [
				'robots-manager' => 'Manage robots.txt file',
			],
			'page' => [
				'name'   => 'robots-txt',
				'parent' => 'setup',
				'title'  => 'Robots.txt',
			],
		];
	}

	// -----------------------------------------------------------------------
	// Preset rules library
	// -----------------------------------------------------------------------

	protected function getPresets(): array {
		return [
			'allow_all' => [
				'label'       => 'Allow all crawlers',
				'description' => 'Open the entire site to all bots — the standard baseline for a live site',
				'rules'       => "User-agent: *\nAllow: /",
			],
			'block_all' => [
				'label'       => 'Block all crawlers',
				'description' => 'Deny every bot completely — useful while a site is in development',
				'rules'       => "User-agent: *\nDisallow: /",
			],
			'block_admin' => [
				'label'       => 'Hide admin panel',
				'description' => 'Keeps the CMS back-end out of search engine indexes',
				'rules'       => "User-agent: *\nDisallow: /admin/\nDisallow: /processwire/",
			],
			'block_assets' => [
				'label'       => 'Block uploaded file assets',
				'description' => 'Prevents the uploaded files folder from being crawled',
				'rules'       => "User-agent: *\nDisallow: /site/assets/files/",
			],
			'block_ai_bots' => [
				'label'       => 'Block AI training bots',
				'description' => 'Blocks GPTBot, CCBot, Anthropic, Google-Extended and other scrapers used to train AI models',
				'rules'       => "User-agent: GPTBot\nDisallow: /\n\nUser-agent: CCBot\nDisallow: /\n\nUser-agent: anthropic-ai\nDisallow: /\n\nUser-agent: Google-Extended\nDisallow: /\n\nUser-agent: Omgilibot\nDisallow: /\n\nUser-agent: FacebookBot\nDisallow: /",
			],
			'sitemap' => [
				'label'       => 'Add Sitemap reference',
				'description' => 'Tells search engines where your sitemap lives — replace the example URL before saving',
				'rules'       => "Sitemap: https://example.com/sitemap.xml",
			],
			'crawl_delay' => [
				'label'       => 'Set Crawl-delay',
				'description' => 'Asks crawlers to wait N seconds between requests — reduces server load',
				'rules'       => "User-agent: *\nCrawl-delay: 10",
			],
			'block_query_strings' => [
				'label'       => 'Block URL query strings',
				'description' => 'Stops duplicate pages from being indexed via URL parameters like ?page=2',
				'rules'       => "User-agent: *\nDisallow: /*?*",
			],
			'google_only' => [
				'label'       => 'Google only',
				'description' => 'Allows only Googlebot; blocks all other crawlers',
				'rules'       => "User-agent: Googlebot\nAllow: /\n\nUser-agent: *\nDisallow: /",
			],
			'bing_only' => [
				'label'       => 'Bing only',
				'description' => 'Allows only Bingbot; blocks all other crawlers',
				'rules'       => "User-agent: Bingbot\nAllow: /\n\nUser-agent: *\nDisallow: /",
			],
		];
	}

	// -----------------------------------------------------------------------
	// File helpers
	// -----------------------------------------------------------------------

	/**
	 * Returns the absolute path to robots.txt.
	 * $config->paths->root is the directory that contains /site and /wire,
	 * which is exactly where robots.txt must be placed.
	 */
	protected function getRobotsPath(): string {
		return rtrim($this->config->paths->root, '/') . '/robots.txt';
	}

	protected function readFile(): string {
		$path = $this->getRobotsPath();
		if (!is_file($path) || !is_readable($path)) return '';
		$content = file_get_contents($path);
		return $content !== false ? $content : '';
	}

	protected function writeFile(string $content): bool {
		$path = $this->getRobotsPath();
		$dir  = dirname($path);

		// Can write if: directory is writable (new file) OR existing file is writable
		$canWrite = is_writable($dir) || (file_exists($path) && is_writable($path));
		if (!$canWrite) return false;

		return file_put_contents($path, $content, LOCK_EX) !== false;
	}

	protected function normalizeContent(string $raw): string {
		$raw   = str_replace(["\r\n", "\r"], "\n", $raw);
		$lines = array_map('rtrim', explode("\n", $raw));
		return trim(implode("\n", $lines)) . "\n";
	}

	// -----------------------------------------------------------------------
	// Main page — handles both GET and POST
	// -----------------------------------------------------------------------

	public function ___execute() {

		// Handle POST (save)
		if ($this->input->post('rm_save')) {
			$this->session->CSRF->validate();
			$content = $this->normalizeContent(
				(string) $this->input->post->textarea('robots_content')
			);
			if ($this->writeFile($content)) {
				$this->message('robots.txt saved successfully.');
			} else {
				$this->error('Could not write the file. Check write permissions on: ' . dirname($this->getRobotsPath()));
			}
			$this->session->redirect('./');
			return '';
		}

		// Handle GET (render page)
		$path    = $this->getRobotsPath();
		$exists  = file_exists($path);
		$content = $exists ? $this->readFile() : '';

		$out  = $this->renderStyles();

		// Status bar
		if ($exists) {
			$size  = number_format(filesize($path));
			$mtime = date('Y-m-d H:i', filemtime($path));
			$out .= "<div class='uk-alert uk-alert-success'>"
				. "<strong>&#10003; File found:</strong> <code>{$path}</code>"
				. " &nbsp;&middot;&nbsp; {$size}&nbsp;bytes"
				. " &nbsp;&middot;&nbsp; Last modified: {$mtime}"
				. "</div>";
		} else {
			$out .= "<div class='uk-alert uk-alert-warning'>"
				. "<strong>! File does not exist yet:</strong> <code>{$path}</code>"
				. " &mdash; paste your rules below and click <strong>Save</strong> to create it."
				. "</div>";
		}

		// Two-column layout
		$out .= "<div id='rm-layout'>";

		// ---- Sidebar: presets ----
		$out .= "<aside id='rm-sidebar'>"
			. "<p class='uk-text-muted uk-text-small uk-margin-small-bottom'><strong>PRESETS</strong> &mdash; click to add</p>"
			. "<ul class='uk-list uk-list-divider uk-margin-remove'>";

		foreach ($this->getPresets() as $preset) {
			$rulesEsc = htmlspecialchars($preset['rules'], ENT_QUOTES);
			$out .= "<li class='rm-preset' data-rules='{$rulesEsc}'>"
				. "<span class='rm-preset__label'>{$preset['label']}</span>"
				. "<br><span class='uk-text-small uk-text-muted'>{$preset['description']}</span>"
				. "</li>";
		}

		$out .= "</ul></aside>"; // .rm-sidebar

		// ---- Main: editor ----
		$out .= "<div id='rm-main'>";

		$out .= "<form id='rm-form' method='post' action='./'>"
			. $this->session->CSRF->renderInput()
			. "<textarea id='rm-editor' name='robots_content' spellcheck='false'>"
			. htmlspecialchars($content)
			. "</textarea>"
			. "<div class='uk-margin-small-top'>"
			. "<button type='submit' name='rm_save' value='1' class='uk-button uk-button-primary'>Save</button> ";

		if ($exists) {
			$fileUrl = $this->config->urls->root . 'robots.txt';
			$out .= "<a href='{$fileUrl}' target='_blank' class='uk-button uk-button-default'>View file &nearr;</a> ";
		}

		$out .= "<button type='button' id='rm-clear-btn' class='uk-button uk-button-danger uk-align-right'>Clear editor</button>"
			. "</div>" // buttons row
			. "</form>";

		// Parsed rules overview
		if (trim($content) !== '') {
			$out .= $this->renderParsedRules($content);
		}

		$out .= "</div>"; // #rm-main
		$out .= "</div>"; // #rm-layout

		$out .= $this->renderScripts();

		return $out;
	}

	// -----------------------------------------------------------------------
	// Parsed rules overview
	// -----------------------------------------------------------------------

	protected function renderParsedRules(string $content): string {
		$blocks = $this->parseRobots($content);
		if (empty($blocks)) return '';

		$out = "<div class='rm-section'>"
			. "<h2 class='rm-section__title'>Rules overview</h2>"
			. "<div class='rm-rules'>";

		foreach ($blocks as $block) {
			if ($block['type'] === 'directive') {
				$agent      = htmlspecialchars($block['agent']);
				$agentLabel = ($block['agent'] === '*') ? 'All crawlers (*)' : $agent;

				$out .= "<div class='rm-rule-block'>"
					. "<div class='rm-rule-block__agent'>User-agent: <strong>{$agentLabel}</strong></div>"
					. "<table class='rm-rule-table'>"
					. "<thead><tr><th>Directive</th><th>Path / Value</th><th>Meaning</th></tr></thead><tbody>";

				foreach ($block['rules'] as $rule) {
					$badge = $this->getRuleBadge($rule['directive'], $rule['value']);
					$cls   = 'rm-directive--' . strtolower(str_replace(['-', ' '], '', $rule['directive']));
					$val   = htmlspecialchars($rule['value'] !== '' ? $rule['value'] : '(empty)');
					$out  .= "<tr>"
						. "<td><span class='rm-directive {$cls}'>{$rule['directive']}</span></td>"
						. "<td><code>{$val}</code></td>"
						. "<td>{$badge}</td>"
						. "</tr>";
				}

				$out .= "</tbody></table></div>";

			} elseif ($block['type'] === 'sitemap') {
				$url = htmlspecialchars($block['url']);
				$out .= "<div class='rm-rule-block rm-rule-block--sitemap'>"
					. "<strong>Sitemap:</strong> <a href='{$url}' target='_blank'>{$url}</a>"
					. "</div>";
			}
		}

		$out .= "</div></div>";
		return $out;
	}

	protected function parseRobots(string $content): array {
		$lines   = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
		$blocks  = [];
		$current = null;

		foreach ($lines as $line) {
			$line = trim($line);

			// Empty line = end of current block
			if ($line === '') {
				if ($current !== null) { $blocks[] = $current; $current = null; }
				continue;
			}

			// Comment
			if (strpos($line, '#') === 0) continue;

			if (stripos($line, 'User-agent:') === 0) {
				$agent = trim(substr($line, 11));
				if ($current === null || !empty($current['rules'])) {
					if ($current !== null) $blocks[] = $current;
					$current = ['type' => 'directive', 'agent' => $agent, 'rules' => []];
				} else {
					$current['agent'] = $agent; // Multiple User-agent lines before rules
				}
			} elseif (stripos($line, 'Disallow:') === 0) {
				if ($current) $current['rules'][] = ['directive' => 'Disallow', 'value' => trim(substr($line, 9))];
			} elseif (stripos($line, 'Allow:') === 0) {
				if ($current) $current['rules'][] = ['directive' => 'Allow', 'value' => trim(substr($line, 6))];
			} elseif (stripos($line, 'Crawl-delay:') === 0) {
				if ($current) $current['rules'][] = ['directive' => 'Crawl-delay', 'value' => trim(substr($line, 12))];
			} elseif (stripos($line, 'Sitemap:') === 0) {
				if ($current !== null) { $blocks[] = $current; $current = null; }
				$blocks[] = ['type' => 'sitemap', 'url' => trim(substr($line, 8))];
			}
		}

		if ($current !== null) $blocks[] = $current;

		return $blocks;
	}

	protected function getRuleBadge(string $directive, string $value): string {
		$v = htmlspecialchars($value);
		if ($directive === 'Disallow') {
			if ($value === '') return "<span class='rm-badge rm-badge--ok'>Allows everything (empty Disallow)</span>";
			if ($value === '/') return "<span class='rm-badge rm-badge--block'>Entire site blocked</span>";
			return "<span class='rm-badge rm-badge--block'>Blocked: {$v}</span>";
		}
		if ($directive === 'Allow') {
			if ($value === '/') return "<span class='rm-badge rm-badge--ok'>Entire site allowed</span>";
			return "<span class='rm-badge rm-badge--ok'>Allowed: {$v}</span>";
		}
		if ($directive === 'Crawl-delay') {
			return "<span class='rm-badge rm-badge--info'>{$v}s pause between requests</span>";
		}
		return '';
	}

	// -----------------------------------------------------------------------
	// Styles
	// -----------------------------------------------------------------------

	protected function renderStyles(): string {
		return <<<'CSS'
<style>
/* Layout */
#rm-layout {
	display: flex;
	gap: 0;
	align-items: stretch;
	min-height: 500px;
}

/* Sidebar */
#rm-sidebar {
	width: 220px;
	flex-shrink: 0;
	border-right: 1px solid #e3e3e3;
	padding-right: 20px;
	margin-right: 24px;
}

/* Presets list */
.rm-preset {
	cursor: pointer;
	padding: 8px 0;
	line-height: 1.4;
}
.rm-preset:hover { background: transparent; }
.rm-preset__label {
	font-size: 14px;
	font-weight: 600;
	color: #1a66cc;
}
.rm-preset:hover .rm-preset__label { text-decoration: underline; }

/* Editor stretches to match sidebar height */
#rm-main {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
}
#rm-editor {
	flex: 1;
	width: 100%;
	min-height: 480px;
	font-family: 'Courier New', Courier, monospace;
	font-size: 15px;
	line-height: 1.7;
	padding: 14px;
	background: #1e1e1e;
	color: #d4d4d4;
	border: 1px solid #c3c3c3;
	border-radius: 3px;
	resize: vertical;
	box-sizing: border-box;
}
#rm-editor:focus {
	outline: none;
	border-color: #1a66cc;
}

/* Rules overview */
.rm-rules { display: flex; flex-direction: column; gap: 12px; margin-top: 20px; }
.rm-rule-block { border: 1px solid #e3e3e3; border-radius: 3px; overflow: hidden; }
.rm-rule-block__agent { background: #37474f; color: #eceff1; padding: 7px 14px; font-size: 13px; }
.rm-rule-block--sitemap { padding: 10px 14px; background: #f8fff8; border-color: #b2dfb2; }

.rm-rule-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.rm-rule-table th { background: #f5f5f5; padding: 6px 12px; text-align: left; font-weight: 600; color: #555; border-bottom: 1px solid #e3e3e3; }
.rm-rule-table td { padding: 6px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.rm-rule-table tr:last-child td { border-bottom: none; }

.rm-directive { padding: 1px 7px; border-radius: 2px; font-size: 12px; font-weight: 600; }
.rm-directive--allow      { background: #e8f5e9; color: #2e7d32; }
.rm-directive--disallow   { background: #ffebee; color: #c62828; }
.rm-directive--crawldelay { background: #fff8e1; color: #e65100; }

.rm-badge { padding: 1px 8px; border-radius: 9px; font-size: 11px; font-weight: 600; }
.rm-badge--ok    { background: #e8f5e9; color: #2e7d32; }
.rm-badge--block { background: #ffebee; color: #c62828; }
.rm-badge--info  { background: #e3f2fd; color: #1565c0; }

@media (max-width: 860px) {
	#rm-layout { flex-direction: column; }
	#rm-sidebar { width: 100%; border-right: none; border-bottom: 1px solid #e3e3e3; padding-right: 0; padding-bottom: 16px; margin-right: 0; margin-bottom: 20px; }
	#rm-editor { min-height: 300px; }
}
</style>
CSS;
	}

	// -----------------------------------------------------------------------
	// Scripts
	// -----------------------------------------------------------------------

	protected function renderScripts(): string {
		return <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function() {
	var editor = document.getElementById('rm-editor');
	if (!editor) return;

	// Append preset to the editor
	document.querySelectorAll('.rm-preset').forEach(function(card) {
		card.addEventListener('click', function() {
			var rules   = card.dataset.rules;
			var current = editor.value.trim();
			editor.value = current ? current + '\n\n' + rules : rules;
			editor.scrollTop = editor.scrollHeight;
			editor.focus();
			card.style.borderColor = '#4caf50';
			card.style.background  = '#f1f8e9';
			setTimeout(function() {
				card.style.borderColor = '';
				card.style.background  = '';
			}, 600);
		});
	});

	// Clear editor
	var clearBtn = document.getElementById('rm-clear-btn');
	if (clearBtn) {
		clearBtn.addEventListener('click', function() {
			if (confirm('Clear all content in the editor?')) {
				editor.value = '';
				editor.focus();
			}
		});
	}
});
</script>
JS;
	}

	// -----------------------------------------------------------------------
	// Install / Uninstall
	// -----------------------------------------------------------------------

	public function ___install()   { parent::___install(); }
	public function ___uninstall() { parent::___uninstall(); }
}
