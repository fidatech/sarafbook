import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const script = fs.readFileSync(new URL('../../public/assets/js/remittance-payment-receipt.js', import.meta.url), 'utf8');
test('prints after fonts and layout are ready, and permits a manual reprint', async () => {
    let prints = 0, click, fontsReady;
    const frames = [];
    const context = {
        window: {print: () => prints++},
        document: {
            fonts: {ready: new Promise(resolve => {fontsReady = resolve;})},
            getElementById: () => ({addEventListener: (event, listener) => {assert.equal(event, 'click'); click = listener;}}),
        },
        requestAnimationFrame: callback => frames.push(callback),
    };
    vm.runInNewContext(script, context);
    assert.equal(prints, 0);
    fontsReady();
    await Promise.resolve();
    frames.shift()();
    assert.equal(prints, 0);
    frames.shift()();
    assert.equal(prints, 1);
    click();
    assert.equal(prints, 2);
});
