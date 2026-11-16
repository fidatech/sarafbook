// Run against a disposable Chrome started with --remote-debugging-port=9237,
// --use-fake-device-for-media-stream and --use-fake-ui-for-media-stream.
// QA_HTML must be a rendered payment page with fictional data; APP_ORIGIN serves assets.
import fs from 'node:fs';
import assert from 'node:assert/strict';
import os from 'node:os';
import path from 'node:path';
const uploadFixture = path.join(os.tmpdir(), `remittance-photo-check-${process.pid}.png`);
const fixture = process.env.QA_HTML;
if (!fixture) throw new Error('Set QA_HTML to a rendered payment page with fictional data.');
const origin = process.env.APP_ORIGIN || 'http://127.0.0.1:8137';
const tabs = await (await fetch(process.env.CHROME_DEBUG_URL || 'http://127.0.0.1:9237/json')).json();
const ws = new WebSocket(tabs.find(tab => tab.type === 'page').webSocketDebuggerUrl);
await new Promise(resolve => ws.addEventListener('open', resolve, {once: true}));
let sequence = 0;
const pending = new Map();
const failures = [];
const command = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++sequence;
    const timer = setTimeout(() => {pending.delete(id); reject(new Error(`Timed out: ${method}`));}, 20000);
    pending.set(id, response => {clearTimeout(timer); response.error ? reject(new Error(JSON.stringify(response.error))) : resolve(response.result);});
    ws.send(JSON.stringify({id, method, params}));
});
ws.addEventListener('message', async event => {
    const result = JSON.parse(event.data);
    if (pending.has(result.id)) {pending.get(result.id)(result); pending.delete(result.id);}
    if (result.method === 'Runtime.exceptionThrown') failures.push(result.params.exceptionDetails.text);
    if (result.method === 'Fetch.requestPaused') {
        await command('Fetch.fulfillRequest', {requestId: result.params.requestId, responseCode: 200,
            responseHeaders: [{name: 'Content-Type', value: 'text/html; charset=utf-8'}],
            body: Buffer.from(fs.readFileSync(fixture, 'utf8').replaceAll('http://localhost', origin)).toString('base64')});
    }
});
const evaluate = async expression => {
    const result = await command('Runtime.evaluate', {expression, awaitPromise: true, returnByValue: true});
    if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
    return result.result.value;
};
const waitFor = expression => evaluate(`new Promise((resolve,reject)=>{const start=Date.now();const timer=setInterval(()=>{if(${expression}){clearInterval(timer);resolve(true)}else if(Date.now()-start>10000){clearInterval(timer);reject(new Error('Condition timed out'))}},50)})`);
const click = id => evaluate(`document.getElementById(${JSON.stringify(id)}).click()`);
try {
    await command('Runtime.enable');
    await command('Page.enable');
    await command('Fetch.enable', {patterns: [{urlPattern: origin + '/__payment-qa*', resourceType: 'Document', requestStage: 'Request'}]});
    await command('Emulation.setDeviceMetricsOverride', {width: 1440, height: 1450, deviceScaleFactor: 1, mobile: false});
    await command('Page.navigate', {url: origin + '/__payment-qa'});
    await waitFor(`document.getElementById('open-camera') && typeof window.remittancePhotoText !== 'undefined'`);
    await evaluate(`document.fonts.ready`);
    assert.equal(await evaluate(`document.getElementById('receiver_fingerprint').disabled && !new FormData(document.getElementById('payment-form')).has('receiver_fingerprint')`), true);
    await click('open-camera');
    await waitFor(`!document.getElementById('capture-photo').disabled`);
    await evaluate(`window.qaTracks=document.getElementById('receiver-camera').srcObject.getTracks()`);
    await click('capture-photo');
    await waitFor(`!document.getElementById('editor-panel').hidden && !document.getElementById('apply-photo').disabled`);
    assert.equal(await evaluate(`qaTracks.every(track=>track.readyState==='ended')`), true);
    const before = await evaluate(`document.getElementById('photo-canvas').toDataURL()`);
    await evaluate(`for (const [id,value] of [['photo-zoom',160],['photo-brightness',120],['photo-contrast',115]]) {const control=document.getElementById(id);control.value=value;control.dispatchEvent(new Event('input'))}document.getElementById('photo-canvas').dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowRight'}))`);
    await click('rotate-photo');
    assert.notEqual(await evaluate(`document.getElementById('photo-canvas').toDataURL()`), before);
    await click('apply-photo');
    await waitFor(`!document.getElementById('photo-studio').classList.contains('show') && document.getElementById('receiver_photo').files.length===1`);
    assert.deepEqual(await evaluate(`(async()=>{const file=document.getElementById('receiver_photo').files[0]; const bitmap=await createImageBitmap(file); return {type:file.type,width:bitmap.width,height:bitmap.height,valid:file.size<5242880}})()`), {type:'image/jpeg',width:720,height:960,valid:true});
    await waitFor(`!document.body.classList.contains('modal-open')`);
    await click('open-camera');
    await waitFor(`!document.getElementById('capture-photo').disabled`);
    await waitFor(`!bootstrap.Modal.getInstance(document.getElementById('photo-studio'))._isTransitioning`);
    await evaluate(`window.qaTracks=document.getElementById('receiver-camera').srcObject.getTracks(); bootstrap.Modal.getInstance(document.getElementById('photo-studio')).hide()`);
    await waitFor(`!document.body.classList.contains('modal-open')`);
    assert.equal(await evaluate(`qaTracks.every(track=>track.readyState==='ended') && document.getElementById('receiver_photo').files.length===1`),true);
    await evaluate(`window.qaMedia=navigator.mediaDevices.getUserMedia.bind(navigator.mediaDevices);navigator.mediaDevices.getUserMedia=()=>Promise.reject(new DOMException('Denied','NotAllowedError'))`);
    await click('open-camera');
    await waitFor(`document.getElementById('studio-status').classList.contains('text-danger')`);
    assert.equal(await evaluate(`!document.getElementById('native-camera').disabled && document.getElementById('apply-photo').disabled`),true);
    await waitFor(`!bootstrap.Modal.getInstance(document.getElementById('photo-studio'))._isTransitioning`);
    await evaluate(`bootstrap.Modal.getInstance(document.getElementById('photo-studio')).hide();navigator.mediaDevices.getUserMedia=window.qaMedia`);
    await waitFor(`!document.body.classList.contains('modal-open')`);
    await click('edit-photo');
    await waitFor(`!document.getElementById('apply-photo').disabled && document.getElementById('photo-studio').classList.contains('show')`);
    await click('reset-photo');
    assert.equal(await evaluate(`['zoom','brightness','contrast'].every(name=>document.getElementById('photo-'+name).value==='100')`),true);
    if(process.env.QA_EDITOR_SCREENSHOT){const result=await command('Page.captureScreenshot',{format:'png'});fs.writeFileSync(process.env.QA_EDITOR_SCREENSHOT,Buffer.from(result.data,'base64'));}
    await waitFor(`!bootstrap.Modal.getInstance(document.getElementById('photo-studio'))._isTransitioning`);
    await evaluate(`bootstrap.Modal.getInstance(document.getElementById('photo-studio')).hide()`);
    await waitFor(`!document.body.classList.contains('modal-open')`);
    // Exercise real file-input change events and the native camera fallback separately.
    const png = await evaluate(`(()=>{const c=document.createElement('canvas');c.width=900;c.height=600;const x=c.getContext('2d');const g=x.createLinearGradient(0,0,900,600);g.addColorStop(0,'#173c58');g.addColorStop(1,'#47b6a0');x.fillStyle=g;x.fillRect(0,0,900,600);return c.toDataURL('image/png').split(',')[1]})()`);
    fs.writeFileSync(uploadFixture,Buffer.from(png,'base64'));
    const {root} = await command('DOM.getDocument');
    const input = await command('DOM.querySelector',{nodeId:root.nodeId,selector:'#receiver_photo'});
    await command('DOM.setFileInputFiles',{nodeId:input.nodeId,files:[uploadFixture]});
    await waitFor(`!document.getElementById('apply-photo').disabled && document.getElementById('photo-studio').classList.contains('show')`);
    await click('apply-photo');
    await waitFor(`!document.body.classList.contains('modal-open')`);
    assert.equal(await evaluate(`document.getElementById('receiver_photo').files[0].name`),'receiver-photo.jpg');
    await evaluate(`window.qaCommitted=document.getElementById('receiver_photo').files[0]`);
    await click('open-camera');
    await waitFor(`!document.getElementById('capture-photo').disabled`);
    const native = await command('DOM.querySelector',{nodeId:root.nodeId,selector:'#native-camera'});
    await command('DOM.setFileInputFiles',{nodeId:native.nodeId,files:[uploadFixture]});
    await waitFor(`!document.getElementById('apply-photo').disabled && document.getElementById('camera-panel').hidden`);
    await evaluate(`bootstrap.Modal.getInstance(document.getElementById('photo-studio')).hide()`);
    await waitFor(`!document.body.classList.contains('modal-open')`);
    assert.equal(await evaluate(`document.getElementById('receiver_photo').files[0]===window.qaCommitted`),true);
    // A camera permission request resolving after Cancel must not keep any camera active.
    await evaluate(`(async()=>{window.qaLateStream=await window.qaMedia({video:true,audio:false});navigator.mediaDevices.getUserMedia=()=>new Promise(resolve=>window.qaResolve=resolve)})()`);
    await click('open-camera');
    await evaluate(`bootstrap.Modal.getInstance(document.getElementById('photo-studio')).hide();window.qaResolve(window.qaLateStream);navigator.mediaDevices.getUserMedia=window.qaMedia`);
    await waitFor(`window.qaLateStream.getTracks().every(track=>track.readyState==='ended')`);
    if(process.env.QA_SCREENSHOT){const result=await command('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});fs.writeFileSync(process.env.QA_SCREENSHOT,Buffer.from(result.data,'base64'));}
    await command('Emulation.setDeviceMetricsOverride',{width:390,height:844,deviceScaleFactor:1,mobile:true});
    assert.equal(await evaluate(`document.documentElement.scrollWidth <= innerWidth`),true);
    if(process.env.QA_MOBILE_SCREENSHOT){const result=await command('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});fs.writeFileSync(process.env.QA_MOBILE_SCREENSHOT,Buffer.from(result.data,'base64'));}
    assert.deepEqual(failures, []);
    console.log('PASS: camera capture, stream cleanup, crop/zoom/rotate/adjust, JPEG upload, cancellation, denied camera fallback, reset, file upload, native picker cancellation, late camera cleanup, disabled fingerprint and mobile width.');
} finally {ws.close();fs.rmSync(uploadFixture,{force:true});}
