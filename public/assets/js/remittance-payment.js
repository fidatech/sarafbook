(() => {
    'use strict';
    const form = document.getElementById('payment-form');
    if (!form) return;
    const $ = id => document.getElementById(id);
    const text = window.remittancePhotoText;
    const fileInput = $('receiver_photo');
    const modalElement = $('photo-studio');
    const modal = new bootstrap.Modal(modalElement);
    const canvas = $('photo-canvas');
    const ctx = canvas.getContext('2d');
    const video = $('receiver-camera');
    const controls = ['zoom', 'brightness', 'contrast'];
    let stream = null, generation = 0, image = null, sourceFile = null;
    let angle = 0, offsetX = 0, offsetY = 0, drag = null, previewUrl = null, idUrl = null;
    let saving = false;
    const status = (message, error = false) => {
        $('studio-status').textContent = message;
        $('studio-status').classList.toggle('text-danger', error);
    };
    const photoStatus = (message, error = false) => {
        $('photo-status').textContent = message;
        $('photo-status').classList.toggle('text-danger', error);
    };
    function stopCamera() {
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = null;
        video.srcObject = null;
        $('capture-photo').disabled = true;
    }
    function resetEdits() {
        angle = 0; offsetX = 0; offsetY = 0;
        controls.forEach(name => {
            $('photo-' + name).value = 100;
            $('value-' + name).value = '100%';
        });
        draw();
    }
    function draw() {
        if (!image) return;
        const sideways = angle % 180 !== 0;
        const width = sideways ? image.height : image.width;
        const height = sideways ? image.width : image.height;
        const scale = Math.max(canvas.width / width, canvas.height / height) * Number($('photo-zoom').value) / 100;
        const maxX = Math.max(0, (width * scale - canvas.width) / 2);
        const maxY = Math.max(0, (height * scale - canvas.height) / 2);
        offsetX = Math.max(-maxX, Math.min(maxX, offsetX));
        offsetY = Math.max(-maxY, Math.min(maxY, offsetY));
        ctx.save();
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.translate(canvas.width / 2 + offsetX, canvas.height / 2 + offsetY);
        ctx.rotate(angle * Math.PI / 180);
        ctx.scale(scale, scale);
        ctx.drawImage(image, -image.width / 2, -image.height / 2);
        ctx.restore();
        // Pixel adjustments also work in browsers without CanvasRenderingContext2D.filter.
        const brightness = Number($('photo-brightness').value) / 100;
        const contrast = Number($('photo-contrast').value) / 100;
        if (brightness !== 1 || contrast !== 1) {
            const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < pixels.data.length; i += 4) {
                for (let c = 0; c < 3; c++) pixels.data[i + c] = (pixels.data[i + c] * brightness - 128) * contrast + 128;
            }
            ctx.putImageData(pixels, 0, 0);
        }
    }
    function showPreview(file) {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        $('preview-photo').src = previewUrl;
        $('preview-photo').hidden = false;
        $('photo-placeholder').hidden = true;
        $('edit-photo').disabled = false;
        photoStatus(text.photo_ready);
    }
    function validFile(file) {
        return file && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type) && file.size <= 5 * 1024 * 1024;
    }
    async function loadEditor(file) {
        const token = ++generation;
        stopCamera();
        $('apply-photo').disabled = true;
        status('');
        const url = URL.createObjectURL(file);
        try {
            const next = new Image();
            next.src = url;
            await next.decode();
            if (token !== generation) return;
            image = next;
            $('camera-panel').hidden = true;
            $('editor-panel').hidden = false;
            resetEdits();
            $('apply-photo').disabled = false;
        } catch {
            if (token === generation) status(text.photo_error, true);
        } finally {
            URL.revokeObjectURL(url);
        }
    }
    async function startCamera() {
        const token = ++generation;
        stopCamera();
        image = null;
        $('editor-panel').hidden = true;
        $('camera-panel').hidden = false;
        $('apply-photo').disabled = true;
        status(text.starting_camera);
        modal.show();
        try {
            if (!navigator.mediaDevices?.getUserMedia) throw new Error('Camera unavailable');
            const nextStream = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'user', width: {ideal: 1280}, height: {ideal: 960}}, audio: false});
            if (token !== generation) { nextStream.getTracks().forEach(track => track.stop()); return; }
            stream = nextStream;
            video.srcObject = stream;
            await video.play();
            if (token !== generation) return;
            $('capture-photo').disabled = !video.videoWidth;
            status('');
        } catch {
            if (token === generation) { stopCamera(); status(text.camera_error, true); }
        }
    }
    $('open-camera').addEventListener('click', startCamera);
    $('retake-photo').addEventListener('click', startCamera);
    $('capture-photo').addEventListener('click', () => {
        if (!stream || !video.videoWidth) return;
        const token = generation;
        const snapshot = document.createElement('canvas');
        snapshot.width = video.videoWidth;
        snapshot.height = video.videoHeight;
        snapshot.getContext('2d').drawImage(video, 0, 0);
        $('capture-photo').disabled = true;
        snapshot.toBlob(blob => {
            if (token !== generation) return;
            if (!blob) { stopCamera(); status(text.save_error, true); return; }
            loadEditor(blob);
        }, 'image/jpeg', 0.95);
    });
    $('edit-photo').addEventListener('click', () => {
        if (!sourceFile) return;
        modal.show();
        loadEditor(sourceFile);
    });
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) return;
        if (!validFile(file)) {
            fileInput.value = ''; sourceFile = null;
            $('preview-photo').hidden = true;
            $('photo-placeholder').hidden = false;
            $('edit-photo').disabled = true;
            photoStatus(text.photo_error, true);
            return;
        }
        sourceFile = file;
        showPreview(file);
        modal.show();
        loadEditor(file);
    });
    $('native-camera').addEventListener('change', event => {
        const file = event.target.files[0];
        event.target.value = '';
        if (!validFile(file)) { status(text.photo_error, true); return; }
        modal.show();
        loadEditor(file);
    });
    controls.forEach(name => $('photo-' + name).addEventListener('input', () => {
        $('value-' + name).value = $('photo-' + name).value + '%';
        draw();
    }));
    $('rotate-photo').addEventListener('click', () => { angle = (angle + 90) % 360; offsetX = 0; offsetY = 0; draw(); });
    $('reset-photo').addEventListener('click', resetEdits);
    canvas.addEventListener('pointerdown', event => {
        drag = {id: event.pointerId, x: event.clientX, y: event.clientY};
        canvas.setPointerCapture(event.pointerId);
    });
    canvas.addEventListener('pointermove', event => {
        if (!drag || drag.id !== event.pointerId) return;
        const rect = canvas.getBoundingClientRect();
        offsetX += (event.clientX - drag.x) * canvas.width / rect.width;
        offsetY += (event.clientY - drag.y) * canvas.height / rect.height;
        drag.x = event.clientX; drag.y = event.clientY;
        draw();
    });
    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(name => canvas.addEventListener(name, () => { drag = null; }));
    canvas.addEventListener('keydown', event => {
        const moves = {ArrowLeft: [-15, 0], ArrowRight: [15, 0], ArrowUp: [0, -15], ArrowDown: [0, 15]};
        if (!moves[event.key]) return;
        event.preventDefault();
        offsetX += moves[event.key][0]; offsetY += moves[event.key][1]; draw();
    });
    $('apply-photo').addEventListener('click', () => {
        if (!image || saving) return;
        const token = generation;
        saving = true;
        $('apply-photo').disabled = true;
        status(text.saving);
        canvas.toBlob(blob => {
            saving = false;
            if (token !== generation) return;
            if (!blob) { status(text.save_error, true); $('apply-photo').disabled = false; return; }
            try {
                const edited = new File([blob], 'receiver-photo.jpg', {type: 'image/jpeg'});
                const transfer = new DataTransfer();
                transfer.items.add(edited);
                fileInput.files = transfer.files;
                sourceFile = edited;
                showPreview(edited);
                modal.hide();
            } catch {
                status(text.save_error, true);
                $('apply-photo').disabled = false;
            }
        }, 'image/jpeg', 0.9);
    });
    modalElement.addEventListener('hide.bs.modal', () => { ++generation; stopCamera(); drag = null; });
    modalElement.addEventListener('hidden.bs.modal', () => { image = null; status(''); $('open-camera').focus(); });
    window.addEventListener('pagehide', () => { ++generation; stopCamera(); });
    document.addEventListener('visibilitychange', () => { if (document.hidden && modalElement.classList.contains('show')) modal.hide(); });
    $('receiver_id_copy').addEventListener('change', event => {
        const preview = $('preview-id-copy');
        if (idUrl) URL.revokeObjectURL(idUrl);
        preview.hidden = true;
        preview.removeAttribute('src');
        const file = event.target.files[0];
        if (file && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            idUrl = URL.createObjectURL(file); preview.src = idUrl; preview.hidden = false;
        }
    });
    form.addEventListener('submit', () => { stopCamera(); $('pay-button').disabled = true; $('pay-button').textContent = text.processing; });
})();
