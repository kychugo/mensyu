<?php
/**
 * config/ai.php — AI API keys and model lists
 */

// Prefer environment variables; fall back to a gitignored config/local.php.
// Never commit real keys directly — use environment variables in production.
$_local_cfg = __DIR__ . '/local.php';
if (file_exists($_local_cfg)) include_once $_local_cfg;

defined('AI_SECRET_KEY')      || define('AI_SECRET_KEY',      getenv('AI_SECRET_KEY')      ?: '');
defined('AI_PUBLISHABLE_KEY') || define('AI_PUBLISHABLE_KEY', getenv('AI_PUBLISHABLE_KEY') ?: '');
define('AI_TEXT_ENDPOINT',   'https://text.pollinations.ai/openai');
define('AI_IMAGE_ENDPOINT',  'https://gen.pollinations.ai/prompt/');

define('AI_TEXT_MODELS',  ['deepseek', 'glm', 'qwen-large', 'qwen-safety']);
define('AI_IMAGE_MODELS', ['gptimage', 'wan-image', 'qwen-image', 'klein', 'zimage', 'flux']);
