import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

const api = fs.readFileSync(new URL('../inc/search-rest-api.php', import.meta.url), 'utf8');
const block = JSON.parse(fs.readFileSync(new URL('../src/blocks/search-autocomplete/block.json', import.meta.url), 'utf8'));

test('language scoping is opt-in', () => {
	assert.equal(block.attributes.useCurrentLanguage.default, false);
});

test('an invalid explicit post type cannot broaden to posts and pages', () => {
	assert.doesNotMatch(api, /\$query_args\['post_type'\]\s*=\s*\[ 'post', 'page' \]/);
	assert.match(api, /if \( ! \$query_args\['post_type'\] \) \{\s*return new WP_REST_Response\( \[\], 200 \);/);
});

test('term suggestions receive the requested language', () => {
	assert.match(api, /\$term_args\['lang'\] = \$lang;/);
});
