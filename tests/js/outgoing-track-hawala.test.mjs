import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/views/admin/accounts/show.blade.php', import.meta.url), 'utf8');
const start = source.indexOf('        window.autoOutgoingTrackHawala =');
const code = source.slice(start, source.indexOf('        window.autoIncomingTrackHawala =', start));

test('uses the selected track account and ignores outdated responses and manual edits', () => {
    let track = '2';
    let trackSubCategory = 2;
    let number;
    const requests = [];
    const context = vm.createContext({
        outgoingEditMode: false, outgoingTrackManual: false, HIDE_REMITTANCE_FIELDS: false,
        $: () => ({ find: selector => ({
            val(value) {
                if (arguments.length) { number = value; return this; }
                return selector.includes('remittance_mode') ? 'track' : track;
            },
            data(key) {
                return key === 'sub-category-id' ? trackSubCategory : undefined;
            },
        }) }),
        fetchNextHawala: (account, column, type, callback) => requests.push({ account, column, type, callback }),
    });
    context.window = context;
    vm.runInContext(code, context);
    context.autoOutgoingTrackHawala();
    assert.equal(requests[0].account, '2');
    assert.equal(requests[0].column, 'track_hawala_number');
    assert.equal(requests[0].type, 'outgoing');
    track = '3';
    context.autoOutgoingTrackHawala();
    requests[1].callback(101);
    requests[0].callback(1);
    assert.equal(number, 101);
    context.outgoingTrackManual = true;
    requests[1].callback(102);
    assert.equal(number, 101);
    context.outgoingTrackManual = false;
    context.outgoingEditMode = true;
    requests[1].callback(103);
    assert.equal(number, 101);

    context.outgoingEditMode = false;
    trackSubCategory = 1;
    context.autoOutgoingTrackHawala();
    assert.equal(requests.length, 2);
});
