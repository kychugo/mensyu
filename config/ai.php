<?php
/**
 * config/ai.php — AI API keys and model lists
 */

define('AI_SECRET_KEY',      'sk_I9LbeRaewORSMEdm2ontKkJEHgimbE1v');
define('AI_PUBLISHABLE_KEY', 'pk_ZQ4XnvfBU2tu6riY');
define('AI_TEXT_ENDPOINT',   'https://text.pollinations.ai/openai');
define('AI_IMAGE_ENDPOINT',  'https://gen.pollinations.ai/prompt/');

define('AI_TEXT_MODELS',  ['deepseek', 'glm', 'qwen-large', 'qwen-safety']);
define('AI_IMAGE_MODELS', ['gptimage', 'wan-image', 'qwen-image', 'klein', 'zimage', 'flux']);
