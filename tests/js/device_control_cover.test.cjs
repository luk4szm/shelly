const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const { runInNewContext } = require('node:vm');
const assert = require('node:assert/strict');
const test = require('node:test');

const source = readFileSync(join(__dirname, '../../public/js/device_control.js'), 'utf8');
const context = {
    document: {},
    $: () => ({ ready() {} }),
};

runInNewContext(source, context);

test('cover scenes send open and close without a position read', () => {
    assert.equal(context.shouldReadPositionBeforeSceneAction('covers', 'open'), false);
    assert.equal(context.shouldReadPositionBeforeSceneAction('covers', 'close'), false);
});

test('gate and garage retain their position checks', () => {
    for (const controller of ['gate', 'garage']) {
        assert.equal(context.shouldReadPositionBeforeSceneAction(controller, 'open'), true);
        assert.equal(context.shouldReadPositionBeforeSceneAction(controller, 'close'), true);
        assert.equal(context.shouldReadPositionBeforeSceneAction(controller, 'move'), false);
    }
});

test('cover indicator colors reflect the last direction only', () => {
    assert.equal(context.coverDirectionIndicatorClass('open'), 'bg-green');
    assert.equal(context.coverDirectionIndicatorClass('close'), 'bg-red');
    assert.equal(context.coverDirectionIndicatorClass(null), 'bg-warning');
});
