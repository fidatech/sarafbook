import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const source = readFileSync(new URL('../../resources/views/admin/remittances/create.blade.php', import.meta.url), 'utf8');

test('track account options exclude both selected remittance accounts and can be rebuilt', () => {
    const start = source.indexOf('        function syncTrackAccountOptions()');
    const end = source.indexOf("        $('#from_account_id,#to_account_id,#track_account_id')", start);
    const code = source.slice(start, end);

    assert.ok(start >= 0, 'syncTrackAccountOptions should exist');
    assert.match(code, /#from_account_id/);
    assert.match(code, /#to_account_id/);
    assert.match(code, /trackSelect\.empty\(\)/);
    assert.match(code, /trackAccountOptions\.forEach/);
    assert.match(code, /!selectedAccountIds\.has\(String\(option\.value\)\)/);
});
